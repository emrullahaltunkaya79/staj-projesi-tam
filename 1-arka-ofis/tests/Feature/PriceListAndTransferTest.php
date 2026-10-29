<?php

namespace Tests\Feature;

use App\Models\CashTransfer;
use App\Models\Contact;
use App\Models\ContactPrice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceListAndTransferTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Özel fiyat listesi ----------

    /** @test */
    public function cari_bazli_ozel_fiyat_kaydedilir_ve_endpoint_donuyor(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contact = Contact::create(['company_id' => $user->company_id, 'type' => 'musteri', 'name' => 'Fiyat Cari']);
        $product = Product::create(['company_id' => $user->company_id, 'name' => 'Fiyat Ürün', 'unit' => 'adet', 'purchase_price' => 100, 'sale_price' => 200, 'stock_quantity' => 10]);

        // UI'dan kaydet
        $this->post('/fiyatlar', [
            'contact_id' => $contact->id,
            'product_id' => $product->id,
            'price' => 175,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('contact_prices', [
            'contact_id' => $contact->id,
            'product_id' => $product->id,
            'price' => 175,
        ]);

        // Fatura formundaki fiyat çözümleme endpoint'i
        $this->getJson('/ara/fiyatlar?contact_id='.$contact->id)
            ->assertOk()
            ->assertJson([$product->id => '175.00']);
    }

    /** @test */
    public function ayni_cari_urun_cifti_guncellenir_duplike_olusmaz(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contact = Contact::create(['company_id' => $user->company_id, 'type' => 'musteri', 'name' => 'Update Cari']);
        $product = Product::create(['company_id' => $user->company_id, 'name' => 'Update Ürün', 'unit' => 'adet', 'purchase_price' => 10, 'sale_price' => 20, 'stock_quantity' => 5]);

        $this->post('/fiyatlar', ['contact_id' => $contact->id, 'product_id' => $product->id, 'price' => 18]);
        $this->post('/fiyatlar', ['contact_id' => $contact->id, 'product_id' => $product->id, 'price' => 15]);

        $this->assertSame(1, ContactPrice::where('contact_id', $contact->id)->where('product_id', $product->id)->count());
        $this->assertSame(15.0, (float) ContactPrice::priceFor($contact->id, $product->id));
    }

    // ---------- Kasa → Banka virmanı ----------

    /** @test */
    public function virman_kaydedilir_ve_kasa_durumuna_yansir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Kasa → Banka 500₺
        $this->post('/virmanlar', [
            'direction' => 'nakit_havale',
            'amount' => 500,
            'transfer_date' => now()->toDateString(),
            'description' => 'Bankaya para yatırma',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('cash_transfers', ['amount' => 500, 'direction' => 'nakit_havale']);

        // Ters yön: Banka → Kasa 200₺
        $this->post('/virmanlar', [
            'direction' => 'havale_nakit',
            'amount' => 200,
            'transfer_date' => now()->toDateString(),
        ])->assertSessionHas('success');

        // Raporlarda kasa durumu: nakit -300, banka +300
        $this->get('/raporlar')
            ->assertOk()
            ->assertSee('Kasada (Nakit)')
            ->assertSee('Bankada (Havale)');
    }

    // ---------- Kâr marjı raporu ----------

    /** @test */
    public function raporlarda_urun_kar_marji_gorunur(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Product::create(['company_id' => $user->company_id, 'name' => 'Kâr Test Ürün', 'unit' => 'adet', 'purchase_price' => 100, 'sale_price' => 250, 'stock_quantity' => 5]);

        $this->get('/raporlar')
            ->assertOk()
            ->assertSee('Ürün Bazında Kâr Marjı')
            ->assertSee('Kâr Test Ürün');
    }
}
