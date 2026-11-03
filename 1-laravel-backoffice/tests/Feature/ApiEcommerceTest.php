<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
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
}
