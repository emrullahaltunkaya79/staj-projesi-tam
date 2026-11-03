<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FinalGapsTest extends TestCase
{
    use RefreshDatabase;

    private function quoteData(User $user): array
    {
        $contact = Contact::create(['company_id' => $user->company_id, 'type' => 'musteri', 'name' => 'Test Cari']);
        $product = Product::create(['company_id' => $user->company_id, 'name' => 'Test Ürün', 'unit' => 'adet', 'purchase_price' => 100, 'sale_price' => 200, 'stock_quantity' => 1]);

        return [$contact, $product];
    }

    /** @test */
    public function stok_yetersiz_teklif_donusumu_rollback_eder_ve_teklif_onayli_kalir(): void
    {
        $user = User::factory()->create();
        [$contact, $product] = $this->quoteData($user); // stok: 1
        $service = app(QuoteService::class);

        $quote = $service->createQuote($user->company_id, [
            'contact_id' => $contact->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 200]], // stok yetmez
        ]);
        $service->changeStatus($quote, 'onaylandi', $user->company_id);

        try {
            $service->convertToInvoice($quote->fresh(), $user->company_id);
            $this->fail('Dönüşüm ValidationException fırlatmalıydı.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // beklenen
        }

        // Rollback: fatura yok, teklif onaylı, stok değişmedi
        $this->assertSame(0, $contact->invoices()->count());
        $this->assertSame(1, $product->fresh()->stock_quantity);
        $quote = $quote->fresh();
        $this->assertSame('onaylandi', $quote->status);
        $this->assertNull($quote->converted_invoice_id);
    }

    /** @test */
    public function baska_sirketin_urunuyle_teklif_acilamaz(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $other = User::factory()->create();
        $otherProduct = Product::create(['company_id' => $other->company_id, 'name' => 'Rakip Ürün', 'unit' => 'adet', 'purchase_price' => 10, 'sale_price' => 20, 'stock_quantity' => 10]);
        [$ownContact, ] = $this->quoteData($user);

        $this->post('/teklifler', [
            'contact_id' => $ownContact->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 0,
            'items' => [['product_id' => $otherProduct->id, 'quantity' => 1, 'unit_price' => 20]],
        ])->assertSessionHasErrors('items');
    }

    /** @test */
    public function admin_olmayan_kullanici_fiyat_araligi_disina_cikamaz(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personel = User::factory()->create(['role' => 'kullanici', 'company_id' => $admin->company_id]);
        [$contact, $product] = $this->quoteData($admin); // fiyat aralığı 100–200

        // Personel 1 ₺'ye satamaz
        $this->actingAs($personel);
        $this->post('/faturalar', [
            'contact_id' => $contact->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1]],
        ])->assertSessionHasErrors('items');

        // Yönetici aynı fiyatı kesebilir
        $this->actingAs($admin);
        $this->post('/faturalar', [
            'contact_id' => $contact->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1]],
        ])->assertRedirect();

        $this->assertSame(1, $contact->invoices()->count());
    }

    /** @test */
    public function kullanici_sifresini_degistirebilir(): void
    {
        $user = User::factory()->create(['password' => Hash::make('eskisifre1')]);
        $this->actingAs($user);

        $this->put('/sifre-degistir', [
            'current_password' => 'eskisifre1',
            'password' => 'yenisifre1',
            'password_confirmation' => 'yenisifre1',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue(Hash::check('yenisifre1', $user->fresh()->password));

        // Yeni şifreyle giriş yapılabilir, eskisiyle yapılamaz
        $this->post('/giris', ['email' => $user->email, 'password' => 'yenisifre1'])->assertRedirect('/panel');
        $this->post('/cikis');
        $this->post('/giris', ['email' => $user->email, 'password' => 'eskisifre1'])->assertSessionHasErrors('email');
    }

    /** @test */
    public function denetim_kaydi_olusur_ve_silinen_kayit_geri_yuklenebilir(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $product = Product::create(['company_id' => $admin->company_id, 'name' => 'Denetim Ürün', 'unit' => 'adet', 'purchase_price' => 10, 'sale_price' => 20, 'stock_quantity' => 5]);

        // created + updated kayıtları
        $product->update(['sale_price' => 25]);
        $this->assertSame(2, ActivityLog::where('model_type', 'Product')->where('model_id', $product->id)->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'updated', 'user_id' => $admin->id]);

        // Silme: soft delete → geri dönüşüm kutusunda
        $this->delete("/urunler/{$product->id}")->assertRedirect();
        $this->assertNotNull($product->fresh()->deleted_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'deleted', 'model_type' => 'Product']);

        // Geri yükleme
        $this->post("/geri-donusum/product/{$product->id}/geri-yukle")->assertRedirect();
        $this->assertNull($product->fresh()->deleted_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'restored', 'model_type' => 'Product']);
    }

    /** @test */
    public function kayitta_eposta_dogrulama_baglantisi_gonderilir(): void
    {
        Notification::fake();

        $this->post('/kayit', [
            'company_name' => 'Doğrulama Şirketi',
            'name' => 'Doğrulama Kullanıcı',
            'email' => 'dogrulama@ornek.com',
            'password' => 'parola123',
            'password_confirmation' => 'parola123',
        ])->assertRedirect('/panel');

        $user = User::where('email', 'dogrulama@ornek.com')->first();
        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
    }

    /** @test */
    public function acik_olmayan_teklif_duzenlenemez(): void
    {
        $user = User::factory()->create();
        [$contact, $product] = $this->quoteData($user);
        $service = app(QuoteService::class);

        $quote = $service->createQuote($user->company_id, [
            'contact_id' => $contact->id, 'issue_date' => now()->toDateString(), 'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 200]],
        ]);

        // Açıkken düzenlenebilir
        $service->updateQuote($quote, $user->company_id, [
            'contact_id' => $contact->id, 'issue_date' => now()->toDateString(), 'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 150]],
        ]);
        $this->assertEquals(300.0, (float) $quote->fresh()->total);

        // Onaylandıktan sonra düzenlenemez
        $service->changeStatus($quote, 'onaylandi', $user->company_id);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->updateQuote($quote->fresh(), $user->company_id, [
            'contact_id' => $contact->id, 'issue_date' => now()->toDateString(), 'tax_rate' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 200]],
        ]);
    }
}
