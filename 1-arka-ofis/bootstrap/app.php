<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        // API çağrılarında token istek sayacını artır
        $middleware->api()->append(\App\Http\Middleware\CountApiRequests::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // PHP'in post_max_size sınırını aşan yüklemelerde dostça mesaj göster
        $exceptions->render(function (\Illuminate\Foundation\Http\Exceptions\PostTooLargeException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Yüklenen dosya sunucu gövde sınırını aşıyor (en fazla '.ini_get('post_max_size').').',
                ], 413);
            }

            return back()->with('error', 'Yüklenen dosya sunucu gövde sınırını aşıyor (en fazla '
                .ini_get('post_max_size').'). Lütfen daha küçük bir dosya deneyin.');
        });
    })->create();
