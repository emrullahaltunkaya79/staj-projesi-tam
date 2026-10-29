<?php

namespace App\Console\Commands;

use App\Models\CasperPayment;
use App\Services\CasperPaymentService;
use App\Services\OrderService;
use Illuminate\Console\Command;

class CheckCasperPayments extends Command
{
    protected $signature = 'casper:odemeler-kontrol';

    protected $description = 'Bekleyen CSPR ödemelerini blok zincirde kontrol eder, onaylananları faturalaştırır, süresi geçenleri expire eder';

    public function handle(CasperPaymentService $casperService, OrderService $orderService): int
    {
        $confirmed = 0;
        $expired = 0;

        $payments = CasperPayment::with('order')->where('status', 'waiting')->get();

        foreach ($payments as $payment) {
            if ($payment->isExpired()) {
                $casperService->expireOldPayments($payment->company_id);
                $expired++;
                continue;
            }

            $checked = $casperService->checkOnChain($payment);

            if ($checked->status === 'confirmed') {
                // Ödeme onaylandı → siparişi faturalaştır
                $orderService->markPaid($checked->order->fresh(), $checked->company_id);
                $confirmed++;
            }
        }

        $this->info("Kontrol: {$payments->count()} bekleyen, {$confirmed} onaylandı, {$expired} expire edildi.");

        return self::SUCCESS;
    }
}
