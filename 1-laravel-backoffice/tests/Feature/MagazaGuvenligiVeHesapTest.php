<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MagazaGuvenligiVeHesapTest extends TestCase
{
    use RefreshDatabase;

    private function magazaSunucusu(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['ecommerce']);

        return $user;
    }

    private function urun(User $user, array $ek = []): Product
    {
        return Product::create(array_merge([
            'company_id' => $user->company_id,
            'name' => 'Güvenlik Testi Ürünü',
            'unit' => 'adet',
            'purchase_price' => 50,
            'sale_price' => 100,
            'stock_quantity' => 20,
        ], $ek));
    }

    private function musteri(User $magaza): User
    {
        return User::factory()->create(['company_id' => $magaza->company_id, 'role' => 'musteri']);
    }

    /** @test */
    public function siparis_tahmin_edilemez_erisim_anahtari_ve_fatura_bilgisiyle_olusur(): void
    {
        $user = $this->magazaSunucusu();
        $cari = Contact::create(['company_id' => $user->company_id, 'name' => 'Ofis A.Ş.', 'email' => 'satin@ofis.com']);
        $urun = $this->urun($user);

        $cevap = $this->postJson('/api/v1/orders', [
            'contact_id' => $cari->id,
            'tax_rate' => 20,
            'customer_phone' => '05321234567',
            'fatura_tipi' => 'kurumsal',
            'fatura_unvan' => 'Ofis Teknoloji A.Ş.',
            'vergi_dairesi' => 'Kadıköy',
            'vergi_no' => '1234567890',
            'fatura_adresi' => 'Merkez Mah. 1. Sok. No:2, Kadıköy / İstanbul',
            'items' => [['product_id' => $urun->id, 'quantity' => 1]],
        ])->assertCreated();

        $anahtar = $cevap->json('data.access_key');
        $this->assertSame(40, strlen($anahtar));

        $detay = $this->getJson('/api/v1/orders/'.$cevap->json('data.order_no'))->assertOk();
        $this->assertSame($anahtar, $detay->json('data.access_key'));
        $this->assertSame('kurumsal', $detay->json('data.fatura.tipi'));
        $this->assertSame('1234567890', $detay->json('data.fatura.vergi_no'));
        $this->assertSame('05321234567', $detay->json('data.customer_phone'));

        // Model JSON'unda anahtar gizli (panel, loglar)
        $this->assertArrayNotHasKey('access_key', Order::first()->toArray());
    }

    /** @test */
    public function kurumsal_faturada_vergi_bilgileri_zorunludur(): void
    {
        $user = $this->magazaSunucusu();
        $cari = Contact::create(['company_id' => $user->company_id, 'name' => 'Test']);
        $urun = $this->urun($user);

        $this->postJson('/api/v1/orders', [
            'contact_id' => $cari->id,
            'tax_rate' => 20,
            'fatura_tipi' => 'kurumsal',
            'items' => [['product_id' => $urun->id, 'quantity' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors(['fatura_unvan', 'vergi_dairesi', 'vergi_no']);
    }

    /** @test */
    public function musteri_tokeni_magaza_ve_panel_uclarina_erisemez(): void
    {
        $magaza = User::factory()->create();
        Sanctum::actingAs($this->musteri($magaza), ['ecommerce']);

        $this->getJson('/api/v1/orders')->assertForbidden();
        $this->getJson('/api/v1/iletisim')->assertForbidden();
        $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.role', 'musteri');
    }

    /** @test */
    public function kismi_iade_yalnizca_secilen_adetleri_ve_tutari_geri_alir(): void
    {
        $user = $this->magazaSunucusu();
        $cari = Contact::create(['company_id' => $user->company_id, 'name' => 'Kısmi İade', 'email' => 'iade@ornek.com']);
        $a = $this->urun($user, ['name' => 'Ürün A', 'sale_price' => 100]);
        $b = $this->urun($user, ['name' => 'Ürün B', 'sale_price' => 50]);

        $no = $this->postJson('/api/v1/orders', [
            'contact_id' => $cari->id,
            'tax_rate' => 20,
            'items' => [['product_id' => $a->id, 'quantity' => 2], ['product_id' => $b->id, 'quantity' => 1]],
        ])->json('data.order_no');

        $this->postJson("/api/v1/orders/{$no}/pay")->assertOk();
        Order::where('order_no', $no)->first()->update(['status' => 'delivered']);
        $stokA = $a->fresh()->stock_quantity;
        $bakiye = (float) $cari->fresh()->balance;

        $this->postJson("/api/v1/orders/{$no}/return-request", [
            'neden' => 'hasarli',
            'aciklama' => 'Kutusu ezik geldi',
            'kalemler' => [['sira' => 0, 'adet' => 1]],
        ])->assertOk();

        $siparis = Order::where('order_no', $no)->first();
        $this->assertTrue($siparis->iade_talebi['kismi']);
        $this->assertSame('Ürün hasarlı veya kusurlu geldi', $siparis->iade_talebi['neden_etiketi']);
        $this->assertEquals(120.00, (float) $siparis->iade_tutari); // 1 × 100 + %20 KDV

        $this->postJson("/api/v1/orders/{$no}/return-approve")->assertOk();

        $this->assertSame($stokA + 1, $a->fresh()->stock_quantity);
        $this->assertEqualsWithDelta($bakiye - 120, (float) $cari->fresh()->balance, 0.001);
    }

    /** @test */
    public function iade_adedi_siparisi_asamaz(): void
    {
        $user = $this->magazaSunucusu();
        $cari = Contact::create(['company_id' => $user->company_id, 'name' => 'Fazla İade']);
        $urun = $this->urun($user);
        $no = $this->postJson('/api/v1/orders', [
            'contact_id' => $cari->id, 'tax_rate' => 20,
            'items' => [['product_id' => $urun->id, 'quantity' => 1]],
        ])->json('data.order_no');
        $this->postJson("/api/v1/orders/{$no}/pay")->assertOk();
        Order::where('order_no', $no)->first()->update(['status' => 'delivered']);

        $this->postJson("/api/v1/orders/{$no}/return-request", ['kalemler' => [['sira' => 0, 'adet' => 3]]])
            ->assertStatus(422);
    }

    /** @test */
    public function musteri_profil_sifre_ve_adreslerini_yonetir(): void
    {
        $magaza = User::factory()->create();
        $musteri = $this->musteri($magaza);
        $baskasi = $this->musteri($magaza);
        Sanctum::actingAs($musteri, ['ecommerce']);

        $this->putJson('/api/v1/hesap/profil', ['name' => 'Yeni Ad Soyad', 'phone' => '05551112233'])->assertOk();
        $this->assertSame('Yeni Ad Soyad', $musteri->fresh()->name);

        $this->putJson('/api/v1/hesap/sifre', ['mevcut' => 'yanlis-sifre', 'yeni' => 'YeniSifre123', 'yeni_confirmation' => 'YeniSifre123'])
            ->assertStatus(422)->assertJsonValidationErrors('mevcut');

        $adres = $this->postJson('/api/v1/hesap/adresler', [
            'baslik' => 'Ev', 'ad_soyad' => 'Yeni Ad Soyad', 'telefon' => '05551112233',
            'il' => 'İzmir', 'ilce' => 'Bornova', 'acik_adres' => 'Kazımdirik Mah. 300 Sok. No: 5 D: 3',
        ])->assertCreated()->json('data.id');

        $this->getJson('/api/v1/hesap/adresler')->assertOk()->assertJsonCount(1, 'data');

        // Başka müşteri bu adresi silemez
        Sanctum::actingAs($baskasi, ['ecommerce']);
        $this->deleteJson("/api/v1/hesap/adresler/{$adres}")->assertNotFound();
    }

    /** @test */
    public function favori_ve_sepet_hesapta_saklanir(): void
    {
        $magaza = User::factory()->create();
        Sanctum::actingAs($this->musteri($magaza), ['ecommerce']);

        $this->putJson('/api/v1/hesap/durum', ['favoriler' => [['id' => 5, 'ad' => 'Webcam']]])->assertOk();
        $this->putJson('/api/v1/hesap/durum', ['sepet' => [['id' => 6, 'ad' => 'Defter', 'adet' => 2]]])->assertOk();

        $durum = $this->getJson('/api/v1/hesap/durum')->assertOk();
        $this->assertSame(5, $durum->json('data.favoriler.0.id'));
        $this->assertSame(2, $durum->json('data.sepet.0.adet'));
    }

    /** @test */
    public function soru_cevaplanana_kadar_magazada_gorunmez(): void
    {
        Mail::fake();
        $user = $this->magazaSunucusu();
        $urun = $this->urun($user);

        $this->postJson("/api/v1/products/{$urun->id}/sorular", [
            'ad' => 'Ayşe Yılmaz', 'email' => 'ayse@ornek.com', 'soru' => 'Bu ürün Mac ile uyumlu mu?',
        ])->assertCreated();

        $this->getJson("/api/v1/products/{$urun->id}/sorular")->assertOk()->assertJsonCount(0, 'data');

        $id = $this->getJson('/api/v1/sorular')->json('data.0.id');
        $this->patchJson("/api/v1/sorular/{$id}/cevap", ['cevap' => 'Evet, macOS ile tam uyumludur.'])->assertOk();

        $liste = $this->getJson("/api/v1/products/{$urun->id}/sorular")->assertOk();
        $this->assertSame('Ayşe Y.', $liste->json('data.0.ad'));
        $this->assertArrayNotHasKey('email', $liste->json('data.0'));
    }

    /** @test */
    public function fiyat_dusunce_alarm_e_postasi_gider(): void
    {
        Mail::fake();
        $user = $this->magazaSunucusu();
        $urun = $this->urun($user, ['sale_price' => 100]);

        $this->postJson('/api/v1/price-alerts', ['email' => 'takip@ornek.com', 'product_id' => $urun->id])->assertOk();
        $this->assertDatabaseHas('price_alerts', ['product_id' => $urun->id, 'takip_fiyati' => 120.00]);

        $this->artisan('fiyat:alarm-gonder');
        $this->assertDatabaseHas('price_alerts', ['product_id' => $urun->id, 'notified_at' => null]);

        $urun->update(['discount_percent' => 10]);
        $this->artisan('fiyat:alarm-gonder');

        $this->assertDatabaseMissing('price_alerts', ['product_id' => $urun->id, 'notified_at' => null]);
    }

    /** @test */
    public function magaza_musterisi_on_muhasebe_paneline_giremez(): void
    {
        $magaza = User::factory()->create();
        $musteri = User::factory()->create([
            'company_id' => $magaza->company_id,
            'role' => 'musteri',
            'password' => bcrypt('MusteriSifre1'),
        ]);

        $this->post('/giris', ['email' => $musteri->email, 'password' => 'MusteriSifre1'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /** @test */
    public function urun_detayi_gercek_puan_ortalamasini_doner(): void
    {
        $user = $this->magazaSunucusu();
        $urun = $this->urun($user);

        $this->getJson("/api/v1/products/{$urun->id}")->assertOk()
            ->assertJsonPath('data.rating_avg', null)
            ->assertJsonPath('data.review_count', 0);
    }
}
