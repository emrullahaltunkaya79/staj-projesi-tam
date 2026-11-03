<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    private function createInvoiceFor(User $user): int
    {
        $contact = Contact::create([
            'company_id' => $user->company_id,
            'type' => 'musteri',
            'name' => 'PDF Test Cari',
        ]);

        $product = Product::create([
            'company_id' => $user->company_id,
            'name' => 'PDF Test Ürün',
            'unit' => 'adet',
            'purchase_price' => 10,
            'sale_price' => 90,
            'stock_quantity' => 10,
        ]);

        $invoice = app(InvoiceService::class)->createSaleInvoice($user->company_id, [
            'contact_id' => $contact->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 20,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 90]],
        ]);

        return $invoice->id;
    }

    /** @test */
    public function fatura_pdf_olarak_indirilebilir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $invoiceId = $this->createInvoiceFor($user);

        $response = $this->get("/faturalar/{$invoiceId}/pdf");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString(
            "attachment; filename=fatura-{$invoiceId}.pdf",
            $response->headers->get('Content-Disposition')
        );
        // Geçerli bir PDF imzası ile başlıyor mu (%PDF)
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /** @test */
    public function baska_sirketin_faturasi_pdf_olarak_indirilemez(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Diğer şirketin faturası, o şirketin kullanıcısı tarafından kesilir
        $this->actingAs($otherUser);
        $otherInvoiceId = $this->createInvoiceFor($otherUser);

        // İlk şirketin kullanıcısı indirime yapamaz
        $this->actingAs($user);
        $this->get("/faturalar/{$otherInvoiceId}/pdf")->assertNotFound();
    }
}
