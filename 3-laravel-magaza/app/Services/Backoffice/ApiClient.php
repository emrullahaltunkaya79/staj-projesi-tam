<?php

namespace App\Services\Backoffice;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Arka ofis (Ön Muhasebe) API istemcisi.
 *
 * Tüm mağaza verisi (katalog, stok, sipariş) arka ofisten gelir;
 * mağaza hiçbir iş mantığını yeniden icat etmez.
 */
class ApiClient
{
    public function __construct(
        private string $baseUrl,
        private string $token,
        private int $timeout,
    ) {}

    public static function make(): self
    {
        return new self(
            rtrim(config('backoffice.base_url'), '/'),
            (string) config('backoffice.token'),
            (int) config('backoffice.timeout'),
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function products(?int $categoryId = null): array
    {
        return $this->get('/products', array_filter(['category_id' => $categoryId]))['data'] ?? [];
    }

    public function product(int $id): array
    {
        return $this->get("/products/{$id}")['data'] ?? [];
    }

    public function stock(array $productIds = []): array
    {
        $query = [];
        foreach ($productIds as $id) {
            $query['product_ids'][] = $id;
        }

        return $this->get('/stock', $query)['data'] ?? [];
    }

    /** Idempotent: aynı e-posta aynı cariye eşlenir. */
    public function findOrCreateCustomer(string $name, string $email): array
    {
        return $this->post('/customers', ['name' => $name, 'email' => $email, 'type' => 'musteri']);
    }

    /** Sipariş oluşturur — stok arka ofiste rezerve olur. */
    public function createOrder(array $payload): array
    {
        return $this->post('/orders', $payload);
    }

    public function order(string $orderNo): array
    {
        return $this->get("/orders/{$orderNo}");
    }

    /** Müşterinin son siparişleri ("Siparişlerim" sayfası). */
    public function ordersByEmail(string $email, int $limit = 20): array
    {
        return $this->get('/orders', ['email' => $email, 'limit' => $limit]);
    }

    public function pay(string $orderNo): array
    {
        return $this->post("/orders/{$orderNo}/pay", ['payment_method' => 'cspr']);
    }

    /** CSPR ödeme talimatı: adres + memo + tutar + QR (bekleyen sipariş için). */
    public function csprPayment(string $orderNo): array
    {
        return $this->post("/orders/{$orderNo}/cspr-payment", []);
    }

    /** CSPR ödeme durumu (memo ile). */
    public function csprStatus(string $memo): array
    {
        return $this->get("/cspr-payments/{$memo}");
    }

    public function cancel(string $orderNo, string $reason = ''): array
    {
        return $this->post("/orders/{$orderNo}/cancel", array_filter(['reason' => $reason]));
    }

    // ---------- alt katman ----------

    private function get(string $path, array $query = []): array
    {
        return $this->send('get', $path, null, $query);
    }

    private function post(string $path, array $data): array
    {
        return $this->send('post', $path, $data);
    }

    private function send(string $method, string $path, ?array $data = null, array $query = []): array
    {
        if ($this->token === '') {
            throw new RuntimeException(
                'BACKOFFICE_TOKEN tanımlı değil — arka ofis panelinden API token oluşturup .env dosyasına girin.'
            );
        }

        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout($this->timeout)
                ->retry(2, 300, throw: false)
                ->{$method}($this->baseUrl.$path, $method === 'get' ? $query : $data);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Arka ofise ulaşılamıyor — sunucunun açık olduğundan emin olun.', 0, $e);
        }

        if ($response->status() === 401) {
            throw new RuntimeException('Arka ofis token geçersiz (401) — panelden yeni token oluşturun.');
        }

        if ($response->serverError()) {
            throw new RuntimeException('Arka ofis hatası ('.$response->status().') — birazdan tekrar deneyin.');
        }

        return $response->json() ?? [];
    }
}
