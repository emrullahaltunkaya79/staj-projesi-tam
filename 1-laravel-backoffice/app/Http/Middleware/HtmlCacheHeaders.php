<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Başarılı HTML yanıtlarına kısa ömürlü tarayıcı önbelleği verir.
 * Böylece Turbo Drive sayfa geçişleri ve hover-prefetch anında çalışır.
 * Yalnızca private (tarayıcıya özel) — paylaşılan önbelleğe girmez.
 */
class HtmlCacheHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->isMethod('get')
            && $response->getStatusCode() === 200
            && str_contains($response->headers->get('Content-Type', ''), 'text/html')
            && ! str_starts_with($request->path(), 'api')) {
            $response->headers->set('Cache-Control', 'private, max-age=60');
        }

        return $response;
    }
}
