<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Laravel arka ofis paneli (127.0.0.1:8000/panel) ↔ mağaza entegrasyonu:
 * ödeme yöntemi görünümü, kapıda kargolama, hazırlığa alma, iade onay/reddet.
 */
class PanelEntegrasyonTest extends TestCase
{
    use RefreshDatabase;

    private function panel(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function siparis(User $user, string $yontem = 'cspr'): Order
    {
        $cari = Contact::create(['company_id' => $user->company_id, 'name' => 'Panel Entegrasyon Müşterisi', 'email' => 'panel@ornek.com']);
        $urun = Product::create([
            'company_id' => $user->company_id,
            'name' => 'Panel Test Ürünü',
            'unit' => 'adet',
            'purchase_price' => 40,
            'sale_price' => 100,
            'stock_quantity' => 10,
        ]);

        return app(OrderService::class)->create($user->company_id, [
            'contact_id' => $cari->id,
            'tax_rate' => 20,
            'customer_email' => 'panel@ornek.com',
            'payment_method' => $yontem,
            'items' => [['product_id' => $urun->id, 'quantity' => 2]],
        ]);
    }

    /** @test */
    public function siparis_listesi_odeme_yontemi_ve_iade_rozetini_gosterir(): void
    {
        $user = $this->panel();
        $this->siparis($user, 'kapida');

        $cevap = $this->get('/siparisler');
        $cevap->assertOk()
            ->assertSee('Kapıda')
            ->assertSee('Ödeme', false);
    }

    /** @test */
    public function kapida_siparisi_beklerken_kargolanir_fatura_sevk_ile_kesilir(): void
    {
        Mail::fake();
        $user = $this->panel();
        $order = $this->siparis($user, 'kapida');
        $this->assertSame('pending', $order->status);

        $this->followingRedirects()->post("/siparisler/{$order->id}/kargo", [
            'carrier' => 'Yurtiçi Kargo',
            'tracking_no' => 'PN987654321',
        ])->assertOk();

        $order = $order->fresh();
        $this->assertSame('shipped', $order->status);
        $this->assertNotNull($order->invoice_id, 'Kapıda siparişte fatura sevk anında kesilmelidir.');
        $this->assertSame('PN987654321', $order->tracking_no);
    }

    /** @test */
    public function odenmis_siparis_hazirliga_alinir(): void
    {
        Mail::fake();
        $user = $this->panel();
        $order = $this->siparis($user, 'havale');
        app(OrderService::class)->markPaid($order, $user->company_id);

        $this->followingRedirects()->post("/siparisler/{$order->id}/hazirla")->assertOk();

        $this->assertSame('preparing', $order->fresh()->status);
    }

    /** @test */
    public function bekleyen_siparis_hazirliga_alinamaz(): void
    {
        Mail::fake();
        $user = $this->panel();
        $order = $this->siparis($user, 'cspr');

        $this->followingRedirects()->post("/siparisler/{$order->id}/hazirla");

        $this->assertSame('pending', $order->fresh()->status);
    }

    /** @test */
    public function panel_iade_talebini_onaylar_stok_geri_alinir(): void
    {
        Mail::fake();
        $user = $this->panel();
        $order = $this->siparis($user, 'cspr');
        $service = app(OrderService::class);
        $order = $service->markPaid($order, $user->company_id);

        $urunId = $order->items[0]['product_id'];
        $stokOncesi = Product::find($urunId)->stock_quantity;

        $order->update(['status' => 'delivered', 'delivered_at' => now()]);
        $order = $service->requestReturn($order, $user->company_id, [
            'neden' => 'hasarli',
            'aciklama' => 'Kutu yırtık geldi',
            'kalemler' => [['sira' => 0, 'adet' => 1]], // kısmi: 1 adet
        ]);

        $this->followingRedirects()->post("/siparisler/{$order->id}/iade-onay")->assertOk();

        $order = $order->fresh();
        $this->assertSame('returned', $order->status);
        $this->assertSame($stokOncesi + 1, Product::find($urunId)->stock_quantity, 'İade edilen 1 adet stoğa geri eklenmelidir.');
    }

    /** @test */
    public function panel_iade_talebini_reddeder_siparis_teslime_doner(): void
    {
        Mail::fake();
        $user = $this->panel();
        $order = $this->siparis($user, 'cspr');
        $service = app(OrderService::class);
        $order = $service->markPaid($order, $user->company_id);
        $order->update(['status' => 'delivered', 'delivered_at' => now()]);
        $order = $service->requestReturn($order, $user->company_id, ['neden' => 'fikir_degisti']);

        $this->followingRedirects()->post("/siparisler/{$order->id}/iade-reddet", [
            'not' => 'Ürün hasar görmeden iade edilemedi',
        ])->assertOk();

        $order = $order->fresh();
        $this->assertSame('delivered', $order->status);
        $this->assertTrue($order->iade_talebi['reddedildi']);
        $this->assertSame('Ürün hasar görmeden iade edilemedi', $order->iade_talebi['red_notu']);
    }

    /** @test */
    public function detay_sayfasi_odeme_yontemini_ve_kosullu_butonlari_gosterir(): void
    {
        Mail::fake();
        $user = $this->panel();
        $kapida = $this->siparis($user, 'kapida');
        $havale = $this->siparis($user, 'havale');

        // Kapıda: CSPR düğmesi YOK, kargo formu VAR (pending'de kargolanabilir), "tahsilat kapıda" notu VAR
        $this->get("/siparisler/{$kapida->id}")
            ->assertOk()
            ->assertSee('Kapıda Ödeme')
            ->assertSee('tahsilat kapıda')
            ->assertDontSee('CSPR ile Öde')
            ->assertSee('Kargola');

        // Havale: "Havaleyi Onayla" etiketi, CSPR düğmesi YOK
        $this->get("/siparisler/{$havale->id}")
            ->assertOk()
            ->assertSee('Havaleyi Onayla')
            ->assertDontSee('CSPR ile Öde');

        // CSPR: düğme VAR
        $cspr = $this->siparis($user, 'cspr');
        $this->get("/siparisler/{$cspr->id}")->assertOk()->assertSee('CSPR ile Öde');
    }
}
