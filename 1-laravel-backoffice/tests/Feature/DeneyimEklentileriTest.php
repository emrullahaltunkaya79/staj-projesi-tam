<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ReviewVote;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Deneyim eklentileri: varsayılan adres, KVKK hesap silme,
 * fiyat geçmişi ucu ve yorumlarda "En yararlı" sıralaması.
 */
class DeneyimEklentileriTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\Company::create(['name' => 'Deneyim Test Şirketi', 'tax_number' => '1111111111']);
    }

    private function magazaSunucusu(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['ecommerce']);

        return $user;
    }

    private function musteri(User $magaza, array $ek = []): User
    {
        $user = User::factory()->create(array_merge(['company_id' => $magaza->company_id, 'role' => 'musteri'], $ek));
        Sanctum::actingAs($user);

        return $user;
    }

    /** @test */
    public function adres_varsayilan_yapilir_ve_digerleri_bayraksizlasir(): void
    {
        $musteri = $this->musteri($this->magazaSunucusu());

        $a1 = DB::table('customer_addresses')->insertGetId([
            'user_id' => $musteri->id, 'baslik' => 'Ev', 'ad_soyad' => 'Ayşe Yılmaz',
            'telefon' => '05320000001', 'il' => 'İstanbul', 'ilce' => 'Kadıköy',
            'acik_adres' => 'Test mahallesi 1. sokak no 12', 'is_default' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $a2 = DB::table('customer_addresses')->insertGetId([
            'user_id' => $musteri->id, 'baslik' => 'Ofis', 'ad_soyad' => 'Ayşe Yılmaz',
            'telefon' => '05320000002', 'il' => 'İstanbul', 'ilce' => 'Beşiktaş',
            'acik_adres' => 'Test mahallesi 2. sokak no 34', 'is_default' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->postJson("/api/v1/hesap/adresler/{$a2}/varsayilan")->assertOk();

        $this->assertTrue((bool) DB::table('customer_addresses')->where('id', $a2)->value('is_default'));
        $this->assertFalse((bool) DB::table('customer_addresses')->where('id', $a1)->value('is_default'), 'Başka adres varsayılana geçtiğinde eskisinin bayrağı kalkmalı.');
    }

    /** @test */
    public function ilk_adres_otomatik_varsayilan_kaydedilir(): void
    {
        $this->musteri($this->magazaSunucusu());

        $cevap = $this->postJson('/api/v1/hesap/adresler', [
            'baslik' => 'Ev', 'ad_soyad' => 'Ayşe Yılmaz', 'telefon' => '05320000003',
            'il' => 'İzmir', 'ilce' => 'Karşıyaka', 'acik_adres' => 'Deneme mahallesi 3. sokak no 56',
        ])->assertCreated();

        $this->assertTrue((bool) DB::table('customer_addresses')->where('id', $cevap->json('data.id'))->value('is_default'));
    }

    /** @test */
    public function adresler_listesi_varsayilan_once_ve_isaretli_doner(): void
    {
        $musteri = $this->musteri($this->magazaSunucusu());

        DB::table('customer_addresses')->insert([
            ['user_id' => $musteri->id, 'baslik' => 'Ev', 'ad_soyad' => 'A', 'telefon' => '05320000004', 'il' => 'Ankara', 'ilce' => 'Çankaya', 'acik_adres' => 'Birinci sokak no 1', 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $musteri->id, 'baslik' => 'Ofis', 'ad_soyad' => 'A', 'telefon' => '05320000005', 'il' => 'Ankara', 'ilce' => 'Yenimahalle', 'acik_adres' => 'İkinci sokak no 2', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $cevap = $this->getJson('/api/v1/hesap/adresler')->assertOk();
        $liste = $cevap->json('data');

        $this->assertSame('Ofis', $liste[0]['baslik'], 'Varsayılan adres liste başında olmalı.');
        $this->assertTrue((bool) $liste[0]['is_default']);
    }

    /** @test */
    public function hesap_silme_kvkk_anonimlestirme_uygular(): void
    {
        $magaza = $this->magazaSunucusu();
        $cari = Contact::create(['company_id' => $magaza->company_id, 'type' => 'musteri', 'name' => 'Ayşe Yılmaz', 'email' => 'ayse@musteri.test', 'phone' => '05321112233']);
        $musteri = $this->musteri($magaza, ['email' => 'ayse@musteri.test']);

        $urun = Product::create([
            'company_id' => $magaza->company_id, 'name' => 'KVKK Ürün', 'unit' => 'adet',
            'purchase_price' => 50, 'sale_price' => 100, 'stock_quantity' => 5,
        ]);
        $siparis = app(OrderService::class)->create($magaza->company_id, [
            'contact_id' => $cari->id, 'tax_rate' => 20,
            'customer_email' => 'ayse@musteri.test', 'customer_name' => 'Ayşe Yılmaz', 'customer_phone' => '05321112233',
            'shipping_address' => 'Test sokak no 1 İstanbul',
            'items' => [['product_id' => $urun->id, 'quantity' => 1]],
        ]);

        // Fatura — cari faturalı sayılır → anonimleştirme yolu
        DB::table('invoices')->insert([
            'company_id' => $magaza->company_id, 'type' => 'satis', 'contact_id' => $cari->id,
            'invoice_no' => 'FT-KVKK-1', 'issue_date' => now()->toDateString(),
            'subtotal' => 100, 'tax_rate' => 20, 'tax_amount' => 20, 'total' => 120,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('customer_addresses')->insert([
            'user_id' => $musteri->id, 'baslik' => 'Ev', 'ad_soyad' => 'Ayşe Yılmaz', 'telefon' => '05320000006',
            'il' => 'İstanbul', 'ilce' => 'Kadıköy', 'acik_adres' => 'Üçüncü sokak no 3', 'is_default' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $cevap = $this->deleteJson('/api/v1/hesap')->assertOk();

        $this->assertSame('Silinmiş Kullanıcı', $cari->fresh()->name);
        $this->assertStringContainsString('anonim.local', (string) $cari->fresh()->email);
        $this->assertNull($cari->fresh()->phone);

        $siparis->refresh();
        $this->assertSame('Silinmiş Kullanıcı', $siparis->customer_name);
        $this->assertStringContainsString('anonim.local', (string) $siparis->customer_email);
        $this->assertNull($siparis->customer_phone);

        $this->assertSoftDeleted('users', ['id' => $musteri->id]);
        $this->assertSame(0, DB::table('customer_addresses')->where('user_id', $musteri->id)->count());
    }

    /** @test */
    public function fiyat_gecmisi_otuz_gunluk_seriyi_dondurur(): void
    {
        $magaza = $this->magazaSunucusu();
        $urun = Product::create([
            'company_id' => $magaza->company_id, 'name' => 'Grafik Ürün', 'unit' => 'adet',
            'purchase_price' => 50, 'sale_price' => 200, 'stock_quantity' => 5,
        ]);

        $seri = $this->getJson("/api/v1/products/{$urun->id}/fiyat-gecmisi")->assertOk()->json('data');

        $this->assertSame(30, count($seri), 'Kayıt yoksa 30 günlük geri doldurma yapılmalı.');
        $this->assertTrue($seri[0]['tarih'] < $seri[29]['tarih'], 'Seri kronolojik olmalı.');

        // İkinci çağrı yeni kayıt üretmez — var olan seriyi döndürür
        $ikinci = $this->getJson("/api/v1/products/{$urun->id}/fiyat-gecmisi")->json('data');
        $this->assertSame(30, count($ikinci));
    }

    /** @test */
    public function panelde_fiyat_degisimi_gecmise_kaydedilir(): void
    {
        $urun = Product::create([
            'company_id' => 1, 'name' => 'Kayıt Ürün', 'unit' => 'adet',
            'purchase_price' => 50, 'sale_price' => 100, 'stock_quantity' => 5,
        ]);

        $urun->update(['sale_price' => 120]);

        $this->assertSame(1, DB::table('price_history')->where('product_id', $urun->id)->count());
        $this->assertSame(120.0, (float) DB::table('price_history')->where('product_id', $urun->id)->value('price'));
    }

    /** @test */
    public function yorumlar_yararli_oyuna_gore_siralanabilir(): void
    {
        $magaza = $this->magazaSunucusu();
        $urun = Product::create([
            'company_id' => $magaza->company_id, 'name' => 'Yorum Ürün', 'unit' => 'adet',
            'purchase_price' => 50, 'sale_price' => 100, 'stock_quantity' => 5,
        ]);

        $az = ProductReview::create([
            'product_id' => $urun->id, 'user_id' => $magaza->id, 'user_name' => 'Az Oylu',
            'rating' => 5, 'comment' => 'Az oy alan yorum', 'is_approved' => true,
        ]);
        $cok = ProductReview::create([
            'product_id' => $urun->id, 'user_id' => $magaza->id, 'user_name' => 'Çok Oylu',
            'rating' => 4, 'comment' => 'Çok oy alan yorum', 'is_approved' => true,
        ]);
        $oyVerenler = User::factory()->count(3)->create();
        $oyVerenler->each(fn ($u) => ReviewVote::create(['review_id' => $cok->id, 'user_id' => $u->id]));
        ReviewVote::create(['review_id' => $az->id, 'user_id' => $magaza->id]);

        // created_at'ler aynı saniyeye düşmesin — en yeni sıralaması kesin olsun
        DB::table('product_reviews')->where('id', $az->id)->update(['created_at' => now()->subDay()]);

        $sirali = array_column(
            $this->getJson("/api/v1/products/{$urun->id}/reviews?sirala=yararli")->assertOk()->json('data.yorumlar'),
            'id'
        );
        $this->assertSame([$cok->id, $az->id], $sirali, 'En yararlı sıralaması oy sayısına göre olmalı.');

        // varsayılan sıralama: en yeni (cok son eklenen → başta)
        $varsayilan = array_column($this->getJson("/api/v1/products/{$urun->id}/reviews")->json('data.yorumlar'), 'id');
        $this->assertSame([$cok->id, $az->id], $varsayilan);
    }
}
