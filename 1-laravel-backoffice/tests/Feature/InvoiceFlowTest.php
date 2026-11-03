<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    private function loginUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    /** @test */
    public function fatura_kesilince_stok_duser_ve_cari_bakiyesi_artar(): void
    {
        $user = $this->loginUser();

        $contact = Contact::create([
            'company_id' => $user->company_id,
            'type' => 'musteri',
            'name' => 'Test Müşteri',
        ]);

        $product = Product::create([
            'company_id' => $user->company_id,
            'name' => 'Test Ürün',
            'unit' => 'adet',
            'purchase_price' => 10,
            'sale_price' => 25,
            'stock_quantity' => 10,
        ]);

        $response = $this->post('/faturalar', [
            'contact_id' => $contact->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 20,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 4, 'unit_price' => 25],
            ],
        ]);

        $response->assertRedirect();

        $product->refresh();
        $contact->refresh();

        // Stok: 10 - 4 = 6
        $this->assertSame(6, $product->stock_quantity);

        // Ara toplam 100 + %20 KDV = 120
        $this->assertEquals(120.0, (float) $contact->balance);

        $invoice = $contact->invoices()->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(100.0, (float) $invoice->subtotal);
        $this->assertEquals(20.0, (float) $invoice->tax_amount);
        $this->assertEquals(120.0, (float) $invoice->total);
        $this->assertSame('SF-000001', $invoice->invoice_no);
        $this->assertSame(1, $invoice->items()->count());
    }

    /** @test */
    public function stok_yetersizse_fatura_kesilmez_ve_hicbir_veri_degismez(): void
    {
        $user = $this->loginUser();

        $contact = Contact::create([
            'company_id' => $user->company_id,
            'type' => 'musteri',
            'name' => 'Test Müşteri',
        ]);

        $product = Product::create([
            'company_id' => $user->company_id,
            'name' => 'Az Stoklu Ürün',
            'unit' => 'adet',
            'purchase_price' => 10,
            'sale_price' => 25,
            'stock_quantity' => 3,
        ]);

        $response = $this
            ->from('/faturalar/create')
            ->post('/faturalar', [
                'contact_id' => $contact->id,
                'issue_date' => now()->toDateString(),
                'tax_rate' => 20,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 25],
                ],
            ]);

        // "Yetersiz stok!" hatası kullanıcıya döner
        $response->assertRedirect('/faturalar/create')->assertSessionHasErrors('items');
        $this->assertStringContainsString('Yetersiz stok!', session('errors')->get('items')[0]);

        // Transaction rollback: fatura yok, stok değişmedi, bakiye 0
        $this->assertSame(0, $contact->invoices()->count());
        $this->assertSame(0, (int) $contact->balance);
        $this->assertSame(3, $product->fresh()->stock_quantity);
    }

    /** @test */
    public function kullanici_baska_sirketin_verilerini_goremez(): void
    {
        $user = $this->loginUser();

        // Başka bir şirketin ürünü (factory kullanıcıyıyla farklı şirket oluşturur)
        $otherProduct = Product::create([
            'company_id' => User::factory()->create()->company_id,
            'name' => 'Rakip Şirketin Ürünü XYZ',
            'unit' => 'adet',
            'purchase_price' => 1,
            'sale_price' => 2,
            'stock_quantity' => 99,
        ]);

        $this->get('/urunler')
            ->assertOk()
            ->assertDontSee('Rakip Şirketin Ürünü XYZ');
    }

    /** @test */
    public function kayit_sirket_ve_kullaniciyi_birlikte_olusturur(): void
    {
        $response = $this->post('/kayit', [
            'company_name' => 'Yeni Şirket A.Ş.',
            'name' => 'Stajer Öğrenci',
            'email' => 'stajer@ornek.com',
            'password' => 'parola123',
            'password_confirmation' => 'parola123',
        ]);

        $response->assertRedirect('/panel');

        $this->assertDatabaseHas('companies', ['name' => 'Yeni Şirket A.Ş.']);
        $this->assertDatabaseHas('users', ['email' => 'stajer@ornek.com']);

        $user = \App\Models\User::where('email', 'stajer@ornek.com')->first();
        $this->assertNotNull($user->company_id);
        $this->assertSame('Yeni Şirket A.Ş.', $user->company->name);
    }
}
