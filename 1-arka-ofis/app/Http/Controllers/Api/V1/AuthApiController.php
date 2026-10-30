<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    /** 6 haneli sayısal doğrulama/sıfırlama kodu üretir. */
    private function kodUret(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Demo ortamında mail log'a yazılır; kod ayrıca yanıtta döner ki akış test edilebilsin.
     * Üretimde data.demo_kod alanı kaldırılmalıdır.
     */
    private function kodGonder(string $email, string $kod, string $konu, string $mesaj): void
    {
        Mail::raw($mesaj, function ($m) use ($email, $konu) {
            $m->to($email)->subject($konu);
        });
    }

    /** Demo tek-şirket kurulumu: ilk şirketi bulur, yoksa oluşturur. */
    private function sirketId(): int
    {
        return Company::query()->min('id')
            ?? Company::create(['name' => 'HepsiBirarada'])->id;
    }

    /**
     * E-ticaret müşteri kaydı — User (rol: musteri) + Contact eşleşmesi.
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'name.required' => 'Ad soyad zorunludur.',
            'email.unique' => 'Bu e-posta zaten kayıtlı — giriş yapın.',
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
        ]);

        $companyId = $this->sirketId();
        $kod = $this->kodUret();

        $user = User::create([
            'company_id' => $companyId,
            'role' => 'musteri',
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'verification_token' => Hash::make($kod),
        ]);

        $this->kodGonder(
            $validated['email'], $kod,
            'HepsiBirarada — E-posta Doğrulama',
            "Hoş geldiniz! E-posta doğrulama kodunuz: {$kod}"
        );

        // İlgili Contact kaydını bul/oluştur (idempotent)
        $contact = Contact::where('company_id', $companyId)
            ->where('email', $validated['email'])
            ->first();

        if (! $contact) {
            $contact = Contact::create([
                'company_id' => $companyId,
                'type' => 'musteri',
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);
        }

        $token = $user->createToken('eticaret-musteri', ['ecommerce'])->plainTextToken;

        return response()->json([
            'message' => 'Hoş geldiniz! Hesabınız oluşturuldu.',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'contact_id' => $contact->id,
                    'email_verified' => false,
                ],
                // Demo: mail log'a gittiği için kodu yanıtta da veriyoruz
                'dogrulama_kodu' => $kod,
            ],
        ], 201);
    }

    /**
     * E-ticaret müşteri girişi.
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'E-posta zorunludur.',
            'password.required' => 'Şifre zorunludur.',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'E-posta veya şifre hatalı.',
            ]);
        }

        // Contact eşleşmesi (yoksa oluştur)
        $contact = Contact::where('company_id', $user->company_id)
            ->where('email', $user->email)
            ->first();

        if (! $contact) {
            $contact = Contact::create([
                'company_id' => $user->company_id,
                'type' => 'musteri',
                'name' => $user->name,
                'email' => $user->email,
            ]);
        }

        $token = $user->createToken('eticaret-musteri', ['ecommerce'])->plainTextToken;

        return response()->json([
            'message' => 'Giriş başarılı.',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'contact_id' => $contact->id,
                    'email_verified' => $user->email_verified_at !== null,
                ],
            ],
        ]);
    }

    /**
     * Oturum açmış müşteri bilgisi.
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $contact = Contact::where('company_id', $user->company_id)
            ->where('email', $user->email)
            ->first();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'contact_id' => $contact?->id,
                'email_verified' => $user->email_verified_at !== null,
            ],
        ]);
    }

    /**
     * Çıkış — token iptali.
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Çıkış yapıldı.']);
    }

    /**
     * Şifre sıfırlama kodu isteği.
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], ['email.required' => 'E-posta zorunludur.']);

        $user = User::where('email', $validated['email'])->first();

        // Kullanıcı yoksa da başarı yanıtı dön (hesap varlığını ele vermeme)
        if (! $user) {
            return response()->json([
                'message' => 'Şifre sıfırlama kodu gönderildi (e-postanızda kontrol edin).',
            ]);
        }

        $kod = $this->kodUret();

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($kod), 'created_at' => now()],
        );

        $this->kodGonder(
            $user->email, $kod,
            'HepsiBirarada — Şifre Sıfırlama',
            "Şifre sıfırlama kodunuz: {$kod} (60 dakika geçerli)"
        );

        return response()->json([
            'message' => 'Şifre sıfırlama kodu gönderildi.',
            // Demo: mail log'a gittiği için kodu yanıtta da veriyoruz
            'data' => ['dogrulama_kodu' => $kod],
        ]);
    }

    /**
     * Kod ile yeni şifre belirleme.
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'code.digits' => 'Kod 6 haneli olmalıdır.',
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'password.confirmed' => 'Şifreler eşleşmiyor.',
        ]);

        $kayit = DB::table('password_reset_tokens')->where('email', $validated['email'])->first();

        if (
            ! $kayit
            || ! Hash::check($validated['code'], $kayit->token)
            || strtotime($kayit->created_at) < now()->subMinutes(60)->getTimestamp()
        ) {
            throw ValidationException::withMessages([
                'code' => 'Kod geçersiz veya süresi dolmuş.',
            ]);
        }

        $user = User::where('email', $validated['email'])->firstOrFail();
        $user->update(['password' => Hash::make($validated['password'])]);

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        // Yeni şifreyle eski oturumların hepsini geçersiz kıl
        $user->tokens()->delete();

        return response()->json(['message' => 'Şifreniz güncellendi. Yeni şifrenizle giriş yapabilirsiniz.']);
    }

    /**
     * Kayıt sırasında gönderilen kodla e-postayı doğrular.
     * POST /api/v1/auth/verify-email
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ], ['code.digits' => 'Kod 6 haneli olmalıdır.']);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! $user->verification_token || ! Hash::check($validated['code'], $user->verification_token)) {
            throw ValidationException::withMessages([
                'code' => 'Doğrulama kodu geçersiz.',
            ]);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'verification_token' => null,
        ])->save();

        return response()->json(['message' => 'E-postanız doğrulandı.']);
    }

    /**
     * Doğrulama kodunu yeniden gönderir.
     * POST /api/v1/auth/resend-verification
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], ['email.required' => 'E-posta zorunludur.']);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || $user->email_verified_at) {
            return response()->json(['message' => 'Hesap zaten doğrulanmış veya bulunamadı.']);
        }

        $kod = $this->kodUret();
        $user->forceFill(['verification_token' => Hash::make($kod)])->save();

        $this->kodGonder(
            $user->email, $kod,
            'HepsiBirarada — E-posta Doğrulama',
            "E-posta doğrulama kodunuz: {$kod}"
        );

        return response()->json([
            'message' => 'Doğrulama kodu tekrar gönderildi.',
            // Demo: mail log'a gittiği için kodu yanıtta da veriyoruz
            'data' => ['dogrulama_kodu' => $kod],
        ]);
    }
}
