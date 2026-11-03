<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingOrder(User $user): array
    {
        $contact = Contact::create(['company_id' => $user->company_id, 'type' => 'musteri', 'name' => 'Webhook Cari']);
        $product = Product::create([
            'company_id' => $user->company_id, 'name' => 'Webhook Ürün', 'unit' => 'adet',
            'purchase_price' => 50, 'sale_price' => 100, 'stock_quantity' => 10,
        ]);

        $service = app(OrderService::class);
        $order = $service->create($user->company_id, [
            'contact_id' => $contact->id,
            'external_ref' => 'ETC-777',
            'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100]],
        ]);

        return [$contact, $product, $order, $service];
    }

    /** @test */
    public function iptal_edilen_siparis_icin_webhook_gonderilir(): void
    {
        Http::fake();

        config(['services.ecommerce.webhook_url' => 'https://eticaret.example.com/webhook']);
        config(['services.ecommerce.webhook_secret' => 'test-secret']);

        $user = User::factory()->create();
        $this->actingAs($user);

        [$contact, $product, $order, $service] = $this->createPendingOrder($user);
        $service->cancel($order, $user->company_id);

        Http::assertSent(function ($request) use ($order) {
            return $request->url() === 'https://eticaret.example.com/webhook'
                && $request['event'] === 'order.cancelled'
                && $request['payload']['order_no'] === $order->order_no
                && $request['payload']['external_ref'] === 'ETC-777'
                && str_contains($request['payload']['reason'], 'iptal');
        });
    }

    /** @test */
    public function kargo_bilgisinde_takip_url_webhook_ile_gider(): void
    {
        Http::fake();

        config(['services.ecommerce.webhook_url' => 'https://eticaret.example.com/webhook']);
        config(['services.ecommerce.webhook_secret' => 'test-secret']);

        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        [$contact, $product, $order, $service] = $this->createPendingOrder($user);
        $service->markPaid($order, $user->company_id);

        Http::assertSent(fn ($r) => $r['event'] === 'order.paid');

        $order = $order->fresh();

        $this->post('/siparisler/'.$order->id.'/kargo', [
            'tracking_no' => 'TK123456',
            'carrier' => 'Aras Kargo',
        ])->assertRedirect();

        // Aras Kargo takip URL'i üretildi mi?
        $this->assertStringContainsString('araskargo.com.tr', $order->fresh()->trackingUrl());

        // Shipped webhook'u gönderildi mi?
        Http::assertSent(fn ($r) => $r['event'] === 'order.shipped'
            && $r['payload']['carrier'] === 'Aras Kargo'
            && $r['payload']['tracking_no'] === 'TK123456'
            && str_contains($r['payload']['tracking_url'], 'araskargo.com.tr'));
    }

    /** @test */
    public function webhook_url_tanimli_degilse_hata_vermeden_atlar(): void
    {
        config(['services.ecommerce.webhook_url' => null]);

        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        [$contact, $product, $order, $service] = $this->createPendingOrder($user);

        // Exception fırlatmadan tamamlanmalı
        $service->cancel($order, $user->company_id);

        $this->assertSame('cancelled', $order->fresh()->status);
    }
}
