<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * API token yönetimi: e-ticaret entegrasyonu için token oluştur/iptal.
 * Web panelinden kullanılır (kullanıcı giriş yapmış olmalı).
 */
class TokenApiController extends Controller
{
    /**
     * Yeni API token oluşturur (şifre doğrulaması gerekir).
     * POST /api-tokens
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'password' => ['required'],
        ], [
            'name.required' => 'Token adı zorunludur.',
            'password.required' => 'Onay için şifrenizi girin.',
        ]);

        if (! Hash::check($validated['password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'Şifreniz hatalı.',
            ]);
        }

        $token = $request->user()->createToken($validated['name'], ['ecommerce']);

        return response()->json([
            'message' => 'Token oluşturuldu. Bu değeri bir kez gösteriyoruz — güvenli yerde saklayın.',
            'token' => $token->plainTextToken,
        ], 201);
    }

    /**
     * Token iptal eder.
     * DELETE /api-tokens/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = $request->user()->tokens()->where('id', $id)->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Token bulunamadı.'], 404);
        }

        return response()->json(['message' => 'Token iptal edildi.']);
    }
}
