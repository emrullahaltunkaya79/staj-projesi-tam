<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DarkModeAndQrTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function fatura_kesildiginde_dogrulama_kodu_uretilir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contact = Contact::create(['company_id' => $user->company_id, 'type' => 'musteri', 'name' => 'QR Cari']);
        $product = Product::create([
            'company_id' => $user->company_id, 'name' => 'QR Ürün', 'unit' => 'adet',
            'purchase_price' => 50, 'sale_price' => 100, 'stock_quantity' => 10,
        ]);

        $invoice = app(InvoiceService::class)->createSaleInvoice($user->company_id, [
            'contact_id' => $contact->id, 'issue_date' => now()->toDateString(), 'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
        ]);

        $this->assertNotEmpty($invoice->verification_code);
        $this->assertSame(24, strlen($invoice->verification_code));
    }

    /** @test */
    public function qr_koduyla_fatura_herkese_acik_dogrulanabilir(): void
    {
        $user = User::factory()->create();

        $contact = Contact::create(['company_id' => $user->company_id, 'type' => 'musteri', 'name' => 'Doğrulama Cari']);
        $product = Product::create([
            'company_id' => $user->company_id, 'name' => 'Doğrulama Ürün', 'unit' => 'adet',
            'purchase_price' => 10, 'sale_price' => 50, 'stock_quantity' => 5,
        ]);

        $invoice = app(InvoiceService::class)->createSaleInvoice($user->company_id, [
            'contact_id' => $contact->id, 'issue_date' => now()->toDateString(), 'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50]],
        ]);

        // Oturum AÇmadan (misafir) doğrulama sayfası erişilebilir
        $this->get('/fatura/dogrula/'.$invoice->verification_code)
            ->assertOk()
            ->assertSee('Fatura Doğrulandı')
            ->assertSee($invoice->invoice_no)
            ->assertSee($invoice->company->name);

        // Geçersiz kod → "Bulunamadı"
        $this->get('/fatura/dogrula/gecersiz-kod-123')
            ->assertOk()
            ->assertSee('Fatura Bulunamadı');
    }

    /** @test */
    public function dark_mode_css_siniflari_derlenmis(): void
    {
        // Derlenen CSS'te dark: varyantları var mı?
        $css = file_get_contents(public_path('build/manifest.json'));
        $cssFile = json_decode($css, true);

        // Build manifest'tan CSS dosyasını bul
        $cssPath = null;
        foreach ($cssFile as $entry) {
            if (isset($entry['file']) && str_contains($entry['file'], '.css')) {
                $cssPath = public_path('build/'.$entry['file']);
                break;
            }
        }

        $this->assertNotNull($cssPath, 'CSS build dosyası bulunamadı');
        $content = file_get_contents($cssPath);
        $this->assertStringContainsString('.dark', $content, 'dark: varyantları CSS içinde yok');
    }
}
