<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Kartla online ödeme (3D Secure) akışı — sandbox simülasyonu üzerinden uçtan uca:
 * sipariş oluşturma → 3DS başlatma → form/OTP onayı → markPaid + fatura + mağaza dönüşü.
 */
class KartOdemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\Company::create(['name' => 'Kart Test Şirketi', 'tax_number' => '4444444444']);
        config(['services.iyzico.key' => null, 'services.iyzico.secret' => null]);
    }

    private function magaza(): User
    {
        $u = User::factory()->create(['company_id' => 1]);
        \Laravel\Sanctum\Sanctum::actingAs($u, ['ecommerce']);

        return $u;
    }

    private function siparis(): \App\Models\Order
    {
        $cari = Contact::create(['company_id' => 1, 'type' => 'musteri', 'name' => 'Kart Cari', 'email' => 'kart@ornek.test']);
        $urun = Product::create([
            'company_id' => 1, 'name' => 'Kart Ürün', 'unit' => 'adet',
            'purchase_price' => 50, 'sale_price' => 100, 'stock_quantity' => 10,
        ]);

        return app(OrderService::class)->create(1, [
            'contact_id' => $cari->id, 'tax_rate' => 20, 'payment_method' => 'kart',
            'items' => [['product_id' => $urun->id, 'quantity' => 2]],
        ]);
    }

    /** @test */
    public function kart_odemesi_baslatilir_ve_form_sayfasi_acilir(): void
    {
        $this->magaza();
        $siparis = $this->siparis();

        $cevap = $this->postJson("/api/v1/orders/{$siparis->order_no}/odeme-baslat")->assertOk();

        $url = $cevap->json('data.odeme_url');
        $token = $cevap->json('data.token');
        $this->assertStringContainsString('/odeme/3ds/', $url);
        $this->assertSame($token, $siparis->fresh()->odeme_token);

        // PSP/sandbox form sayfası açılır ve tutarı gösterir
        $form = $this->get("/odeme/3ds/{$token}");
        $form->assertOk();
        $form->assertSee('3D Secure Doğrulama');
        $form->assertSee('240,00 ₺'); // 200 net + %20 KDV
    }

    /** @test */
    public function yanlis_otp_formu_reddeder_ve_deneme_sayisini_arttirir(): void
    {
        $this->magaza();
        $siparis = $this->siparis();
        $token = $this->postJson("/api/v1/orders/{$siparis->order_no}/odeme-baslat")->json('data.token');

        $yanlis = $this->post("/odeme/3ds/{$token}", [
            'kart_no' => '5528790000000008',
            'son_kullanma' => '12/28',
            'cvv' => '123',
            'otp' => '000000',
        ]);

        $yanlis->assertRedirect();
        $yanlis->assertSessionHas('hata', '3D Secure onay kodu hatalı veya süresi geçti.');
        $this->assertSame('pending', $siparis->fresh()->status);
    }

    /** @test */
    public function dogru_otp_odemeyi_tamamlar_fatura_keser_ve_magazaya_doner(): void
    {
        $this->magaza();
        $siparis = $this->siparis();
        $token = $this->postJson("/api/v1/orders/{$siparis->order_no}/odeme-baslat")->json('data.token');

        $kod = Cache::get("3ds-otp:{$token}");
        $this->assertMatchesRegularExpression('/^\d{6}$/', $kod);

        $tamam = $this->post("/odeme/3ds/{$token}", [
            'kart_no' => '5528790000000008',
            'son_kullanma' => '12/28',
            'cvv' => '123',
            'otp' => $kod,
        ]);

        $tamam->assertRedirect();
        $hedef = $tamam->headers->get('Location');
        $this->assertStringContainsString("/siparis/{$siparis->order_no}", $hedef, 'Onay sonrası mağaza sipariş sayfasına dönülmeli.');
        $this->assertStringContainsString('odendi=1', $hedef);

        $siparis = $siparis->fresh();
        $this->assertSame('paid', $siparis->status);
        $this->assertNotNull($siparis->invoice_id, '3DS onayında fatura kesilmeli.');

        // kod tek kullanımlık + sipariş ödendi — ikinci gönderim ödeme oluşturamaz
        $tekrar = $this->post("/odeme/3ds/{$token}", [
            'kart_no' => '5528790000000008', 'son_kullanma' => '12/28', 'cvv' => '123', 'otp' => $kod,
        ]);
        $this->assertSame(404, $tekrar->status(), 'Ödenmiş sipariş için form bulunamaz.');
        $this->assertSame($siparis->invoice_id, $siparis->fresh()->invoice_id, 'Çift fatura kesilemez.');
    }

    /** @test */
    public function kart_olmayan_siparis_icin_baslatma_reddedilir(): void
    {
        $this->magaza();
        $cari = Contact::create(['company_id' => 1, 'type' => 'musteri', 'name' => 'C', 'email' => 'c@ornek.test']);
        $urun = Product::create(['company_id' => 1, 'name' => 'U', 'unit' => 'adet', 'purchase_price' => 10, 'sale_price' => 20, 'stock_quantity' => 5]);
        $havale = app(OrderService::class)->create(1, [
            'contact_id' => $cari->id, 'tax_rate' => 20, 'payment_method' => 'havale',
            'items' => [['product_id' => $urun->id, 'quantity' => 1]],
        ]);

        $this->postJson("/api/v1/orders/{$havale->order_no}/odeme-baslat")->assertStatus(422);
    }
}
