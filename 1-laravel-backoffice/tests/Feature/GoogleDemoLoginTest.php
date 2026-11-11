<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Google ile giriş DEMO modu: OAuth kimliği yapılandırılmamışken e-postayla
 * doğrulanmış müşteri hesabı; yapılandırılınca kapanır; satıcı e-postaları reddedilir.
 */
class GoogleDemoLoginTest extends TestCase
{
    use RefreshDatabase;

    private function sirketKur(): void
    {
        Company::create(['name' => 'Test Şirketi', 'tax_number' => '1234567890']);
    }

    /** @test */
    public function demo_girisi_dogrulanmis_musteri_hesabi_olusturur(): void
    {
        $this->sirketKur();
        $cevap = $this->postJson('/api/v1/auth/google/demo-login', [
            'email' => 'demo@google-musteri.com',
            'name' => 'Demo Kullanıcı',
        ]);

        $cevap->assertOk()
            ->assertJsonStructure(['message', 'data' => ['token', 'user' => ['id', 'name', 'email', 'role', 'email_verified']]]);

        $kullanici = User::where('email', 'demo@google-musteri.com')->first();
        $this->assertNotNull($kullanici);
        $this->assertSame('musteri', $kullanici->role);
        $this->assertNotNull($kullanici->email_verified_at);
        $this->assertNotNull($kullanici->contact, 'Müşteri carisi otomatik oluşturulmalıdır.');
    }

    /** @test */
    public function ayni_e_posta_ikinci_giriste_yeni_hesap_acmaz(): void
    {
        $this->sirketKur();
        $this->postJson('/api/v1/auth/google/demo-login', ['email' => 'tekrar@google-musteri.com'])->assertOk();
        $this->postJson('/api/v1/auth/google/demo-login', ['email' => 'tekrar@google-musteri.com'])->assertOk();

        $this->assertSame(1, User::where('email', 'tekrar@google-musteri.com')->count());
    }

    /** @test */
    public function satici_e_postasiyla_demo_girisi_reddedilir(): void
    {
        $this->sirketKur();
        // roller enum'u: admin | kullanici | musteri — musteri dışı her rol reddedilir
        User::factory()->create(['email' => 'personel@hesap.com', 'role' => 'kullanici']);

        $this->postJson('/api/v1/auth/google/demo-login', ['email' => 'personel@hesap.com'])
            ->assertStatus(403);

        User::factory()->create(['email' => 'yonetici@hesap.com', 'role' => 'admin']);
        $this->postJson('/api/v1/auth/google/demo-login', ['email' => 'yonetici@hesap.com'])
            ->assertStatus(403);
    }

    /** @test */
    public function gercek_oauth_yapilandirilinca_demo_girisi_kapanir(): void
    {
        $this->sirketKur();
        config(['services.google.client_id' => 'gercek-client-id.apps.googleusercontent.com']);

        $this->postJson('/api/v1/auth/google/demo-login', ['email' => 'biri@ornek.com'])
            ->assertStatus(403)
            ->assertJsonFragment(['message' => 'Gerçek Google girişi aktif — demo girişi kapalı.']);

        $this->assertSame(0, User::where('email', 'biri@ornek.com')->count());
    }

    /** @test */
    public function durum_uci_yapilandirmaya_gore_yanit_verir(): void
    {
        $this->getJson('/api/v1/auth/google/durum')
            ->assertOk()
            ->assertJsonFragment(['yapilandirildi' => false]);

        config(['services.google.client_id' => 'birsey.apps.googleusercontent.com']);
        $this->getJson('/api/v1/auth/google/durum')
            ->assertOk()
            ->assertJsonFragment(['yapilandirildi' => true]);
    }
}
