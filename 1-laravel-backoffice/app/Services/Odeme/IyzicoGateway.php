<?php

namespace App\Services\Odeme;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

/**
 * Gerçek iyzico 3D Secure (checkout form) entegrasyonu.
 * config/services.php iyzico anahtarları doldurulunca devreye girer.
 */
class IyzicoGateway implements OdemeGateway
{
    public function baslat(Order $siparis): array
    {
        $aylar = config('services.iyzico');
        $apiKey = $aylar['key'];
        $secret = $aylar['secret'];
        $adres = rtrim($aylar['base'], '/');
        $rastgele = bin2hex(random_bytes(8));

        $girdi = [
            'locale' => 'tr',
            'conversationId' => $siparis->order_no,
            'price' => number_format((float) $siparis->total, 2, '.', ''),
            'paidPrice' => number_format((float) $siparis->total, 2, '.', ''),
            'currency' => 'TRY',
            'basketId' => $siparis->order_no,
            'paymentGroup' => 'PRODUCT',
            'callbackUrl' => url('/odeme/3ds-geri-donus/' . $siparis->order_no),
            'buyer' => [
                'id' => (string) $siparis->contact_id,
                'name' => mb_substr($siparis->customer_name ?? 'Müşteri', 0, 50),
                'surname' => '—',
                'email' => $siparis->customer_email ?? 'musteri@hepbirarada.test',
                'ip' => request()->ip() ?? '127.0.0.1',
                'city' => 'İstanbul',
                'country' => 'Türkiye',
                'address' => $siparis->shipping_address ?? '—',
            ],
            'basketItems' => collect($siparis->items)->map(fn ($k) => [
                'id' => (string) $k['product_id'],
                'name' => mb_substr((string) $k['product_name'], 0, 100),
                'category1' => 'Genel',
                'itemType' => 'PHYSICAL',
                'price' => number_format((float) $k['line_total'], 2, '.', ''),
            ])->values()->all(),
        ];

        $icerik = json_encode($girdi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $imza = base64_encode(hash_hmac('sha256', $apiKey . $rastgele . $secret . $icerik, $secret, true));

        $yanit = Http::withHeaders([
            'Authorization' => 'IYZWS ' . $apiKey . ':' . $secret,
            'x-iyzi-rnd' => $rastgele,
            'x-iyzi-client-id' => $apiKey,
            'x-iyzi-transaction-date' => now()->format('Y-m-d\TH:i:s'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($adres . '/payment/iyzipay/resource/3ds/initialize/checkoutform', $girdi);

        $sonuc = $yanit->json() ?? [];

        if (($sonuc['status'] ?? '') !== 'success') {
            throw new \RuntimeException('iyzico 3DS başlatılamadı: ' . ($sonuc['errorMessage'] ?? 'bilinmeyen hata'));
        }

        // Token PSP tarafında; siparişe da yazılır ki callback eşleşsin
        $siparis->update(['odeme_token' => (string) ($sonuc['token'] ?? '')]);

        return [
            'odeme_url' => (string) ($sonuc['paymentPageUrl'] ?? ''),
            'token' => (string) ($sonuc['token'] ?? ''),
        ];
    }

    public function dogrula(Order $siparis, array $girdi = []): bool
    {
        $aylar = config('services.iyzico');
        $apiKey = $aylar['key'];
        $secret = $aylar['secret'];
        $adres = rtrim($aylar['base'], '/');
        $rastgele = bin2hex(random_bytes(8));

        $girdi = [
            'locale' => 'tr',
            'conversationId' => $siparis->order_no,
            'token' => $siparis->odeme_token,
        ];
        $icerik = json_encode($girdi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $imza = base64_encode(hash_hmac('sha256', $apiKey . $rastgele . $secret . $icerik, $secret, true));

        $yanit = Http::withHeaders([
            'Authorization' => 'IYZWS ' . $apiKey . ':' . $secret,
            'x-iyzi-rnd' => $rastgele,
            'x-iyzi-client-id' => $apiKey,
            'x-iyzi-transaction-date' => now()->format('Y-m-d\TH:i:s'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($adres . '/payment/iyzipay/resource/3ds/auth/checkoutform/detail', $girdi);

        $sonuc = $yanit->json() ?? [];

        return ($sonuc['paymentStatus'] ?? '') === 'SUCCESS';
    }

    public function ad(): string
    {
        return 'iyzico-3ds';
    }
}
