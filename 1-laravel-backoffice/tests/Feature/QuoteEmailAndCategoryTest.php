<?php

namespace Tests\Feature;

use App\Mail\QuoteMail;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuoteEmailAndCategoryTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Teklif e-postası ----------

    /** @test */
    public function teklif_pdf_ekiyle_cariye_gonderilir(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->actingAs($user);

        $contact = Contact::create([
            'company_id' => $user->company_id, 'type' => 'musteri',
            'name' => 'Teklif E-posta Cari', 'email' => 'musteri@ornek.com',
        ]);
        $product = Product::create([
            'company_id' => $user->company_id, 'name' => 'Teklif Ürün', 'unit' => 'adet',
            'purchase_price' => 50, 'sale_price' => 100, 'stock_quantity' => 10,
        ]);

        $quote = app(QuoteService::class)->createQuote($user->company_id, [
            'contact_id' => $contact->id, 'issue_date' => now()->toDateString(), 'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
        ]);

        $this->post("/teklifler/{$quote->id}/e-posta")
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertQueued(QuoteMail::class, fn ($mail) => $mail->hasTo('musteri@ornek.com'));
    }

    /** @test */
    public function teklif_pdf_indirilebilir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contact = Contact::create(['company_id' => $user->company_id, 'type' => 'musteri', 'name' => 'PDF Test']);
        $product = Product::create([
            'company_id' => $user->company_id, 'name' => 'PDF Ürün', 'unit' => 'adet',
            'purchase_price' => 10, 'sale_price' => 50, 'stock_quantity' => 5,
        ]);

        $quote = app(QuoteService::class)->createQuote($user->company_id, [
            'contact_id' => $contact->id, 'issue_date' => now()->toDateString(), 'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50]],
        ]);

        $this->get("/teklifler/{$quote->id}/pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    // ---------- Ürün kategorileri ----------

    /** @test */
    public function kategori_eklenir_ve_urune_baglanir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Kategori ekle
        $this->post('/kategoriler', ['name' => 'Test Kategori'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $category = Category::where('name', 'Test Kategori')->first();
        $this->assertNotNull($category);

        // Ürünü bu kategoriye bağla
        $product = Product::create([
            'company_id' => $user->company_id,
            'category_id' => $category->id,
            'name' => 'Kategorili Ürün', 'unit' => 'adet',
            'purchase_price' => 10, 'sale_price' => 20, 'stock_quantity' => 5,
        ]);

        $this->assertSame('Test Kategori', $product->category->name);

        // Ürün listesinde kategori filtresi çalışır
        $this->get('/urunler?category='.$category->id)
            ->assertOk()
            ->assertSee('Kategorili Ürün');
    }
}
