<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductDiscountTest extends TestCase
{
    use RefreshDatabase;

    private function tokenAl(): string
    {
        $company = Company::factory()->create();
        $user = User::create([
            'company_id' => $company->id,
            'role' => 'admin',
            'name' => 'Api Kullanıcı',
            'email' => 'api@ornek.com',
            'password' => Hash::make('Sifre123'),
        ]);

        return $user->createToken('test', ['ecommerce'])->plainTextToken;
    }

    public function test_urun_listesi_indirim_alanlarini_donderir(): void
    {
        $token = $this->tokenAl();
        $company = Company::first();

        Product::create([
            'company_id' => $company->id,
            'name' => 'İndirimli Ürün',
            'unit' => 'adet',
            'purchase_price' => 50,
            'sale_price' => 100,
            'discount_percent' => 25,
            'stock_quantity' => 5,
        ]);

        $this->getJson('/api/v1/products', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('data.0.discount_percent', 25)
            ->assertJsonPath('data.0.final_price', 75)
            ->assertJsonPath('data.0.sale_price', 100);
    }

    public function test_siparis_satirlari_indirimli_fiyattan_hesaplanir(): void
    {
        $token = $this->tokenAl();
        $company = Company::first();

        $urun = Product::create([
            'company_id' => $company->id,
            'name' => 'Kampanyalı Ürün',
            'unit' => 'adet',
            'purchase_price' => 40,
            'sale_price' => 200,
            'discount_percent' => 20, // net: 160
            'stock_quantity' => 10,
        ]);

        $musteri = Contact::create([
            'company_id' => $company->id,
            'type' => 'musteri',
            'name' => 'İndirim Müşteri',
            'email' => 'indirim@ornek.com',
        ]);

        $yanit = $this->postJson('/api/v1/orders', [
            'contact_id' => $musteri->id,
            'tax_rate' => 0,
            'items' => [
                ['product_id' => $urun->id, 'quantity' => 2], // 2 × 160 = 320
            ],
        ], ['Authorization' => "Bearer {$token}"]);

        $yanit->assertCreated();
        $this->assertEquals(320.0, (float) $yanit->json('data.total'));

        // İndirim oranı %90'ı aşamaz
        $this->putJson("/api/v1/products/{$urun->id}", [
            'discount_percent' => 95,
        ], ['Authorization' => "Bearer {$token}"])->assertUnprocessable();
    }

    public function test_varyantli_siparis_varyant_stogunu_duser(): void
    {
        $token = $this->tokenAl();
        $company = Company::first();

        $urun = Product::create([
            'company_id' => $company->id,
            'name' => 'Varyantlı Ürün',
            'unit' => 'adet',
            'purchase_price' => 30,
            'sale_price' => 100,
            'stock_quantity' => 10,
        ]);
        $varyant = \App\Models\ProductVariant::create([
            'company_id' => $company->id,
            'product_id' => $urun->id,
            'name' => 'Siyah',
            'stock_quantity' => 5,
        ]);

        $musteri = Contact::create([
            'company_id' => $company->id,
            'type' => 'musteri',
            'name' => 'Varyant Müşteri',
            'email' => 'varyant@ornek.com',
        ]);

        $yanit = $this->postJson('/api/v1/orders', [
            'contact_id' => $musteri->id,
            'tax_rate' => 0,
            'items' => [
                ['product_id' => $urun->id, 'quantity' => 2, 'variant_id' => $varyant->id],
            ],
        ], ['Authorization' => "Bearer {$token}"]);

        $yanit->assertCreated();
        $this->assertSame('Varyantlı Ürün (Siyah)', $yanit->json('data.items.0.product_name'));
        $this->assertSame(3, (int) \App\Models\ProductVariant::find($varyant->id)->stock_quantity);

        // Varyant stoğu yetmezse reddedilir
        $this->postJson('/api/v1/orders', [
            'contact_id' => $musteri->id,
            'tax_rate' => 0,
            'items' => [
                ['product_id' => $urun->id, 'quantity' => 99, 'variant_id' => $varyant->id],
            ],
        ], ['Authorization' => "Bearer {$token}"])->assertUnprocessable();
    }
}
