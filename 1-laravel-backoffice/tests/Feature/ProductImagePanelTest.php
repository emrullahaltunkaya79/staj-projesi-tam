<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Panel ürün görsel mekanizması: yükleme + otomatik 1600 px küçültme, ana görsel
 * ataması, ana-görsel-değiştirme, silme sonrası sıradaki görselin ana olması.
 */
class ProductImagePanelTest extends TestCase
{
    use RefreshDatabase;

    private User $yonetici;

    private Product $urun;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->yonetici = User::factory()->create();
        $this->actingAs($this->yonetici);

        $this->urun = Product::create([
            'company_id' => $this->yonetici->company_id,
            'name' => 'Görsel Panel Test Ürünü',
            'unit' => 'adet',
            'purchase_price' => 50,
            'sale_price' => 100,
            'stock_quantity' => 5,
        ]);
    }

    private function depoYolu(string $yol): string
    {
        return Storage::disk('local')->path($yol);
    }

    /** @test */
    public function gorsel_yuklenir_ve_1600_px_sinirina_kucultulur(): void
    {
        // 2400×1600 yükle → saklanan kopya en fazla 1600 px genişlikte olmalı
        $dosya = UploadedFile::fake()->image('buyuk.jpg', 2400, 1600);

        $this->followingRedirects()
            ->post("/urunler/{$this->urun->id}/gorseller", ['images' => [$dosya]])
            ->assertOk();

        $gorsel = $this->urun->images()->first();
        $this->assertNotNull($gorsel);
        $this->assertTrue($gorsel->is_primary, 'İlk yüklenen görsel otomatik ana görsel olmalı.');

        $bilgi = getimagesize($this->depoYolu($gorsel->path));
        $this->assertLessThanOrEqual(1600, $bilgi[0], 'Görsel 1600 px genişliğe küçültülmeli.');
    }

    /** @test */
    public function ikinci_yuklenen_gorsel_ana_gorseli_calmaz(): void
    {
        $this->post("/urunler/{$this->urun->id}/gorseller", [
            'images' => [UploadedFile::fake()->image('birinci.jpg', 800, 800)],
        ]);
        $this->post("/urunler/{$this->urun->id}/gorseller", [
            'images' => [UploadedFile::fake()->image('ikinci.jpg', 800, 800)],
        ]);

        $primaries = $this->urun->images()->where('is_primary', true)->get();
        $this->assertSame(1, $primaries->count());
        $this->assertTrue(
            $primaries->first()->id === $this->urun->images()->orderBy('id')->first()->id,
            'İlk yüklenen görsel ana görsel olarak kalmalı.'
        );
    }

    /** @test */
    public function ana_gorsel_butonu_roketi_degistirir(): void
    {
        $this->post("/urunler/{$this->urun->id}/gorseller", [
            'images' => [
                UploadedFile::fake()->image('a.jpg', 800, 800),
                UploadedFile::fake()->image('b.jpg', 800, 800),
            ],
        ]);

        $gorseller = $this->urun->images()->orderBy('id')->get();
        [$ilkgorsel, $ikincigorsel] = $gorseller;
        $this->assertTrue($ilkgorsel->is_primary);
        $this->assertFalse($ikincigorsel->is_primary);

        $this->followingRedirects()
            ->post("/urunler/{$this->urun->id}/gorseller/{$ikincigorsel->id}/ana-gorsel")
            ->assertOk();

        $this->assertTrue($ikincigorsel->fresh()->is_primary);
        $this->assertFalse($ilkgorsel->fresh()->is_primary);
        $this->assertSame(1, $this->urun->images()->where('is_primary', true)->count());
    }

    /** @test */
    public function ana_gorsel_silinince_siradaki_gorsel_ana_olur(): void
    {
        $this->post("/urunler/{$this->urun->id}/gorseller", [
            'images' => [
                UploadedFile::fake()->image('ana.jpg', 800, 800),
                UploadedFile::fake()->image('diger.jpg', 800, 800),
            ],
        ]);

        $gorseller = $this->urun->images()->orderBy('id')->get();
        $ana = $gorseller[0];

        $this->followingRedirects()
            ->delete("/urunler/{$this->urun->id}/gorseller/{$ana->id}")
            ->assertOk();

        $this->assertSame(1, $this->urun->images()->count());
        $this->assertTrue($this->urun->images()->first()->is_primary, 'Silme sonrası kalan görsel ana görsel olmalı.');
    }

    /** @test */
    public function goster_uci_gorseli_kimlik_dogrulamasiyla_servis_eder(): void
    {
        $this->post("/urunler/{$this->urun->id}/gorseller", [
            'images' => [UploadedFile::fake()->image('goster.jpg', 800, 800)],
        ]);
        $gorsel = $this->urun->images()->first();

        // oturum açıkken 200, oturum kapalıyken kimlik doğrulamaya yönlendirilir
        $this->get("/urunler/{$this->urun->id}/gorseller/{$gorsel->id}/goster")->assertOk();

        auth()->logout();
        $this->get("/urunler/{$this->urun->id}/gorseller/{$gorsel->id}/goster")->assertRedirect();
    }

    /** @test */
    public function gecersiz_dosya_tipi_reddedilir(): void
    {
        $pdf = UploadedFile::fake()->create('belge.pdf', 100);

        $this->followingRedirects()
            ->post("/urunler/{$this->urun->id}/gorseller", ['images' => [$pdf]])
            ->assertOk();

        $this->assertSame(0, $this->urun->images()->count());
    }
}
