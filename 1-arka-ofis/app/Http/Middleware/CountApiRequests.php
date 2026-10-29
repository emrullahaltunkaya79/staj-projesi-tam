<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Her API çağrısında token'ın istek sayacını artırır —
 * API Tokenlar sayfasındaki istatistik kartı buradan beslenir.
 */
class CountApiRequests
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Test ortamındaki geçici token'ın (TransientToken) id özelliği yoktur
        $token = $request->user()?->currentAccessToken();

        if ($token && isset($token->id)) {
            DB::table('personal_access_tokens')
                ->where('id', $token->id)
                ->increment('requests_count');
        }

        return $response;
    }
}
