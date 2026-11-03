<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
use App\Models\RecurringInvoice;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function takvim_geciken_yaklasan_ve_tekrarlayan_olaylari_gosterir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contact = Contact::create(['company_id' => $user->company_id, 'type' => 'musteri', 'name' => 'Takvim Cari']);
        $product = Product::create(['company_id' => $user->company_id, 'name' => 'Takvim Ürün', 'unit' => 'adet', 'purchase_price' => 50, 'sale_price' => 100, 'stock_quantity' => 50]);

        // Geciken vade (10 gün önce)
        app(InvoiceService::class)->createSaleInvoice($user->company_id, [
            'contact_id' => $contact->id,
            'issue_date' => now()->subDays(40)->toDateString(),
            'due_date' => now()->subDays(10)->toDateString(),
            'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100]],
        ]);

        // Yaklaşan vade (5 gün sonra)
        app(InvoiceService::class)->createSaleInvoice($user->company_id, [
            'contact_id' => $contact->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
        ]);

        // Aktif tekrarlayan kural (kesim günü işaretlenmeli)
        RecurringInvoice::create([
            'company_id' => $user->company_id,
            'contact_id' => $contact->id,
            'title' => 'Aylık Bakım',
            'day_of_month' => min(15, now()->daysInMonth),
            'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
        ]);

        $response = $this->get('/takvim');
        $response->assertOk()
            ->assertSee('Takvim Cari')
            ->assertSee('gecikti')
            ->assertSee('5 gün');

        // Tekrarlayan kuralın adı yalnızca gün seçilince görünür
        $this->get('/takvim?gun='.now()->day(min(15, now()->daysInMonth))->format('Y-m-d'))
            ->assertOk();
    }

    /** @test */
    public function takvim_gun_secilince_o_gunun_odemeleri_filtrelenir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contact = Contact::create(['company_id' => $user->company_id, 'type' => 'musteri', 'name' => 'Filtre Cari']);
        $product = Product::create(['company_id' => $user->company_id, 'name' => 'Filtre Ürün', 'unit' => 'adet', 'purchase_price' => 10, 'sale_price' => 50, 'stock_quantity' => 10]);

        app(InvoiceService::class)->createSaleInvoice($user->company_id, [
            'contact_id' => $contact->id,
            'issue_date' => now()->subDays(2)->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
            'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50]],
        ]);

        $day = now()->addDays(3)->toDateString();

        $this->get('/takvim?gun='.$day)
            ->assertOk()
            ->assertSee('Ödeme Takvimi');
    }
}
