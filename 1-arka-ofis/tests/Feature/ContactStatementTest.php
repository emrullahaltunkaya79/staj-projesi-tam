<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactStatementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function cari_ekstresi_bilgileri_faturalari_ve_bakiyeyi_gosterir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contact = Contact::create([
            'company_id' => $user->company_id,
            'type' => 'musteri',
            'name' => 'Ekstre Test Market',
            'tax_number' => '1112223334',
        ]);

        $product = Product::create([
            'company_id' => $user->company_id,
            'name' => 'Ekstre Test Ürünü',
            'unit' => 'adet',
            'purchase_price' => 10,
            'sale_price' => 100,
            'stock_quantity' => 10,
        ]);

        app(InvoiceService::class)->createSaleInvoice($user->company_id, [
            'contact_id' => $contact->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 20,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100],
            ],
        ]);

        $this->get('/cariler/'.$contact->id)
            ->assertOk()
            ->assertSee('Ekstre Test Market')
            ->assertSee('1112223334')
            ->assertSee('SF-000001')
            ->assertSee('240,00')   // fatura tutarı (200 + %20 KDV)
            ->assertSee('Açık');    // durum rozeti
    }

    /** @test */
    public function baska_sirketin_cari_ekstresine_erisilemez(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $otherUser = User::factory()->create();
        $otherContact = Contact::create([
            'company_id' => $otherUser->company_id,
            'type' => 'musteri',
            'name' => 'Rakip Şirketin Carisi',
        ]);

        $this->get('/cariler/'.$otherContact->id)->assertNotFound();
    }
}
