<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function sifre_sifirlama_baglantisi_gonderilir(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'stajyer@ornek.com']);

        $response = $this->from('/sifremi-unuttum')->post('/sifremi-unuttum', [
            'email' => 'stajyer@ornek.com',
        ]);

        $response->assertRedirect('/sifremi-unuttum')->assertSessionHas('success');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    /** @test */
    public function kayitsiz_eposta_hata_dondurur(): void
    {
        $this->post('/sifremi-unuttum', ['email' => 'yok@ornek.com'])
            ->assertSessionHasErrors('email');
    }

    /** @test */
    public function gecerli_token_ile_sifre_guncellenir(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->from('/sifre-sifirla/'.$token)
            ->post('/sifre-sifirla', [
                'token' => $token,
                'email' => $user->email,
                'password' => 'yeniparola1',
                'password_confirmation' => 'yeniparola1',
            ])
            ->assertRedirect('/giris')
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('yeniparola1', $user->fresh()->password));
    }

    /** @test */
    public function gecersiz_token_reddedilir(): void
    {
        $user = User::factory()->create();

        $this->post('/sifre-sifirla', [
            'token' => 'gecersiz-token',
            'email' => $user->email,
            'password' => 'yeniparola1',
            'password_confirmation' => 'yeniparola1',
        ])->assertSessionHasErrors('email');

        $this->assertFalse(Hash::check('yeniparola1', $user->fresh()->password));
    }
}
