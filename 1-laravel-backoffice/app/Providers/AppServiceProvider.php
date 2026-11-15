<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 2FA kod doğrulama: e-posta başına sert hız limiti (brute force karşı)
        RateLimiter::for('iki-adim', function (Request $request) {
            return Limit::perMinute(5)
                ->by('iki-adim|' . strtolower((string) $request->input('email', $request->ip())));
        });

        // 2FA kod gönderimi (login): e-posta başına dakikada 1 — kod spam'ini önler
        RateLimiter::for('iki-adim-gonder', function (Request $request) {
            return Limit::perMinute(1)
                ->by('iki-adim-gonder|' . strtolower((string) $request->input('email', $request->ip())));
        });

        // Şifre sıfırlama e-postasını Türkçeleştir
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            $url = url(route('password.reset', ['token' => $token, 'email' => $notifiable->email], false));

            return (new MailMessage)
                ->subject('Şifre Sıfırlama Bağlantısı')
                ->greeting("Merhaba {$notifiable->name},")
                ->line('Hesabınız için bir şifre sıfırlama isteği aldık.')
                ->action('Şifreyi Sıfırla', $url)
                ->line('Bu bağlantı 60 dakika sonra geçersiz olacaktır.')
                ->line('İsteği siz yapmadıysanız bu e-postayı güvenle yok sayabilirsiniz.')
                ->salutation('İyi çalışmalar, '.config('app.name'));
        });

        // Brute-force koruması: dakikada 5 giriş denemesi (e-posta + IP bazında)
        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(5)->by($request->input('email').'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        // Şifre sıfırlama bağlantısı: dakikada 3 istek
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));
    }
}
