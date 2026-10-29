<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DueDateTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function createOverdueInvoice(User $user): Contact
    {
        $contact = Contact::create([
            'company_id' => $user->company_id,
            'type' => 'musteri',
            'name' => 'Vade Test Cari',
        ]);

        $product = Product::create([
            'company_id' => $user->company_id,
            'name' => 'Vade Test Ürün',
            'unit' => 'adet',
            'purchase_price' => 50,
            'sale_price' => 100,
            'stock_quantity' => 10,
        ]);

        app(InvoiceService::class)->createSaleInvoice($user->company_id, [
            'contact_id' => $contact->id,
            'issue_date' => now()->subDays(40)->toDateString(),
            'due_date' => now()->subDays(10)->toDateString(), // 10 gün önce vadesi geçti
            'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 100]],
        ]);

        return $contact;
    }

    /** @test */
    public function geciken_fatura_dashboardda_uyari_gosterir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->createOverdueInvoice($user);

        $this->get('/panel')
            ->assertOk()
            ->assertSee('faturanın vadesi geçti')
            ->assertSee('300,00');
    }

    /** @test */
    public function ekstre_vade_tarihi_ve_gecikme_gunu_gosterir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contact = $this->createOverdueInvoice($user);

        $this->get("/cariler/{$contact->id}")
            ->assertOk()
            ->assertSee('gün gecikti')
            ->assertSee('Yaşlandırma')
            ->assertSee('1-30 gün gecikmiş')
            ->assertSee('Kalan');
    }

    /** @test */
    public function raporlarda_yaslandirma_kovalari_cikar(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->createOverdueInvoice($user);

        $this->get('/raporlar')
            ->assertOk()
            ->assertSee('Alacak Yaşlandırma')
            ->assertSee('1-30 gün gecikmiş')
            ->assertSee('300,00');
    }
}
