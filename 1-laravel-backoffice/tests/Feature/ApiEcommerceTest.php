<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Http\UploadedFile;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiEcommerceTest extends TestCase
{
    use RefreshDatabase;

    private function loginUser(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['ecommerce']);

        return $user;
    }

    private function createProduct(User $user, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'company_id' => $user->company_id,
            'name' => 'API Test Ürün',
            'unit' => 'adet',
            'purchase_price' => 50,
            'sale_price' => 100,
            'stock_quantity' => 10,
        ], $overrides));
    }

    /** @test */
    public function token_olmadan_api_erisimi_engellenir(): void
    {
        $this->getJson('/api/v1/products')->assertUnauthorized();
        $this->postJson('/api/v1/orders', [])->assertUnauthorized();
    }

    /** @test */
    public function urun_listesi_stok_bilgisiyle_doner(): void
    {
        $user = $this->loginUser();
        $product = $this->createProduct($user, ['name' => 'Katalog Ürün', 'sale_price' => 150, 'stock_quantity' => 7]);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Katalog Ürün',
                'stock_quantity' => 7,
                'in_stock' => true,
            ]);
    }

    /** @test */
    public function canli_stok_sorgusu_calisir(): void
    {
        $user = $this->loginUser();
        $product = $this->createProduct($user, ['stock_quantity' => 3]);

        $this->getJson('/api/v1/stock?product_ids[]='.$product->id)
            ->assertOk()
            ->assertJsonFragment(['stock_quantity' => 3, 'in_stock' => true]);
    }

    /** @test */
    public function siparis_otomatik_fatura_keser_ve_stok_duser(): void
    {
        $user = $this->loginUser();

        $contactResponse = $this->postJson('/api/v1/customers', [
            'name' => 'E-Ticaret Müşterisi',
            'email' => 'eticaret@ornek.com',
        ]);
        $contactResponse->assertStatus(201);
        $contactId = $contactResponse->json('data.contact_id');

        $product = $this->createProduct($user, ['stock_quantity' => 10]);

        $this->postJson('/api/v1/orders', [
            'contact_id' => $contactId,
            'tax_rate' => 20,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ])->assertStatus(201)
            ->assertJsonStructure(['message', 'data' => ['order_id', 'order_no', 'status', 'expires_at', 'total']]);

        // Rezerve model: sipariş pending'de stok fiziksel değişmez, rezerv artar
        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertSame(3, $product->fresh()->reserved_quantity);
        $order = \App\Models\Order::where('external_ref', null)->latest('id')->first();
        $this->assertNotNull($order);
    }

    /** @test */
    public function sipariste_stok_yetersizse_hata_doner_ve_veri_degismez(): void
    {
        $user = $this->loginUser();

        $contact = Contact::create(['company_id' => $user->company_id, 'type' => 'musteri', 'name' => 'Stok Test']);
        $product = $this->createProduct($user, ['stock_quantity' => 2]);

        $this->postJson('/api/v1/orders', [
            'contact_id' => $contact->id,
            'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ])->assertStatus(422)->assertJsonValidationErrors('items');

        $this->assertSame(2, $product->fresh()->stock_quantity);
        $this->assertSame(0, $contact->invoices()->count());
    }

    /** @test */
    public function musteri_idempotent_olusturulur(): void
    {
        $user = $this->loginUser();

        $payload = ['name' => 'Aynı Müşteri', 'email' => 'ayni@ornek.com'];
        $this->postJson('/api/v1/customers', $payload)->assertStatus(201);
        $this->postJson('/api/v1/customers', $payload)->assertStatus(200);

        $this->assertSame(1, Contact::where('email', 'ayni@ornek.com')->count());
    }

    /** @test */
    public function arama_endpointu_terimle_sonuc_doner(): void
    {
        $user = $this->loginUser();
        $this->createProduct($user, ['name' => 'Kablosuz Kulaklık Pro', 'brand' => 'Sony']);
        $this->createProduct($user, ['name' => 'Ofis Sandalyesi', 'brand' => 'IKEA']);

        $cevap = $this->getJson('/api/v1/products/arama?q=kulak');

        $cevap->assertOk();
        $cevap->assertJsonStructure(['data' => [['id', 'name', 'final_price', 'in_stock']], 'meta' => ['toplam', 'markalar']]);
        $this->assertStringContainsString('Kulaklık', $cevap->json('data.0.name'));
    }

    /** @test */
    public function arama_fiyata_gore_siralanir(): void
    {
        $user = $this->loginUser();
        $this->createProduct($user, ['name' => 'Ucuz Kulaklık', 'sale_price' => 90, 'brand' => 'A']);
        $this->createProduct($user, ['name' => 'Pahalı Kulaklık', 'sale_price' => 900, 'brand' => 'B']);

        $cevap = $this->getJson('/api/v1/products/arama?q=kulak&sirala=fiyat-artan');
        $cevap->assertOk();
        $fiyatlar = array_map(fn ($u) => $u['final_price'], $cevap->json('data'));
        $this->assertCount(2, $fiyatlar);
        $this->assertLessThanOrEqual($fiyatlar[1], $fiyatlar[0]);
    }

    /** @test */
    public function arama_kategori_faceti_ve_varyant_turlerini_doner(): void
    {
        $user = $this->loginUser();
        $kategori = Category::create(['company_id' => $user->company_id, 'name' => 'Elektronik']);
        $saat = $this->createProduct($user, ['name' => 'Akıllı Saat Sport', 'category_id' => $kategori->id]);
        ProductVariant::create(['company_id' => $user->company_id, 'product_id' => $saat->id, 'name' => 'M/L', 'type' => 'beden', 'beden' => 'M/L', 'stock_quantity' => 3]);
        ProductVariant::create(['company_id' => $user->company_id, 'product_id' => $saat->id, 'name' => 'L/XL', 'type' => 'beden', 'beden' => 'L/XL', 'stock_quantity' => 0]);

        $cevap = $this->getJson('/api/v1/products/arama?q=saat&per_page=200');

        $cevap->assertOk();
        $this->assertSame(200, $cevap->json('meta.per_page'));
        $this->assertSame(1, $cevap->json('meta.kategoriler.Elektronik'));
        $this->assertSame(
            [
                ['ad' => 'M/L', 'tur' => 'beden', 'renk' => null, 'beden' => 'M/L', 'stokta' => true],
                ['ad' => 'L/XL', 'tur' => 'beden', 'renk' => null, 'beden' => 'L/XL', 'stokta' => false],
            ],
            $cevap->json('data.0.varyantlar'),
        );
    }

    /** @test */
    public function varyant_turu_belirtilmezse_renk_kabul_edilir(): void
    {
        $user = $this->loginUser();
        $urun = $this->createProduct($user, ['name' => 'Kablosuz Kulaklık']);
        ProductVariant::create(['company_id' => $user->company_id, 'product_id' => $urun->id, 'name' => 'Siyah', 'stock_quantity' => 5]);

        $liste = $this->getJson('/api/v1/products')->assertOk();
        $this->assertSame('renk', $liste->json('data.0.varyantlar.0.tur'));

        $detay = $this->getJson("/api/v1/products/{$urun->id}")->assertOk();
        $this->assertSame('renk', $detay->json('data.variants.0.type'));
    }

    /** @test */
    public function gorselli_yorum_multipart_ile_eklenir(): void
    {
        $user = $this->loginUser();
        $product = $this->createProduct($user);

        $cevap = $this->postJson(
            "/api/v1/products/{$product->id}/reviews",
            [
                'rating' => 5,
                'comment' => 'Fotolu deneme yorumu',
                'image' => UploadedFile::fake()->image('yorum.jpg', 300, 200),
            ],
        );

        $cevap->assertCreated();
        $yorum = ProductReview::latest('id')->first();
        $this->assertNotNull($yorum->image_path);
        $this->assertStringStartsWith('reviews/', $yorum->image_path);

        $liste = $this->getJson("/api/v1/products/{$product->id}/reviews");
        $liste->assertOk()->assertJsonPath('data.yorumlar.0.image_path', $yorum->image_path);
    }

    /** @test */
    public function gorselli_yorum_gecersiz_dosyada_reddedilir(): void
    {
        $user = $this->loginUser();
        $product = $this->createProduct($user);

        $cevap = $this->postJson(
            "/api/v1/products/{$product->id}/reviews",
            [
                'rating' => 4,
                'image' => UploadedFile::fake()->create('not.pdf', 50, 'application/pdf'),
            ],
        )->assertStatus(422);
    }

    /** @test */
    public function bulten_aboneligi_kaydedilir(): void
    {
        $this->loginUser();

        $this->postJson('/api/v1/newsletter', ['email' => 'abone@ornek.test'])
            ->assertOk()
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Abone'));

        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'abone@ornek.test']);
    }

    /** @test */
    public function stok_bildirimi_kaydi_eklenir(): void
    {
        $user = $this->loginUser();
        $product = $this->createProduct($user);

        $this->postJson('/api/v1/stock-alerts', ['email' => 'bekleyen@ornek.test', 'product_id' => $product->id])
            ->assertOk();

        $this->assertDatabaseHas('stock_alerts', ['email' => 'bekleyen@ornek.test', 'product_id' => $product->id]);
    }

    /** @test */
    public function bildirim_kayitlari_listelenir(): void
    {
        $this->loginUser();

        \DB::table('newsletter_subscribers')->insert([
            'email' => 'liste@ornek.test', 'subscribed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        \DB::table('stock_alerts')->insert([
            'email' => 'alert@ornek.test', 'product_id' => $this->createProduct($this->loginUser())->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $cevap = $this->getJson('/api/v1/bildirimler');
        $cevap->assertOk();
        $this->assertSame('liste@ornek.test', $cevap->json('data.bulten.0.email'));
        $this->assertSame('alert@ornek.test', $cevap->json('data.stok.0.email'));
    }

    /** @test */
    public function iletisim_formu_mesaji_kaydedilir(): void
    {
        $this->loginUser();

        $this->postJson('/api/v1/iletisim', [
            'ad' => 'Ayşe Yılmaz',
            'eposta' => 'ayse@ornek.test',
            'mesaj' => 'Toplu alım fiyatı öğrenmek istiyorum.',
        ])->assertOk()->assertJsonPath('message', fn ($m) => str_contains($m, 'alındı'));

        $this->assertDatabaseHas('contact_messages', ['eposta' => 'ayse@ornek.test']);
    }

    /** @test */
    public function iletisim_mesajlari_listelenir(): void
    {
        $this->loginUser();

        \DB::table('contact_messages')->insert([
            'ad' => 'Mehmet', 'eposta' => 'mehmet@ornek.test', 'mesaj' => 'Bayilik süreci?',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $cevap = $this->getJson('/api/v1/iletisim');
        $cevap->assertOk();
        $this->assertSame('mehmet@ornek.test', $cevap->json('data.0.eposta'));
    }
}
