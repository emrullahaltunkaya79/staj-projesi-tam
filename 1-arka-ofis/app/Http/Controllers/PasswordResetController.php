<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/**
 * "Şifremi unuttum" akışı: e-posta ile sıfırlama bağlantısı + yeni şifre belirleme.
 * E-posta gönderimi .env'deki MAIL_MAILER'a göre çalışır (varsayılan: log).
 */
class PasswordResetController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'E-posta adresi zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.');
        }

        return back()
            ->withErrors(['email' => 'Bu e-posta adresiyle kayıtlı bir kullanıcı bulunamadı.'])
            ->onlyInput('email');
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', old('email')),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'E-posta adresi zorunludur.',
            'password.required' => 'Yeni şifre zorunludur.',
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'password.confirmed' => 'Şifre tekrarı eşleşmiyor.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                event(new PasswordReset($user));

                // Güvenlik: sıfırlamadan önce açılmış tüm oturumları geçersiz kıl
                \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('success', 'Şifreniz güncellendi, yeni şifrenizle giriş yapabilirsiniz.');
        }

        return back()->withErrors(['email' => 'Şifre sıfırlama bağlantısı geçersiz veya süresi dolmuş.']);
    }
}
