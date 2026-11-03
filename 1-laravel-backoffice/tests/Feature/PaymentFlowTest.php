<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function tahsilat_kaydi_cari_bakiyesini_azaltir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contact = Contact::create([
            'company_id' => $user->company_id,
            'type' => 'musteri',
            'name' => 'Tahsilat Test Cari',
        ]);

        $product = Product::create([
            'company_id' => $user->company_id,
            'name' => 'Test Ürün',
            'unit' => 'adet',
            'purchase_price' => 10,
            'sale_price' => 100,
            'stock_quantity' => 10,
        ]);

        // Bakiye 240 olsun (2 x 100 = 200 + %20 KDV = 40)
        app(InvoiceService::class)->createSaleInvoice($user->company_id, [
            'contact_id' => $contact->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 20,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100]],
        ]);

        $response = $this->post('/tahsilatlar', [
            'contact_id' => $contact->id,
            'amount' => 50,
            'payment_type' => 'havale',
            'description' => 'Banka havalesi',
        ]);

        $response->assertRedirect('/cariler/'.$contact->id);

        // Bakiye: 240 - 50 = 190
        $this->assertEquals(190.0, (float) $contact->fresh()->balance);
        $this->assertDatabaseHas('payments', [
            'contact_id' => $contact->id,
            'amount' => 50,
            'payment_type' => 'havale',
        ]);
    }

    /** @test */
    public function bakiyeden_fazla_tahsilat_reddedilir_ve_hicbir_sey_degismez(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contact = Contact::create([
            'company_id' => $user->company_id,
            'type' => 'musteri',
            'name' => 'Aşım Test Cari',
            'balance' => 100,
        ]);

        $this->post('/tahsilatlar', [
            'contact_id' => $contact->id,
            'amount' => 150,
            'payment_type' => 'nakit',
        ])->assertSessionHasErrors('amount');

        // Rollback: tahsilat kaydı yok, bakiye değişmedi
        $this->assertSame(0, $contact->payments()->count());
        $this->assertEquals(100.0, (float) $contact->fresh()->balance);
    }

    /** @test */
    public function bakiyesi_olmayan_cariye_tahsilat_yapilamaz(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contact = Contact::create([
            'company_id' => $user->company_id,
            'type' => 'musteri',
            'name' => 'Sıfır Bakiye Cari',
        ]);

        $this->post('/tahsilatlar', [
            'contact_id' => $contact->id,
            'amount' => 10,
            'payment_type' => 'nakit',
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, $contact->payments()->count());
    }
}
