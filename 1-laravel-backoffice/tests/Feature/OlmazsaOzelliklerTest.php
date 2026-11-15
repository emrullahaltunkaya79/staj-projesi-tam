<?php

namespace Tests\Feature;

use App\Mail\AbandonedCartMail;
use App\Mail\BirthdayCouponMail;
use App\Models\Coupon;
use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Olmazsa olmaz paketi (Batch 2): terk edilmiş sepet hatırlatması,
 * iade kargo kodu, referans programı ve doğum günü kuponu.
 */
class OlmazsaOzelliklerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\Company::create(['name' => 'Olmazsa Test Şirketi', 'tax_number' => '2222222222']);
    }

    private function magazaSunucusu(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['ecommerce']);

        return $user;
    }

    private function musteri(array $ek = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => 'musteri',
            'email_verified_at' => now(),
        ], $ek));
        Sanctum::actingAs($user);

        return $user;
    }

    private function magazaSunucusuSade(): User
    {
        return User::factory()->create();
    }

    private function cari(User $magaza, array $ek = []): Contact
    {
        return Contact::create(array_merge([
            'company_id' => $magaza->company_id,
            'type' => 'musteri',
            'name' => 'Test Cari',
            'email' => 'test@musteri.test',
        ], $ek));
    }

    private function urun(User $magaza): Product
    {
        return Product::create([
            'company_id' => $magaza->company_id, 'name' => 'Paket Ürün', 'unit' => 'adet',
            'purchase_price' => 50, 'sale_price' => 100, 'stock_quantity' => 20,
        ]);
    }

    /** @test */
    public function terk_edilmis_sepet_hatirlatilir_ve_gunluk_bir_seferle_sinirlidir(): void
    {
        $musteri = $this->musteri();

        DB::table('customer_states')->insert([
            'user_id' => $musteri->id,
            'favoriler' => '[]',
            'sepet' => json_encode([['id' => 7, 'ad' => 'Kablosuz Mouse', 'adet' => 2, 'fiyat' => 250]], JSON_UNESCAPED_UNICODE),
            'updated_at' => now()->subHours(3),
            'created_at' => now()->subHours(3),
        ]);

        Mail::fake();
        $this->artisan('gonder:sepet-hatirlatma')->assertSuccessful();

        Mail::assertSent(AbandonedCartMail::class, 1);
        Mail::assertSent(AbandonedCartMail::class, fn (AbandonedCartMail $m) => $m->ogeler[0]['ad'] === 'Kablosuz Mouse');
        $this->assertNotNull(
            DB::table('customer_states')->where('user_id', $musteri->id)->value('sepet_hatirlatma'),
            'Hatırlatma sonrası damga atılmalı.'
        );

        // Aynı gün ikinci koşum → tekrar gönderilmez
        Mail::fake();
        $this->artisan('gonder:sepet-hatirlatma')->assertSuccessful();
        Mail::assertNotSent(AbandonedCartMail::class);
    }

    /** @test */
    public function iade_onayinda_musteriye_kargo_kodu_uretilir(): void
    {
        $magaza = $this->magazaSunucusu();
        $cari = $this->cari($magaza);
        $urun = $this->urun($magaza);
        $os = app(OrderService::class);

        $siparis = $os->create($magaza->company_id, [
            'contact_id' => $cari->id, 'tax_rate' => 20,
            'items' => [['product_id' => $urun->id, 'quantity' => 1]],
        ]);
        $os->markPaid($siparis, $magaza->company_id);
        $os->updateShipping($siparis->fresh(), ['status' => 'delivered', 'carrier' => 'Yurtici Kargo', 'tracking_no' => 'KY1'], $magaza->company_id);
        $os->requestReturn($siparis->fresh(), $magaza->company_id, [
            'neden' => 'fikir_degisti',
            'kalemler' => [['sira' => 0, 'adet' => 1]],
        ]);

        $this->assertNull($siparis->fresh()->iade_talebi['kargo_kodu'] ?? null);

        $os->approveReturn($siparis->fresh(), $magaza->company_id);

        $iade = $siparis->fresh()->iade_talebi;
        $this->assertMatchesRegularExpression('/^IADE-[A-F0-9]{6}$/', $iade['kargo_kodu'], 'Onayda IADE-XXXXXX biçiminde kod üretilmeli.');
    }

    /** @test */
    public function referans_kodu_durumda_uretilir_ve_sabit_kalir(): void
    {
        $musteri = $this->musteri();
        Contact::create(['company_id' => $musteri->company_id, 'type' => 'musteri', 'name' => 'Test Cari', 'email' => $musteri->email]);

        $birinci = $this->getJson('/api/v1/hesap/durum')->assertOk()->json('data.referans_kodu');
        $ikinci = $this->getJson('/api/v1/hesap/durum')->assertOk()->json('data.referans_kodu');

        $this->assertMatchesRegularExpression('/^HB[A-F0-9]{6}$/', $birinci);
        $this->assertSame($birinci, $ikinci, 'Referans kodu sonraki çağrılarda değişmemeli.');
    }

    /** @test */
    public function referans_kodu_dogrulanir_ve_kendi_kodunu_ayarlar(): void
    {
        $magaza = $this->magazaSunucusu();
        $davetEden = $this->cari($magaza, ['email' => 'davet@musteri.test', 'referans_kodu' => 'HBABCDEF']);

        $ok = $this->getJson('/api/v1/referans/HBABCDEF?eposta=baska@musteri.test')->assertOk()->json('data');
        $this->assertTrue($ok['gecerli']);
        $this->assertFalse($ok['kendi_kodu']);

        $kendi = $this->getJson('/api/v1/referans/HBABCDEF?eposta=davet@musteri.test')->assertOk()->json('data');
        $this->assertTrue($kendi['gecerli']);
        $this->assertTrue($kendi['kendi_kodu'], 'Davet eden kendi kodunu indirim için kullanamamalı.');

        $yok = $this->getJson('/api/v1/referans/HBZZZZZZ')->assertOk()->json('data');
        $this->assertFalse($yok['gecerli']);
        $this->assertSame($davetEden->fresh()->referans_kodu, 'HBABCDEF');
    }

    /** @test */
    public function referans_bonusu_ilk_odemede_davet_edene_gider_tekrarlanmaz(): void
    {
        $magaza = $this->magazaSunucusu();
        $davetEden = $this->cari($magaza, ['email' => 'davet@musteri.test', 'referans_kodu' => 'HBABCDEF']);
        $alici = $this->cari($magaza, ['email' => 'alici@musteri.test']);
        $urun = $this->urun($magaza);
        $os = app(OrderService::class);

        $birinci = $os->create($magaza->company_id, [
            'contact_id' => $alici->id, 'tax_rate' => 20,
            'notes' => 'Referans: HBABCDEF (-50 TL)',
            'items' => [['product_id' => $urun->id, 'quantity' => 2]],
        ]);
        $os->markPaid($birinci, $magaza->company_id);

        $this->assertSame(500, (int) $davetEden->fresh()->sadakat_puani, 'Davet eden ilk ödemede 500 puan kazanmalı.');
        $this->assertTrue((bool) $alici->fresh()->referans_kazandi);

        // İkinci sipariş → ek bonus yok
        $ikinci = $os->create($magaza->company_id, [
            'contact_id' => $alici->id, 'tax_rate' => 20,
            'notes' => 'Referans: HBABCDEF',
            'items' => [['product_id' => $urun->id, 'quantity' => 1]],
        ]);
        $os->markPaid($ikinci, $magaza->company_id);

        $this->assertSame(500, (int) $davetEden->fresh()->sadakat_puani, 'Bonus yalnızca bir kez verilmeli.');
    }

    /** @test */
    public function dogum_gunu_kuponu_uretilir_ve_epostalanir(): void
    {
        $musteri = $this->musteri();
        $cari = Contact::create([
            'company_id' => $musteri->company_id, 'type' => 'musteri', 'name' => 'Test Cari',
            'email' => $musteri->email, 'dogum_tarihi' => now()->toDateString(),
        ]);

        Mail::fake();
        $this->artisan('gonder:dogum-gunu-kuponlari')->assertSuccessful();

        $kod = 'DOGUM-' . now()->format('Y') . '-' . $cari->id;
        $kupon = Coupon::where('code', $kod)->first();
        $this->assertNotNull($kupon, 'Doğum günü kuponu oluşturulmalı.');
        $this->assertSame('percent', $kupon->type);
        $this->assertSame(1, (int) $kupon->usage_limit);
        Mail::assertSent(BirthdayCouponMail::class, 1);

        // İkinci koşum → tekrar kupon üretmez
        Mail::fake();
        $this->artisan('gonder:dogum-gunu-kuponlari')->assertSuccessful();
        Mail::assertNotSent(BirthdayCouponMail::class);
        $this->assertSame(1, Coupon::where('code', $kod)->count());
    }

    /** @test */
    public function plus_uyesi_odemesinde_cift_puan_kazanir(): void
    {
        $magaza = $this->magazaSunucusu();
        $cari = $this->cari($magaza, ['plus_bitis' => now()->addYear()]);
        $urun = $this->urun($magaza);
        $os = app(OrderService::class);

        // total = 240 → normal 24 puan, Plus 48 puan
        $siparis = $os->create($magaza->company_id, [
            'contact_id' => $cari->id, 'tax_rate' => 20,
            'items' => [['product_id' => $urun->id, 'quantity' => 2]],
        ]);
        $os->markPaid($siparis, $magaza->company_id);

        $this->assertSame(48, (int) $cari->fresh()->sadakat_puani, 'Plus üyede puanlar 2 katı kazanılmalı.');
    }
}
