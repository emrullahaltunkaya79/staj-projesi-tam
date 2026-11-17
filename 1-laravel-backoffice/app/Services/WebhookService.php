<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * E-ticaret sitesine olay bildirimi (webhook) gönderir.
 *
 * Teslimatlar webhook_deliveries tablosuna kaydedilir — panelde listelenir,
 * başarısız olanlar tekrar gönderilebilir.
 */
class WebhookService
{
    /**
     * Kayıtlı teslimatı yeniden gönderir (retry).
     */
    public function retry(WebhookDelivery $delivery): WebhookDelivery
    {
        // deliver() sonucu yeni satır olarak açar; sonucu eski teslimata
        // taşıyıp yeni satırı siliyoruz — null güncelleme ve yineleme olmasın.
        $sonuc = $this->deliver($delivery->event, $delivery->payload, $delivery->url, $delivery->attempts + 1);

        $delivery->update([
            'success' => $sonuc->success,
            'response_code' => $sonuc->response_code,
            'attempts' => $delivery->attempts + 1,
            'delivered_at' => $sonuc->delivered_at,
        ]);

        $sonuc->delete();

        return $delivery->fresh();
    }

    /**
     * Stok değişimi bildirimi.
     */
    public function stockChanged(int $productId, int $newStock, int $available, string $productName): void
    {
        $this->send('stock.changed', [
            'product_id' => $productId,
            'product_name' => $productName,
            'stock_quantity' => $newStock,
            'available_quantity' => $available,
        ]);
    }

    /**
     * Ürün oluşturma/güncelleme bildirimi (katalog senkronizasyonu).
     */
    public function productUpsert(int $productId, string $name, ?string $code, float $price, int $stock, ?string $category, ?string $imageBase64 = null): void
    {
        $payload = [
            'product_id' => $productId,
            'name' => $name,
            'code' => $code,
            'price' => $price,
            'stock_quantity' => $stock,
            'category' => $category,
        ];

        if ($imageBase64) {
            $payload['image_base64'] = $imageBase64;
        }

        $this->send('product.upserted', $payload);
    }

    /**
     * Ürün silme bildirimi.
     */
    public function productDeleted(int $productId, string $name): void
    {
        $this->send('product.deleted', ['product_id' => $productId, 'name' => $name]);
    }

    /**
     * Sipariş ödendi bildirimi.
     */
    public function orderPaid(string $orderNo, ?string $externalRef, float $total, ?string $invoiceNo, ?string $trackingUrl): void
    {
        $this->send('order.paid', [
            'order_no' => $orderNo,
            'external_ref' => $externalRef,
            'total' => $total,
            'invoice_no' => $invoiceNo,
            'tracking_url' => $trackingUrl,
        ]);
    }

    /**
     * Sipariş iptal bildirimi — e-ticaret siparişi iptal edip stok geri alsın.
     */
    public function orderCancelled(string $orderNo, string $externalRef, string $reason, float $refunded = 0): void
    {
        $this->send('order.cancelled', [
            'order_no' => $orderNo,
            'external_ref' => $externalRef,
            'reason' => $reason,
            'refunded_amount' => $refunded,
        ]);
    }

    /**
     * İade tamamlandı bildirimi — mağaza sipariş sayfası iade durumunu anında günceller.
     */
    public function orderReturned(string $orderNo, ?string $externalRef, float $refunded, bool $kismi = false): void
    {
        $this->send('order.returned', [
            'order_no' => $orderNo,
            'external_ref' => $externalRef,
            'refunded_amount' => $refunded,
            'partial' => $kismi,
        ]);
    }

    /**
     * Kargo bilgisi bildirimi — e-ticaret müşteriye takip linki göstersin.
     */
    public function orderShipped(string $orderNo, string $externalRef, string $carrier, string $trackingNo, string $trackingUrl): void
    {
        $this->send('order.shipped', [
            'order_no' => $orderNo,
            'external_ref' => $externalRef,
            'carrier' => $carrier,
            'tracking_no' => $trackingNo,
            'tracking_url' => $trackingUrl,
        ]);
    }

    /**
     * Teslim bildirimi — mağaza tarafında iade penceresi teslimden sonra açılır.
     */
    public function orderDelivered(string $orderNo, string $externalRef): void
    {
        $this->send('order.delivered', [
            'order_no' => $orderNo,
            'external_ref' => $externalRef,
            'delivered_at' => now()->toIso8601String(),
        ]);
    }

    private function send(string $event, array $payload): void
    {
        $url = Setting::webhookUrl();

        if (! $url) {
            Log::debug("Webhook atlandı (URL tanımlı değil): {$event}");

            return;
        }

        // Kuyruk üzerinden gönder: API yanıtı bloklanmasın (test ortamında sync çalışır)
        \App\Jobs\SendWebhookJob::dispatch($event, $payload, $url);
    }

    public function deliver(string $event, array $payload, string $url, int $attempts): WebhookDelivery
    {
        $body = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'payload' => $payload,
        ];

        $signature = hash_hmac('sha256', json_encode($body), Setting::webhookSecret());
        $success = false;
        $code = null;

        try {
            // Zaman aşımı gibi geçici tıkanmalarda bir kez daha denenir
            $response = Http::withHeaders([
                'X-Signature' => $signature,
                'X-Event' => $event,
            ])->timeout(10)->retry(2, 1500, throw: false)->post($url, $body);

            $code = $response->status();
            $success = $response->successful();
        } catch (\Throwable $e) {
            Log::warning("Webhook gönderilemedi: {$event} — {$e->getMessage()}");
        }

        return WebhookDelivery::create([
            'event' => $event,
            'url' => $url,
            'payload' => $payload,
            'response_code' => $code,
            'success' => $success,
            'attempts' => $attempts,
            'delivered_at' => now(),
        ]);
    }
}
