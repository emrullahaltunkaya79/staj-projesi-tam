<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
use App\Models\RecurringInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QualityAndPolishTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Policy + Global arama ----------

    /** @test */
    public function global_arama_sonuclari_sektor_ve_sirkete_gore_filtreli_doner(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Contact::create(['company_id' => $user->company_id, 'type' => 'musteri', 'name' => 'Arama Market']);
        Product::create(['company_id' => $user->company_id, 'name' => 'Arama Ürünü', 'unit' => 'adet', 'purchase_price' => 5, 'sale_price' => 9, 'stock_quantity' => 4]);

        // Başka şirketin verisi görünmemeli
        $other = User::factory()->create();
        Contact::create(['company_id' => $other->company_id, 'type' => 'musteri', 'name' => 'Arama Diğer Şirket']);

        $response = $this->getJson('/ara?q=Arama')->assertOk()->json();

        $names = array_merge(
            array_column($response['cariler'], 'title'),
            array_column($response['urunler'], 'title'),
        );

        $this->assertContains('Arama Market', $names);
        $this->assertContains('Arama Ürünü', $names);
        $this->assertNotContains('Arama Diğer Şirket', $names);
    }

    // ---------- Rate limiting ----------

    /** @test */
    public function cok_sayida_hatali_giris_denemesi_throttle_ile_engellenir(): void
    {
        User::factory()->create(['email' => 'kilitsiz@ornek.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/giris', ['email' => 'kilitsiz@ornek.com', 'password' => 'yanlisparola'])
                ->assertRedirect(); // hatalı giriş: geri yönlendirme
        }

        // 6. deneme: 429 Too Many Requests
        $this->post('/giris', ['email' => 'kilitsiz@ornek.com', 'password' => 'yanlisparola'])
            ->assertStatus(429);
    }

    // ---------- Şirket logosu ----------

    /** @test */
    public function yonetici_logo_yukleyebilir_ve_faturada_gorunur(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->put('/ayarlar', [
            'name' => 'Logolu Şirket',
            'tax_number' => '1234567890',
            'phone' => '0212 000 00 00',
            'address' => 'Test Mah.',
            'logo' => UploadedFile::fake()->image('logo.png', 120, 120),
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertNotNull($admin->company->fresh()->logo_path);
        Storage::disk('local')->assertExists($admin->company->fresh()->logo_path);

        // Standart kullanıcı ayar sayfasına giremez
        $this->post('/cikis');
        $personel = User::factory()->create(['role' => 'kullanici', 'company_id' => $admin->company_id]);
        $this->actingAs($personel);
        $this->get('/ayarlar')->assertForbidden();
    }

    // ---------- Tekrarlayan faturalar ----------

    private function seedRecurring(User $user, int $day): array
    {
        $contact = Contact::create(['company_id' => $user->company_id, 'type' => 'musteri', 'name' => 'Abone Cari']);
        $product = Product::create(['company_id' => $user->company_id, 'name' => 'Abonelik Ürünü', 'unit' => 'adet', 'purchase_price' => 50, 'sale_price' => 100, 'stock_quantity' => 50]);

        return [$contact, $product, RecurringInvoice::create([
            'company_id' => $user->company_id,
            'contact_id' => $contact->id,
            'title' => 'Aylık Abonelik',
            'day_of_month' => $day,
            'tax_rate' => 20,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
        ])];
    }

    /** @test */
    public function tekrarlayan_fatura_gunu_gelince_otomatik_kesilir(): void
    {
        $user = User::factory()->create();
        [$contact, $product, $rule] = $this->seedRecurring($user, today()->day); // bugün kesilmeli

        Artisan::call('tekrarlayan:faturalar-kes');

        $this->assertSame(1, $contact->invoices()->count());
        $this->assertSame(49, $product->fresh()->stock_quantity); // 50 - 1
        $this->assertTrue($rule->fresh()->last_run_at->isToday());

        // Aynı gün ikinci kez çalıştırılırsa tekrar kesmez
        Artisan::call('tekrarlayan:faturalar-kes');
        $this->assertSame(1, $contact->invoices()->count());
    }

    /** @test */
    public function gunu_gelmeyen_kural_kesilmez(): void
    {
        // Zamana bağışıklı: saati ayın ortasında dondur (test ayın 28-31'inde çalışırsa varsayım bozulur)
        \Illuminate\Support\Carbon::setTestNow(now()->setDay(15)->setTime(10, 0));
        $user = User::factory()->create();
        [$contact, , ] = $this->seedRecurring($user, 28); // ayın 28'i (dondurulan günden ileri bir gün)

        try {
            Artisan::call('tekrarlayan:faturalar-kes');

            $this->assertSame(0, $contact->invoices()->count());
        } finally {
            \Illuminate\Support\Carbon::setTestNow();
        }
    }

    /** @test */
    public function tekrarlayan_kural_olusturma_ekranindan_kaydedilir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [$contact, $product, ] = $this->seedRecurring($user, 1);
        $this->post('/cikis');

        $this->actingAs($user);
        $response = $this->post('/tekrarlayan', [
            'title' => 'Yeni Kural Test',
            'contact_id' => $contact->id,
            'day_of_month' => 15,
            'tax_rate' => 20,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100]],
        ]);

        $response->assertRedirect('/tekrarlayan')->assertSessionHas('success');
        $this->assertDatabaseHas('recurring_invoices', ['title' => 'Yeni Kural Test']);
    }
}
