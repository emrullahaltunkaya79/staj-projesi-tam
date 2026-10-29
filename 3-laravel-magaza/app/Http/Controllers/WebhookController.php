<?php

namespace App\Http\Controllers;

use App\Models\WebhookEvent;
use Illuminate\Http\Request;

/**
 * Arka ofisten gelen webhook'ları alır (HMAC imzalı).
 *
 * stock.changed / product.upserted / product.deleted → katalog önbelleği tazelenir;
 * order.* olayları kayda geçer (sipariş durumu sorguda canlı okunur).
 */
class WebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $secret = (string) config('backoffice.webhook_secret');

        if ($secret !== '' && ! hash_equals(
            hash_hmac('sha256', $request->getContent(), $secret),
            (string) $request->header('X-Signature', '')
        )) {
            return response()->json(['message' => 'Geçersiz imza'], 401);
        }

        $event = (string) $request->header('X-Event', $request->input('event', 'bilinmiyor'));
        $payload = $request->input('payload', []);

        WebhookEvent::create([
            'event' => $event,
            'payload' => $payload,
        ]);

        // Katalog kaynaklı olaylarda vitrin önbelleği düşürülür → sonraki yüklemede canlı çeker
        if (in_array($event, ['stock.changed', 'product.upserted', 'product.deleted'], true)) {
            cache()->forget('shop.catalog');
            cache()->put('shop.last_sync', now(), now()->addDay());
        }

        return response()->json(['ok' => true]);
    }
}
