<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sadakat puanı: her 10 ₺ ödemede 1 puan kazanılır; checkout'ta 1 puan = 1 ₺
 * harcanır (en fazla ara toplamın yarısı).
 */
class SadakatPuanTest extends TestCase
{
    use RefreshDatabase;

    private function sirketKur(): void
    {
        Company::create(['name' => 'Sadakat Test Şirketi', 'tax_number' => '9876543210']);
    }

    private function cariOl(): Contact
    {
        return Contact::create(['company_id' => 1, 'type' => 'musteri', 'name' => 'Sadakat Cari', 'email' => 'sadakat@ornek.com']);
    }

    private function urunOl(): Product
    {
        return Product::create([
            'company_id' => 1, 'name' => 'Sadakat Test Ürünü', 'unit' => 'adet',
            'purchase_price' => 40, 'sale_price' => 100, 'stock_quantity' => 10,
        ]);
    }

    private function siparisOlustur(string $yontem = 'cspr', bool $puan = false): Order
    {
        $cari = $this->cariOl();
        $urun = $this->urunOl();

        return app(\App\Services\OrderService::class)->create(1, [
            'contact_id' => $cari->id, 'tax_rate' => 20, 'payment_method' => $yontem,
            'puan_kullan' => $puan,
            'items' => [['product_id' => $urun->id, 'quantity' => 2, 'unit_price' => 100]],
        ]);
    }

    /** @test */
    public function odemede_sadakat_puani_kazanilir(): void
    {
        $this->sirketKur();
        $cari = $this->cariOl();
        $urun = $this->urunOl();

        $order = app(\App\Services\OrderService::class)->create(1, [
            'contact_id' => $cari->id, 'tax_rate' => 20, 'payment_method' => 'cspr',
            'items' => [['product_id' => $urun->id, 'quantity' => 2, 'unit_price' => 100]],
        ]);

        app(\App\Services\OrderService::class)->markPaid($order, 1);

        // total = 240 ₺ → 24 puan
        $this->assertSame(24, (int) $cari->fresh()->sadakat_puani);
    }

    /** @test */
    public function checkout_ta_puan_harcandiysa_indirim_uygulanir_ve_bakiye_duser(): void
    {
        $this->sirketKur();
        $cari = $this->cariOl();
        $cari->update(['sadakat_puani' => 60]);
        $urun = $this->urunOl();

        // subtotal = 200 ₺ (KDV hariç) · puan 60 < 100 → 60 ₺ indirim
        $order = app(\App\Services\OrderService::class)->create(1, [
            'contact_id' => $cari->id, 'tax_rate' => 20, 'puan_kullan' => true,
            'items' => [['product_id' => $urun->id, 'quantity' => 2, 'unit_price' => 100]],
        ]);

        // subtotal 200 → indirim 60 → net 140 → KDV 28 → total 168
        $this->assertSame(168.0, (float) $order->total);
        $this->assertSame(0, (int) $cari->fresh()->sadakat_puani, 'Harcanan puanlar bakiyeden düşülmeli.');
        $this->assertSame(60, (int) $order->kullanilan_puan);
    }

    /** @test */
    public function puan_yarim_toplami_asamaz(): void
    {
        $this->sirketKur();
        $cari = $this->cariOl();
        $cari->update(['sadakat_puani' => 500]); // 200 ₺ subtotal → cap %50 = 100 ₺
        $urun = $this->urunOl();

        $order = app(\App\Services\OrderService::class)->create(1, [
            'contact_id' => $cari->id, 'tax_rate' => 20, 'puan_kullan' => true,
            'items' => [['product_id' => $urun->id, 'quantity' => 2, 'unit_price' => 100]],
        ]);

        // indirim cap 100 ₺ → net 100 → KDV 20 → total 120
        $this->assertSame(120.0, (float) $order->total);
        $this->assertSame(100, (int) $order->kullanilan_puan);
    }
}
