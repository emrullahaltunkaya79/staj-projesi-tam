<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sadece "admin" rolüne sahip kullanıcıların geçebildiği middleware.
 * (routes/web.php içinde 'admin' takma adıyla kullanılır)
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAdmin()) {
            abort(403, 'Bu sayfa için yönetici yetkisi gerekiyor.');
        }

        return $next($request);
    }
}
