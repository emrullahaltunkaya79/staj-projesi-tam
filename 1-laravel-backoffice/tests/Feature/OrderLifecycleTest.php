<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    private function seedOrderData(User $user): array
    {
        $contact = Contact::create(['company_id' => $user->company_id, 'type' => 'musteri', 'name' => 'E-Ticaret Cari']);
        $product = Product::create([
            'company_id' => $user->company_id, 'name' => 'E-Ticaret Ürün', 'unit' => 'adet',
            'purchase_price' => 50, 'sale_price' => 200, 'stock_quantity' => 10,
        ]);

        return [$contact, $product];
    }

    /** @test */
    public function siparis_stogu_rezerve_eder_fiziksel_stok_degismez(): void
    {
        $user = $this->user();
        [$contact, $product] = $this->seedOrderData($user);
        $service = app(OrderService::class);

        $order = $service->create($user->company_id, [
            'contact_id' => $contact->id,
            'tax_rate' => 20,
            'items' => [['product_id' => $product->id, 'quantity' => 4, 'unit_price' => 200]],
        ]);

        $this->assertSame('ORD-000001', $order->order_no);
        $this->assertSame('pending', $order->status);
        $this->assertSame(10, $product->fresh()->stock_quantity);            // fiziksel değişmedi
        $this->assertSame(4, $product->fresh()->reserved_quantity);          // 4 rezerve
        $this->assertEquals(0.0, (float) $contact->fresh()->balance);        // bakiye değişmedi
    }

    /** @test */
    public function musait_stok_yetersizse_rezerv_reddedilir(): void
    {
        $user = $this->user();
        [$contact, $product] = $this->seedOrderData($user);
        $service = app(OrderService::class);

        // İlk sipariş 6 adet rezerve etti → müsait 4 kaldı
        $service->create($user->company_id, [
            'contact_id' => $contact->id, 'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 6, 'unit_price' => 200]],
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->create($user->company_id, [
            'contact_id' => $contact->id, 'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 200]], // 5 > 4 müsait
        ]);
    }

    /** @test */
    public function odeme_alininca_rezerv_kalici_stoğa_doner_ve_fatura_kesilir(): void
    {
        $user = $this->user();
        [$contact, $product] = $this->seedOrderData($user);
        $service = app(OrderService::class);

        $order = $service->create($user->company_id, [
            'contact_id' => $contact->id, 'tax_rate' => 20,
            'items' => [['product_id' => $product->id, 'quantity' => 4, 'unit_price' => 200]],
        ]);

        $order = $service->markPaid($order, $user->company_id);

        // Fatura kesildi, stok düştü, rezerv sıfırlandı
        $this->assertNotNull($order->invoice_id);
        $this->assertSame('paid', $order->status);
        $this->assertSame(6, $product->fresh()->stock_quantity);          // 10 - 4
        $this->assertSame(0, $product->fresh()->reserved_quantity);
        $this->assertEquals(960.0, (float) $contact->fresh()->balance);   // 800 + %20
    }

    /** @test */
    public function iptal_rezervi_serbest_birakir_fiziksel_stok_degismez(): void
    {
        $user = $this->user();
        [$contact, $product] = $this->seedOrderData($user);
        $service = app(OrderService::class);

        $order = $service->create($user->company_id, [
            'contact_id' => $contact->id, 'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 200]],
        ]);

        $service->cancel($order, $user->company_id);

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(10, $product->fresh()->stock_quantity);   // rezerv geri alındı
        $this->assertSame(0, $product->fresh()->reserved_quantity);
        $this->assertEquals(0.0, (float) $contact->fresh()->balance);
    }

    /** @test */
    public function zaman_asimi_otomatik_iptal_eder(): void
    {
        $user = $this->user();
        [$contact, $product] = $this->seedOrderData($user);
        $service = app(OrderService::class);

        $order = $service->create($user->company_id, [
            'contact_id' => $contact->id, 'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 200]],
        ]);

        // expires_at'ı geçmişe al
        $order->update(['expires_at' => now()->subMinutes(31)]);

        $service->releaseExpiredReservations($user->company_id);

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertSame(0, $product->fresh()->reserved_quantity);
    }

    /** @test */
    public function api_token_ile_siparis_olusturulabilir(): void
    {
        $user = $this->user();
        \Laravel\Sanctum\Sanctum::actingAs($user, ['ecommerce']);

        [$contact, $product] = $this->seedOrderData($user);

        $this->postJson('/api/v1/orders', [
            'contact_id' => $contact->id,
            'tax_rate' => 20,
            'external_ref' => 'ETC-1001',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 200]],
        ])->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.order_no', 'ORD-000001');

        // Rezerve model: fiziksel stok değişmez, rezerv artar
        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertSame(2, $product->fresh()->reserved_quantity);
        $this->assertSame(2, $product->fresh()->reserved_quantity);
    }
}
