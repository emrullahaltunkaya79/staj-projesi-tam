<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'company_name.required' => 'Şirket adı zorunludur.',
            'name.required' => 'Ad Soyad zorunludur.',
            'email.required' => 'E-posta zorunludur.',
            'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
            'password.required' => 'Şifre zorunludur.',
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'password.confirmed' => 'Şifre tekrarı eşleşmiyor.',
        ]);

        // Şirket ve kullanıcı aynı transaction'da oluşturulur:
        // kullanıcıyı şirket olmadan bırakma ihtimali yoktur.
        // Şirketi kaydeden ilk kullanıcı otomatik "admin" rolü alır.
        $user = DB::transaction(function () use ($validated) {
            $company = Company::create([
                'name' => $validated['company_name'],
            ]);

            return User::create([
                'company_id' => $company->id,
                'role' => 'admin',
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        // E-posta doğrulama bağlantısı gönder (doğrulama zorunlu değildir; panelde hatırlatma gösterilir)
        $user->sendEmailVerificationNotification();

        return redirect()->route('dashboard')
            ->with('success', "Hoş geldiniz! {$validated['company_name']} hesabınız oluşturuldu.");
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'E-posta zorunludur.',
            'password.required' => 'Şifre zorunludur.',
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withErrors(['email' => 'E-posta veya şifre hatalı.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
