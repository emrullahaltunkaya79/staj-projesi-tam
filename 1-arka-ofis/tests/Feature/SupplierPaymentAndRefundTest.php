<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierPaymentAndRefundTest extends TestCase
{
    use RefreshDatabase;

    private function setupSale(User $user): array
    {
        $customer = Contact::create([
            'company_id' => $user->company_id,
            'type' => 'musteri',
            'name' => 'İade Test Müşteri',
        ]);
        $product = Product::create([
            'company_id' => $user->company_id,
            'name' => 'İade Test Ürün',
            'unit' => 'adet',
            'purchase_price' => 100,
            'sale_price' => 200,
            'stock_quantity' => 10,
        ]);
        $invoice = app(InvoiceService::class)->createSaleInvoice($user->company_id, [
            'contact_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 200]],
        ]);

        return [$customer, $product, $invoice];
    }

    /** @test */
    public function tedarikci_odemesi_borcu_azaltir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Contact::create([
            'company_id' => $user->company_id,
            'type' => 'tedarikci',
            'name' => 'Ödeme Test Tedarikçi',
            'balance' => -1000, // 1000 ₺ borcumuz var
        ]);

        $this->post('/tedarikci-odemeleri', [
            'contact_id' => $supplier->id,
            'amount' => 400,
            'payment_type' => 'havale',
            'description' => 'Kısmi ödeme',
        ])->assertRedirect('/cariler/'.$supplier->id);

        // Borç: -1000 + 400 = -600
        $this->assertEquals(-600.0, (float) $supplier->fresh()->balance);
        $this->assertDatabaseHas('payments', [
            'contact_id' => $supplier->id,
            'amount' => 400,
            'direction' => 'odeme',
        ]);
    }

    /** @test */
    public function tedarikci_odemesi_borcu_asamaz(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Contact::create([
            'company_id' => $user->company_id,
            'type' => 'tedarikci',
            'name' => 'Aşım Test Tedarikçi',
            'balance' => -500,
        ]);

        $this->post('/tedarikci-odemeleri', [
            'contact_id' => $supplier->id,
            'amount' => 800,
            'payment_type' => 'nakit',
        ])->assertSessionHasErrors('amount');

        $this->assertEquals(-500.0, (float) $supplier->fresh()->balance);
        $this->assertSame(0, $supplier->payments()->count());
    }

    /** @test */
    public function musteriye_tedarikci_odemesi_yapilamaz(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Contact::create([
            'company_id' => $user->company_id,
            'type' => 'musteri',
            'name' => 'Yanlış Hedef',
            'balance' => -100,
        ]);

        $this->post('/tedarikci-odemeleri', [
            'contact_id' => $customer->id,
            'amount' => 50,
            'payment_type' => 'nakit',
        ])->assertSessionHasErrors('contact_id');
    }

    /** @test */
    public function iade_faturasi_stok_geri_yukler_ve_bakiye_dusurur(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        [$customer, $product, $invoice] = $this->setupSale($user);
        // Satış sonrası: stok 7, bakiye 600

        $this->assertSame(7, $product->fresh()->stock_quantity);

        $this->post("/faturalar/{$invoice->id}/iade", [
            'quantities' => [$product->id => 3],
        ])->assertRedirect();

        $refund = $invoice->fresh()->refunds->first();
        $this->assertNotNull($refund);
        $this->assertSame('IF-000002', $refund->invoice_no); // SF-000001'den sonra
        $this->assertSame('iade', $refund->type);

        // Stok geri yüklendi: 7 + 3 = 10, bakiye 600 - 600 = 0
        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertEquals(0.0, (float) $customer->fresh()->balance);

        // İade hareketi kaydedildi
        $this->assertDatabaseHas('stock_movements', [
            'type' => 'iade',
            'direction' => 'giris',
            'quantity' => 3,
        ]);
    }

    /** @test */
    public function kismi_iade_sadece_iade_miktari_kadar_etki_yapar(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        [$customer, $product, $invoice] = $this->setupSale($user);
        // 3 x 200 = 600 (KDV yok): stok 7, bakiye 600

        // Yalnızca 1 adet iade
        $this->post("/faturalar/{$invoice->id}/iade", [
            'quantities' => [$product->id => 1],
        ])->assertRedirect();

        $refund = $invoice->fresh()->refunds->first();
        $this->assertEquals(200.0, (float) $refund->total);          // iade tutarı: 1 x 200
        $this->assertSame(8, $product->fresh()->stock_quantity);     // 7 + 1
        $this->assertEquals(400.0, (float) $customer->fresh()->balance); // 600 - 200

        // Kalan hakkı (2) aşan iade reddedilir
        $this->post("/faturalar/{$invoice->id}/iade", [
            'quantities' => [$product->id => 5],
        ])->assertRedirect()->assertSessionHasErrors('invoice');
    }

    /** @test */
    public function tam_iadeden_sonra_yeni_iade_hakki_kalmaz(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        [, , $invoice] = $this->setupSale($user);

        // Tam iade: 3 adedin tamamı
        $this->post("/faturalar/{$invoice->id}/iade", [
            'quantities' => [$invoice->items->first()->product_id => 3],
        ])->assertRedirect();

        // Aynı faturaya ikinci iade denemesi: hak kalmadı → hata
        $this->post("/faturalar/{$invoice->id}/iade", [
            'quantities' => [$invoice->items->first()->product_id => 1],
        ])->assertRedirect()->assertSessionHasErrors('invoice');
    }

    /** @test */
    public function iade_faturasinin_iadesi_alinmaz(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        [, , $invoice] = $this->setupSale($user);
        $this->post("/faturalar/{$invoice->id}/iade", [
            'quantities' => [$invoice->items->first()->product_id => 3],
        ])->assertRedirect();

        $refund = $invoice->fresh()->refunds->first();

        $this->post("/faturalar/{$refund->id}/iade", [
            'quantities' => [$refund->items->first()->product_id => 1],
        ])->assertRedirect()->assertSessionHasErrors('invoice');
    }
}
