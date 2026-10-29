<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
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

        $user = User::create([
            'company_id' => 1,
            'role' => 'musteri',
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        // İlgili Contact kaydını bul/oluştur (idempotent)
        $contact = Contact::where('company_id', 1)
            ->where('email', $validated['email'])
            ->first();

        if (! $contact) {
            $contact = Contact::create([
                'company_id' => 1,
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
                ],
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
}
