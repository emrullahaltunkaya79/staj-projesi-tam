<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Kullanıcı profili: e-posta doğrulama ve giriş yapmış kullanıcı için şifre değiştirme.
 */
class ProfileController extends Controller
{
    /**
     * Doğrulama bağlantısını işler (signed URL).
     */
    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $user = \App\Models\User::findOrFail($id);

        if (! hash_equals(sha1($user->email), $hash)) {
            abort(403, 'Geçersiz doğrulama bağlantısı.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect()->route('login')->with('success', 'E-posta adresiniz doğrulandı, teşekkürler!');
    }

    /**
     * Doğrulama e-postasını yeniden gönderir.
     */
    public function resendVerification(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return back()->with('success', 'E-posta adresiniz zaten doğrulanmış.');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('success', 'Doğrulama e-postası tekrar gönderildi.');
    }

    public function passwordForm()
    {
        return view('profile.password');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Mevcut şifre zorunludur.',
            'password.required' => 'Yeni şifre zorunludur.',
            'password.min' => 'Yeni şifre en az 8 karakter olmalıdır.',
            'password.confirmed' => 'Yeni şifre tekrarı eşleşmiyor.',
        ]);

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            return back()->withErrors(['current_password' => 'Mevcut şifreniz hatalı.']);
        }

        $request->user()->update(['password' => Hash::make($validated['password'])]);

        // Diğer cihazlardaki oturumları geçersiz kıl (mevcut oturum açık kalır)
        DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return redirect()->route('sifre-degistir')
            ->with('success', 'Şifreniz güncellendi; diğer cihazlardaki oturumlar kapatıldı.');
    }
}
