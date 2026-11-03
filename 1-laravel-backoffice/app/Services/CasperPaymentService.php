<?php

namespace App\Services;

use App\Models\CasperPayment;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Casper Network (CSPR) kripto ödeme servisi.
 *
 * Akış:
 *  1. createPayment()  → sipariş için benzersiz memo üretir, CSPR tutarı hesaplar
 *  2. checkPayment()   → Casper event/RPC API'den bu memo'ya gelen transferi sorgular
 *  3. Onaylanınca → sipariş markPaid (fatura kesilir, stok düşer)
 *
 * Not: Geliştirme ortamında CASPER_NETWORK=testnet iken blok zincir erişimi
 * olmasa bile "detected" durumuna elle geçirilebilir (test için forceConfirm).
 */
class CasperPaymentService
{
    /**
     * Bekleyen sipariş için CSPR ödeme kaydı oluşturur.
     */
    public function createPayment(Order $order, int $companyId): CasperPayment
    {
        // Zaten aktif bir CSPR ödemesi varsa onu döndür
        $existing = CasperPayment::where('order_id', $order->id)
            ->where('status', 'waiting')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return $existing;
        }

        $rate = (float) \App\Models\Setting::casperRate();
        $amountCspr = round((float) $order->total / max($rate, 0.0001), 9);

        // Benzersiz memo: ödemeyi siparişe bağlayan kimlik
        $memo = $order->order_no.'-'.strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));

        return CasperPayment::create([
            'company_id' => $companyId,
            'order_id' => $order->id,
            'memo' => $memo,
            'amount_cspr' => $amountCspr,
            'amount_try' => (float) $order->total,
            'network' => \App\Models\Setting::casperNetwork(),
            'status' => 'waiting',
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    /**
     * Casper ağında bu memo'ya gelen transferi sorgular.
     * (Event Store API — transfers by target account)
     */
    public function checkOnChain(CasperPayment $payment): CasperPayment
    {
        if ($payment->status !== 'waiting') {
            return $payment;
        }

        $address = \App\Models\Setting::casperAddress();

        if (! $address) {
            return $payment; // cüzdan adresi tanımlı değil — beklemede kalır
        }

        try {
            $eventUrl = config('services.casper.event_url');
            $response = Http::timeout(15)->get("{$eventUrl}/transfers/target/{$address}", [
                'page_size' => 50,
            ]);

            if (! $response->successful()) {
                return $payment;
            }

            $transfers = $response->json('data') ?? [];

            foreach ($transfers as $transfer) {
                $memoField = data_get($transfer, 'memo', '');

                // Memo eşleşmesi + tutar kontrolü
                if (str_contains($memoField, $payment->memo)) {
                    $amount = (float) data_get($transfer, 'amount', 0) / 1e9; // motes → CSPR

                    if ($amount + 0.0001 >= (float) $payment->amount_cspr) {
                        $payment->update([
                            'status' => 'confirmed',
                            'deploy_hash' => data_get($transfer, 'deploy_hash'),
                            'confirmed_at' => now(),
                        ]);

                        return $payment;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Casper ödeme kontrolü başarısız: '.$e->getMessage());
        }

        return $payment;
    }

    /**
     * Test/geliştirme: ödemeyi elle onaylar (blok zincir beklemeden).
     */
    public function forceConfirm(CasperPayment $payment): CasperPayment
    {
        $payment->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'deploy_hash' => 'MANUAL-'.strtoupper(substr(bin2hex(random_bytes(8)), 0, 12)),
        ]);

        return $payment;
    }

    /**
     * Süresi geçen bekleyen ödemeleri expired yapar.
     */
    public function expireOldPayments(int $companyId): int
    {
        return CasperPayment::where('company_id', $companyId)
            ->where('status', 'waiting')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }
}
