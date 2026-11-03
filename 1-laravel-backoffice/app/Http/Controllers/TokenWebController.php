<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * API token yönetimi (web panel): e-ticaret entegrasyonu için token
 * oluşturma/iptal. Token değeri yalnızca oluşturma anında gösterilir.
 */
class TokenWebController extends Controller
{
    public function index(Request $request)
    {
        $tokens = $request->user()->tokens()->get(['id', 'name', 'last_used_at', 'created_at']);
        $plainToken = session('plain_token'); // oluşturma anında bir kez gösterilir

        return view('api-tokens.index', compact('tokens', 'plainToken'));
    }

    public function store(Request $request)
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

        return redirect()->route('api-tokenlar.index')
            ->with('plain_token', $token->plainTextToken)
            ->with('success', 'Token oluşturuldu. Değer yalnızca bir kez gösteriliyor — kopyalayın.');
    }

    public function destroy(Request $request, int $tokenId)
    {
        $deleted = $request->user()->tokens()->where('id', $tokenId)->delete();

        return back()->with(
            $deleted ? 'success' : 'error',
            $deleted ? 'Token iptal edildi.' : 'Token bulunamadı.'
        );
    }

    /**
     * Tüm e-ticaret uçlarını içeren Postman koleksiyonu (v2.1) indirir.
     */
    public function postman(Request $request)
    {
        $base = $request->getSchemeAndHttpHost();

        $item = fn (string $name, string $method, string $path, ?array $body = null) => [
            'name' => $name,
            'request' => array_filter([
                'method' => $method,
                'header' => [
                    ['key' => 'Authorization', 'value' => 'Bearer {{token}}'],
                    ['key' => 'Accept', 'value' => 'application/json'],
                    ['key' => 'Content-Type', 'value' => 'application/json'],
                ],
                'url' => ['raw' => $base . $path, 'host' => [$base], 'path' => explode('/', ltrim($path, '/'))],
                'body' => $body ? ['mode' => 'raw', 'raw' => json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)] : null,
            ]),
        ];

        $collection = [
            'info' => [
                'name' => 'Ön Muhasebe E-Ticaret API v1',
                'description' => 'Sipariş ve stok entegrasyonu. Token: Authorization: Bearer <token>. Hız limiti: 60 istek/dk.',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'variable' => [['key' => 'token', 'value' => 'API_TOKEN_BURAYA']],
            'item' => [
                $item('Ürün listesi', 'GET', '/api/v1/products'),
                $item('Ürün detayı', 'GET', '/api/v1/products/1'),
                $item('Canlı stok sorgusu', 'GET', '/api/v1/stock?product_ids[]=1'),
                $item('Müşteri oluştur (idempotent)', 'POST', '/api/v1/customers', ['name' => 'Örnek Müşteri', 'email' => 'musteri@ornek.com', 'type' => 'musteri']),
                $item('Sipariş oluştur (stok rezerve)', 'POST', '/api/v1/orders', [
                    'contact_id' => 1,
                    'tax_rate' => 20,
                    'items' => [['product_id' => 1, 'quantity' => 2]],
                    'shipping_address' => 'Örnek Mah. No:1 İstanbul',
                    'external_ref' => 'SIP-2026-001',
                ]),
                $item('Sipariş durumu', 'GET', '/api/v1/orders/ORD-000001'),
                $item('Ödeme (CSPR)', 'POST', '/api/v1/orders/ORD-000001/pay', ['payment_method' => 'cspr']),
                $item('Kargo bilgisi', 'POST', '/api/v1/orders/ORD-000001/shipping', ['carrier' => 'aras', 'tracking_no' => 'ARAS123456789']),
                $item('Sipariş iptal', 'POST', '/api/v1/orders/ORD-000001/cancel', ['reason' => 'müşteri talebi']),
                $item('CSPR ödeme durumu', 'GET', '/api/v1/cspr-payments/ORD-000001-abc123'),
                $item('Token listesi', 'GET', '/api/v1/tokens'),
            ],
        ];

        return response()->json($collection)
            ->header('Content-Disposition', 'attachment; filename="eticaret-api-v1.postman_collection.json"');
    }
}
