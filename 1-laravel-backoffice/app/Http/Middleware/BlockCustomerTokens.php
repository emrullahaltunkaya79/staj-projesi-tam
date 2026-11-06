<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Müşteri (rol: musteri) token'ları yalnızca kendi hesap uçlarına erişebilir.
 * Mağaza sunucusu ve panel kullanıcıları (admin/kullanıcı) tüm uçları kullanır.
 */
class BlockCustomerTokens
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role === 'musteri') {
            abort(403, 'Bu işlem için yetkiniz yok.');
        }

        return $next($request);
    }
}
