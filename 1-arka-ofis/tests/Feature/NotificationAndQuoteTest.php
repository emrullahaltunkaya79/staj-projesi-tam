<?php

namespace Tests\Feature;

use App\Mail\DailySummaryMail;
use App\Mail\InvoiceMail;
use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationAndQuoteTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Günlük özet bildirimi ----------

    /** @test */
    public function gundelik_ozet_kritik_stok_ve_geciken_faturayi_icerir(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'kullanici', 'company_id' => $admin->company_id]);

        // Kritik stok
        Product::create([
            'company_id' => $admin->company_id,
            'name' => 'Bitmek Üzere Ürün',
            'unit' => 'adet',
            'purchase_price' => 10,
            'sale_price' => 20,
            'stock_quantity' => 2,
        ]);

        // Geciken vadeli fatura
        $contact = Contact::create([
            'company_id' => $admin->company_id,
            'type' => 'musteri',
            'name' => 'Bildirim Cari',
        ]);
        $product = Product::create([
            'company_id' => $admin->company_id,
            'name' => 'Bildirim Ürün',
            'unit' => 'adet',
            'purchase_price' => 10,
            'sale_price' => 100,
            'stock_quantity' => 10,
        ]);
        app(InvoiceService::class)->createSaleInvoice($admin->company_id, [
            'contact_id' => $contact->id,
            'issue_date' => now()->subDays(40)->toDateString(),
            'due_date' => now()->subDays(5)->toDateString(),
            'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
        ]);

        Artisan::call('bildirim:gundelik-ozet');

        // Özet yalnızca yöneticiye gider (kuyruklı mailable)
        Mail::assertQueued(DailySummaryMail::class, 1);
        Mail::assertQueued(DailySummaryMail::class, fn ($mail) => $mail->hasTo($admin->email)
            && str_contains($mail->envelope()->subject, 'Günlük')
        );
    }

    /** @test */
    public function fatura_pdf_ekiyle_cariye_eposta_gonderilir(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $contact = Contact::create([
            'company_id' => $user->company_id,
            'type' => 'musteri',
            'name' => 'E-posta Cari',
            'email' => 'musteri@ornek.com',
        ]);
        $product = Product::create([
            'company_id' => $user->company_id,
            'name' => 'E-posta Ürün',
            'unit' => 'adet',
            'purchase_price' => 10,
            'sale_price' => 100,
            'stock_quantity' => 10,
        ]);
        $invoice = app(InvoiceService::class)->createSaleInvoice($user->company_id, [
            'contact_id' => $contact->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
        ]);

        $this->post("/faturalar/{$invoice->id}/e-posta")
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertQueued(InvoiceMail::class, fn ($mail) => $mail->hasTo('musteri@ornek.com')
            && $mail->invoice->id === $invoice->id
        );
    }

    // ---------- Teklif modülü ----------

    private function quoteData(User $user): array
    {
        $contact = Contact::create([
            'company_id' => $user->company_id,
            'type' => 'musteri',
            'name' => 'Teklif Cari',
        ]);
        $product = Product::create([
            'company_id' => $user->company_id,
            'name' => 'Teklif Ürün',
            'unit' => 'adet',
            'purchase_price' => 100,
            'sale_price' => 200,
            'stock_quantity' => 10,
        ]);

        return [$contact, $product];
    }

    /** @test */
    public function teklif_stok_ve_bakiye_etkisi_yaratmaz(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        [$contact, $product] = $this->quoteData($user);

        $quote = app(QuoteService::class)->createQuote($user->company_id, [
            'contact_id' => $contact->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 20,
            'items' => [['product_id' => $product->id, 'quantity' => 4, 'unit_price' => 200]],
        ]);

        $this->assertSame('TK-000001', $quote->quote_no);
        $this->assertSame(10, $product->fresh()->stock_quantity); // stok değişmedi
        $this->assertEquals(0.0, (float) $contact->fresh()->balance); // bakiye değişmedi
    }

    /** @test */
    public function onayli_teklif_faturaya_donusur(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        [$contact, $product] = $this->quoteData($user);
        $service = app(QuoteService::class);

        $quote = $service->createQuote($user->company_id, [
            'contact_id' => $contact->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 20,
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 200]],
        ]);

        $service->changeStatus($quote, 'onaylandi', $user->company_id);
        $invoice = $service->convertToInvoice($quote->fresh(), $user->company_id);

        // Fatura kesildi: stok düştü, bakiye arttı
        $this->assertSame(7, $product->fresh()->stock_quantity);
        $this->assertEquals(720.0, (float) $contact->fresh()->balance); // 600 + %20

        $quote = $quote->fresh();
        $this->assertSame('faturalandi', $quote->status);
        $this->assertSame($invoice->id, $quote->converted_invoice_id);

        // İkinci dönüşüm engellenir
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->convertToInvoice($quote->fresh(), $user->company_id);
    }

    /** @test */
    public function onaysiz_ve_reddedilen_teklif_faturalanamaz(): void
    {
        $user = User::factory()->create();
        [$contact, $product] = $this->quoteData($user);
        $service = app(QuoteService::class);

        $quote = $service->createQuote($user->company_id, [
            'contact_id' => $contact->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 200]],
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->convertToInvoice($quote, $user->company_id);
    }
}
