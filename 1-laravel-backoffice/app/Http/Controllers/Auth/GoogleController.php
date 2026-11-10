<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Google ile giriş (OAuth 2.0) — yalnızca mağaza MÜŞTERİ hesapları için.
 * Akış: mağaza → /auth/google/redirect → Google → /auth/google/callback →
 * tek kullanımlık kod → mağaza sunucusu /auth/google/exchange ile token'ı alır.
 */
class GoogleController extends Controller
{
    public function redirect(Request $request)
    {
        $store = $request->query('store') === 'livewire' ? 'livewire' : 'next';

        $clientId = config('services.google.client_id');
        if (! $clientId) {
            // Kimlik bilgileri henüz girilmemiş: .env'e GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET ekleyin
            return redirect()->away($this->storeUrl($store, ['googleHata' => 'yapilandirma']));
        }

        $state = Str::random(24);
        session(['google_state' => $state, 'google_store' => $store]);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request)
    {
        $store = (string) session('google_store', 'next');
        $fail = fn (string $kod) => redirect()->away($this->storeUrl($store, ['googleHata' => $kod]));

        if ($request->query('error')) {
            return $fail('iptal');
        }

        if (! $request->hasSession() || $request->session()->get('google_state') !== $request->query('state')) {
            return $fail('guvenlik');
        }
        $request->session()->forget('google_state');

        $code = $request->query('code');
        if (! $code) {
            return $fail('kod');
        }

        $tokenYaniti = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect'),
            'grant_type' => 'authorization_code',
        ]);

        $accessToken = $tokenYaniti->json('access_token');
        if (! $tokenYaniti->successful() || ! $accessToken) {
            return $fail('token');
        }

        $profil = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo')->json();

        $email = $profil['email'] ?? null;
        if (! $email || empty($profil['email_verified'])) {
            return $fail('dogrulama');
        }

        $name = $profil['name'] ?? explode('@', $email)[0];

        $user = User::where('email', $email)->first();

        // Google girişi yalnızca mağaza müşteri hesapları içindir; satıcı/panel hesabı e-postasıyla müşteri oturumu açılmaz
        if ($user && $user->role !== 'musteri') {
            return $fail('satici');
        }

        if (! $user) {
            $user = User::create([
                'company_id' => config('services.google.company_id') ?? 1,
                'role' => 'musteri',
                'name' => $name,
                'email' => $email,
                'password' => Str::random(32), // müşteri şifresiz başlar; şifre belirleme akışı "Hesabım"dan
                'email_verified_at' => now(),
            ]);

            Contact::firstOrCreate(
                ['company_id' => $user->company_id, 'email' => $email],
                ['type' => 'musteri', 'name' => $name]
            );
        } else {
            $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();
        }

        $token = $user->createToken('eticaret-musteri', ['ecommerce'])->plainTextToken;

        // Tek kullanımlık kod: mağaza bu kodu token ile değiştirir (token URL'de görünmez)
        $kod = Str::random(48);
        Cache::put('google-kod:'.hash('sha256', $kod), [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'musteri',
                'contact_id' => $user->contact?->id,
                'email_verified' => true,
            ],
        ], now()->addSeconds(90));

        return redirect()->away($this->storeUrl($store, ['giris' => $kod]));
    }

    private function storeUrl(string $store, array $params = []): string
    {
        $base = $store === 'livewire'
            ? config('services.google.store_livewire', 'http://127.0.0.1:3000')
            : config('services.google.store_next', 'http://127.0.0.1:3001');

        return $base.(! empty($params) ? '/?'.http_build_query($params) : '/');
    }
}
