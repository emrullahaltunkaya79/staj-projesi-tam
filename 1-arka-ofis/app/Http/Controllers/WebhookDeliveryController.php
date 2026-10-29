<?php

namespace App\Http\Controllers;

use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Http\Request;

class WebhookDeliveryController extends Controller
{
    /**
     * Webhook teslimat geçmişi: entegrasyona gönderilen tüm olaylar.
     */
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $query = WebhookDelivery::query()->latest('id');

        if ($request->get('durum') === 'basarisiz') {
            $query->where('success', false);
        } elseif ($request->get('durum') === 'basarili') {
            $query->where('success', true);
        }

        $deliveries = $query->paginate(20)->withQueryString();

        $summary = [
            'toplam' => WebhookDelivery::count(),
            'basarili' => WebhookDelivery::where('success', true)->count(),
            'basarisiz' => WebhookDelivery::where('success', false)->count(),
            'son_24s' => WebhookDelivery::where('created_at', '>=', now()->subDay())->count(),
        ];

        return view('webhooks.index', [
            'deliveries' => $deliveries,
            'summary' => $summary,
            'filter' => $request->get('durum'),
        ]);
    }

    /**
     * Başarısız teslimatı yeniden gönder.
     */
    public function retry(Request $request, WebhookDelivery $delivery)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $delivery = app(WebhookService::class)->retry($delivery);

        $mesaj = $delivery->success
            ? "Webhook yeniden gönderildi ve {$delivery->response_code} yanıtı alındı."
            : "Yeniden deneme başarısız — yanıt kodu: {$delivery->response_code}.";

        return back()->with($delivery->success ? 'success' : 'error', $mesaj);
    }
}
