<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ön muhasebe entegrasyonu — ön sipariş backorder akışı:
 * stoksuz ürün (pre_order) siparişe girer, fatura stoğu negatife düşürür,
 * rezerv temizlenir. Diğer ürünlerde mevcut stok koruması değişmedi.
 */
class OnSiparisEntegrasyonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\Company::create(['name' => 'Entegrasyon Test Şirketi', 'tax_number' => '3333333333']);
    }

    private function urun(array $ek = []): Product
    {
        return Product::create(array_merge([
            'company_id' => 1, 'name' => 'Ön Sipariş Ürünü', 'unit' => 'adet',
            'purchase_price' => 50, 'sale_price' => 100, 'stock_quantity' => 0,
            'pre_order' => true,
        ], $ek));
    }

    /** @test */
    public function stoksuz_on_siparis_urunu_siparis_edilip_odenebilir(): void
    {
        $cari = \App\Models\Contact::create(['company_id' => 1, 'type' => 'musteri', 'name' => 'Ön Sipariş Cari', 'email' => 'on@ornek.test']);
        $urun = $this->urun(); // stok: 0, pre_order: true
        $os = app(OrderService::class);

        $siparis = $os->create(1, [
            'contact_id' => $cari->id, 'tax_rate' => 20,
            'items' => [['product_id' => $urun->id, 'quantity' => 2]],
        ]);

        $this->assertSame('pending', $siparis->status);
        $os->markPaid($siparis, 1);

        $siparis = $siparis->fresh();
        $urun = $urun->fresh();
        $this->assertSame('paid', $siparis->status);
        $this->assertNotNull($siparis->invoice_id, 'Ön sipariş ödemesinde fatura kesilmeli.');
        $this->assertSame(-2, (int) $urun->stock_quantity, 'Backorder stoğu negatife düşürür.');
        $this->assertSame(0, (int) $urun->reserved_quantity, 'Rezerv fatura ile temizlenir.');
    }

    /** @test */
    public function on_siparis_kapali_urunde_stok_korunmaya_devam_eder(): void
    {
        $cari = \App\Models\Contact::create(['company_id' => 1, 'type' => 'musteri', 'name' => 'Normal Cari', 'email' => 'normal@ornek.test']);
        $urun = $this->urun(['pre_order' => false, 'name' => 'Normal Ürün']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(OrderService::class)->create(1, [
            'contact_id' => $cari->id, 'tax_rate' => 20,
            'items' => [['product_id' => $urun->id, 'quantity' => 1]],
        ]);
    }

    /** @test */
    public function urun_listesi_on_siparis_bayragini_tasiyan_doner(): void
    {
        $magaza = \App\Models\User::factory()->create(['company_id' => 1]);
        \Laravel\Sanctum\Sanctum::actingAs($magaza, ['ecommerce']);
        $urun = $this->urun();

        $cevap = $this->getJson('/api/v1/products')->assertOk();
        $satir = collect($cevap->json('data'))->firstWhere('id', $urun->id);

        $this->assertNotNull($satir);
        $this->assertTrue((bool) $satir['pre_order'], 'Katalog listesi ön sipariş bayrağını taşımalı.');
    }
}
