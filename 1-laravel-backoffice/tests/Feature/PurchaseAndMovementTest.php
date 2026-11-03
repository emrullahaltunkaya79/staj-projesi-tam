<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseAndMovementTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithSession(): array
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return [$user, $user->company_id];
    }

    private function createContact(int $companyId, string $type, string $name): Contact
    {
        return Contact::create([
            'company_id' => $companyId,
            'type' => $type,
            'name' => $name,
        ]);
    }

    private function createProduct(int $companyId, int $stock = 10): Product
    {
        return Product::create([
            'company_id' => $companyId,
            'name' => 'Hareket Test Ürünü',
            'unit' => 'adet',
            'purchase_price' => 100,
            'sale_price' => 150,
            'stock_quantity' => $stock,
        ]);
    }

    /** @test */
    public function urun_acilis_stogu_hareket_olusturur(): void
    {
        [$user, $companyId] = $this->createUserWithSession();
        $product = $this->createProduct($companyId, 25);

        $this->assertSame(1, StockMovement::where('product_id', $product->id)->count());
        $movement = StockMovement::first();
        $this->assertSame('acilis', $movement->type);
        $this->assertSame('giris', $movement->direction);
        $this->assertEquals(25, (float) $movement->quantity);
    }

    /** @test */
    public function manuel_stok_degisikligi_duzeltme_hareketi_olusturur(): void
    {
        [$user, $companyId] = $this->createUserWithSession();
        $product = $this->createProduct($companyId, 10);

        $product->update(['stock_quantity' => 7]);

        $movement = StockMovement::where('type', 'duzeltme')->first();
        $this->assertNotNull($movement);
        $this->assertSame('cikis', $movement->direction);
        $this->assertEquals(3, (float) $movement->quantity);
        $this->assertEquals(7, (float) $movement->stock_after);
    }

    /** @test */
    public function satis_faturasi_stok_cikis_hareketi_olusturur(): void
    {
        [$user, $companyId] = $this->createUserWithSession();
        $contact = $this->createContact($companyId, 'musteri', 'Satış Cari');
        $product = $this->createProduct($companyId, 10);

        app(InvoiceService::class)->createSaleInvoice($companyId, [
            'contact_id' => $contact->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 20,
            'items' => [['product_id' => $product->id, 'quantity' => 4, 'unit_price' => 150]],
        ]);

        $movement = StockMovement::where('type', 'satis')->first();
        $this->assertNotNull($movement);
        $this->assertSame('cikis', $movement->direction);
        $this->assertEquals(4, (float) $movement->quantity);
        $this->assertEquals(6, (float) $movement->stock_after);
        $this->assertNotNull($movement->invoice_id);
    }

    /** @test */
    public function alis_faturasi_stogu_artirir_ve_bakiyeyi_borca_cevirir(): void
    {
        [$user, $companyId] = $this->createUserWithSession();
        $supplier = $this->createContact($companyId, 'tedarikci', 'Tedarikçi A.Ş.');
        $product = $this->createProduct($companyId, 10);

        $invoice = app(InvoiceService::class)->createPurchaseInvoice($companyId, [
            'contact_id' => $supplier->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 20,
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100]],
        ]);

        // Stok arttı: 10 + 5 = 15
        $this->assertSame(15, $product->fresh()->stock_quantity);

        // 5 x 100 = 500 + %20 KDV = 600 tedarikçiye borç (negatif bakiye)
        $this->assertEquals(-600.0, (float) $supplier->fresh()->balance);

        // AF- önekli numara ve giriş hareketi
        $this->assertSame('AF-000001', $invoice->invoice_no);
        $movement = StockMovement::where('type', 'alis')->first();
        $this->assertSame('giris', $movement->direction);
        $this->assertEquals(15, (float) $movement->stock_after);
    }

    /** @test */
    public function alis_faturasi_musteriye_kesilemez(): void
    {
        [$user, $companyId] = $this->createUserWithSession();
        $customer = $this->createContact($companyId, 'musteri', 'Müşteri A.Ş.');
        $product = $this->createProduct($companyId, 10);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(InvoiceService::class)->createPurchaseInvoice($companyId, [
            'contact_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 20,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
        ]);
    }

    /** @test */
    public function raporlar_sayfasi_acilir_ve_veri_icerir(): void
    {
        [$user, $companyId] = $this->createUserWithSession();
        $contact = $this->createContact($companyId, 'musteri', 'Rapor Cari');
        $product = $this->createProduct($companyId, 10);

        app(InvoiceService::class)->createSaleInvoice($companyId, [
            'contact_id' => $contact->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 20,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 150]],
        ]);

        $this->get('/raporlar')
            ->assertOk()
            ->assertSee('Toplam Satış')
            ->assertSee('En Çok Satılan Ürünler')
            ->assertSee('Brüt Kâr');

        $this->get('/stok-hareketleri')
            ->assertOk()
            ->assertSee('Hareket Test Ürünü')
            ->assertSee('Satış');
    }
}
