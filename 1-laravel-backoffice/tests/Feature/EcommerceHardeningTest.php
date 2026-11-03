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

/**
 * E-ticaret sağlamlaştırma: ürün senkron webhook'ları, siparişte
 * idempotency ve barkod desteği.
 */
class EcommerceHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function loginAdmin(): User
    {
        return tap(User::factory()->create(['role' => 'admin']), fn ($u) => $this->actingAs($u));
    }

    /** @test */
    public function urun_kaydedilince_ve_guncellenince_katalog_webhooku_gonderilir(): void
    {
        Http::fake();
        config(['services.ecommerce.webhook_url' => 'https://eticaret.example.com/wh']);
        $user = $this->loginAdmin();

        $payload = [
            'name' => 'Katalog Ürün',
            'unit' => 'adet',
            'purchase_price' => 40,
            'sale_price' => 80,
            'stock_quantity' => 5,
            'barcode' => '8690000000111',
        ];

        $this->post('/urunler', $payload)->assertRedirect();
        $this->assertDatabaseHas('webhook_deliveries', ['event' => 'product.upserted']);

        $product = Product::where('name', 'Katalog Ürün')->first();
        $this->put("/urunler/{$product->id}", $payload + ['sale_price' => 95])->assertRedirect();
        $this->assertSame(2, \App\Models\WebhookDelivery::where('event', 'product.upserted')->count());
    }

    /** @test */
    public function urun_silince_product_deleted_webhooku_gonderilir(): void
    {
        Http::fake();
        config(['services.ecommerce.webhook_url' => 'https://eticaret.example.com/wh']);
        $user = $this->loginAdmin();

        $product = Product::create([
            'company_id' => $user->company_id, 'name' => 'Silinecek Ürün', 'unit' => 'adet',
            'purchase_price' => 10, 'sale_price' => 20, 'stock_quantity' => 1,
        ]);

        $this->delete("/urunler/{$product->id}")->assertRedirect();
        $this->assertDatabaseHas('webhook_deliveries', ['event' => 'product.deleted']);
    }

    /** @test */
    public function ayni_external_ref_ile_ikinci_siparis_yeni_siparis_acmaz(): void
    {
        $user = $this->loginAdmin();

        $contact = Contact::create(['company_id' => $user->company_id, 'type' => 'musteri', 'name' => 'İdempotent Cari']);
        $product = Product::create([
            'company_id' => $user->company_id, 'name' => 'İdempotent Ürün', 'unit' => 'adet',
            'purchase_price' => 50, 'sale_price' => 100, 'stock_quantity' => 10,
        ]);

        $service = app(OrderService::class);
        $data = [
            'contact_id' => $contact->id,
            'external_ref' => 'ETC-IDEM-1',
            'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100]],
        ];

        $order1 = $service->create($user->company_id, $data);
        $order2 = $service->create($user->company_id, $data);

        $this->assertSame($order1->order_no, $order2->order_no);
        $this->assertSame(1, Order::count());
        // Çift rezerv yaşanmadı: 2 adet rezerve kalmalı, 8 müsait
        $this->assertSame(2, (int) $product->fresh()->reserved_quantity);
        $this->assertSame(8, $product->fresh()->availableQuantity());
    }

    /** @test */
    public function api_siparis_ucusunda_idempotent_donus_200_dur(): void
    {
        $user = $this->loginAdmin();
        $token = $user->createToken('test', ['ecommerce'])->plainTextToken;

        $contact = Contact::create(['company_id' => $user->company_id, 'type' => 'musteri', 'name' => 'API İdempotent']);
        $product = Product::create([
            'company_id' => $user->company_id, 'name' => 'API Ürün', 'unit' => 'adet',
            'purchase_price' => 10, 'sale_price' => 30, 'stock_quantity' => 10,
        ]);

        $body = [
            'contact_id' => $contact->id,
            'external_ref' => 'ETC-API-IDEM',
            'tax_rate' => 10,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        $headers = ['Authorization' => "Bearer {$token}"];

        $this->postJson('/api/v1/orders', $body, $headers)->assertStatus(201);
        $ikinci = $this->postJson('/api/v1/orders', $body, $headers);

        $ikinci->assertStatus(200)
            ->assertJsonPath('data.order_no', Order::first()->order_no)
            ->assertJsonFragment(['message' => 'Bu external_ref ile sipariş zaten mevcut — yeni sipariş açılmadı (idempotent).']);
    }

    /** @test */
    public function barkod_ile_stok_sorgulanir_ve_urun_listesinde_gorunur(): void
    {
        $user = $this->loginAdmin();
        $token = $user->createToken('test', ['ecommerce'])->plainTextToken;

        Product::create([
            'company_id' => $user->company_id, 'name' => 'Barkodlu Ürün', 'unit' => 'adet',
            'purchase_price' => 5, 'sale_price' => 9, 'stock_quantity' => 42, 'barcode' => '8691111222333',
        ]);

        $headers = ['Authorization' => "Bearer {$token}"];

        // Barkod ile tek ürün sorgusu (POS senaryosu)
        $this->getJson('/api/v1/stock?barcode=8691111222333', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Barkodlu Ürün')
            ->assertJsonPath('data.0.stock_quantity', 42);

        // Katalog yanıtı barkodu taşır
        $this->getJson('/api/v1/products', $headers)
            ->assertOk()
            ->assertJsonFragment(['barcode' => '8691111222333']);
    }

    /** @test */
    public function kargo_etiketi_pdf_indirilir(): void
    {
        $user = $this->loginAdmin();

        $contact = Contact::create(['company_id' => $user->company_id, 'type' => 'musteri', 'name' => 'Etiket Cari']);
        $product = Product::create([
            'company_id' => $user->company_id, 'name' => 'Etiket Ürün', 'unit' => 'adet',
            'purchase_price' => 10, 'sale_price' => 20, 'stock_quantity' => 5,
        ]);

        $order = app(OrderService::class)->create($user->company_id, [
            'contact_id' => $contact->id,
            'external_ref' => 'ETC-LABEL',
            'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 20]],
        ]);
        $order->update(['status' => 'paid', 'tracking_no' => 'ARAS123', 'carrier' => 'Aras Kargo']);

        $response = $this->get("/siparisler/{$order->id}/etiket");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }
}
