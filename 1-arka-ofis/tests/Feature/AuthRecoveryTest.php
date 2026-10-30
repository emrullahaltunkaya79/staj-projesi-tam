<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private function musteriOlustur(string $email = 'musteri@ornek.com', string $sifre = 'EskiSifre123'): User
    {
        return User::create([
            'company_id' => Company::factory()->create()->id,
            'role' => 'musteri',
            'name' => 'Test Müşteri',
            'email' => $email,
            'password' => Hash::make($sifre),
        ]);
    }

    public function test_kayit_dogrulama_kodu_donderir_ve_email_dogrulanir(): void
    {
        $yanit = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ayşe Yılmaz',
            'email' => 'ayse@ornek.com',
            'password' => 'Sifre12345',
        ])->assertCreated();

        $yanit->assertJsonPath('data.user.email_verified', false);
        $kod = $yanit->json('data.dogrulama_kodu');
        $this->assertEquals(6, strlen((string) $kod));

        // Yanlış kod reddedilir
        $this->postJson('/api/v1/auth/verify-email', [
            'email' => 'ayse@ornek.com',
            'code' => '000000',
        ])->assertUnprocessable();

        // Doğru kod doğrular
        $this->postJson('/api/v1/auth/verify-email', [
            'email' => 'ayse@ornek.com',
            'code' => (string) $kod,
        ])->assertOk();

        $this->assertNotNull(User::where('email', 'ayse@ornek.com')->value('email_verified_at'));
    }

    public function test_sifre_sifirlama_akisi_tam_calisir(): void
    {
        $user = $this->musteriOlustur();

        $yanit = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        $kod = $yanit->json('data.dogrulama_kodu');
        $this->assertEquals(6, strlen((string) $kod));

        // Yanlış kodla sıfırlama reddedilir
        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'code' => '111111',
            'password' => 'YeniSifre456',
            'password_confirmation' => 'YeniSifre456',
        ])->assertUnprocessable();

        // Doğru kodla şifre değişir
        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'code' => (string) $kod,
            'password' => 'YeniSifre456',
            'password_confirmation' => 'YeniSifre456',
        ])->assertOk();

        // Kod tek kullanımlıktır — ikinci deneme reddedilir
        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'code' => (string) $kod,
            'password' => 'BaskaSifre789',
            'password_confirmation' => 'BaskaSifre789',
        ])->assertUnprocessable();

        $this->assertEquals(0, DB::table('password_reset_tokens')->where('email', $user->email)->count());

        // Yeni şifreyle giriş yapılır
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'YeniSifre456',
        ])->assertOk();
    }

    public function test_olmayan_eposta_ile_kod_istegi_hesap_sizligini_ele_vermez(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'yok@ornek.com',
        ])->assertOk()->assertJsonMissingPath('data');
    }
}
