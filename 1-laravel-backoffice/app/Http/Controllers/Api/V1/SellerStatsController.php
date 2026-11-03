<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerStatsController extends Controller
{
    /**
     * Satıcı paneli özeti: ciro, sipariş, ürün, stok durumu.
     * GET /api/v1/stats/seller
     */
    public function index(Request $request): JsonResponse
    {
        $satisFaturalari = Invoice::where('type', 'satis');
        $buAy = now()->startOfMonth();

        $urunler = Product::with('category')->orderBy('name')->get();

        return response()->json([
            'data' => [
                'ozet' => [
                    'toplam_ciro' => (float) $satisFaturalari->sum('total'),
                    'bu_ay_ciro' => (float) Invoice::where('type', 'satis')
                        ->where('issue_date', '>=', $buAy->toDateString())
                        ->sum('total'),
                    'fatura_sayisi' => Invoice::where('type', 'satis')->count(),
                    'siparis_sayisi' => Order::count(),
                    'bekleyen_siparis' => Order::where('status', 'pending')->count(),
                    'kargolanacak' => Order::whereIn('status', ['paid', 'preparing'])->count(),
                    'urun_sayisi' => $urunler->count(),
                    'kritik_stok' => $urunler->where('stock_quantity', '<', 5)->count(),
                    'tukenen' => $urunler->where('stock_quantity', 0)->count(),
                    'stok_degeri' => (float) $urunler->sum(fn ($p) => $p->stock_quantity * $p->purchase_price),
                ],
                'kritik_urunler' => $urunler
                    ->where('stock_quantity', '<', 5)
                    ->sortBy('stock_quantity')
                    ->take(8)
                    ->map(fn ($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'category' => $p->category?->name,
                        'stock_quantity' => $p->stock_quantity,
                        'sale_price' => (float) $p->sale_price,
                    ])
                    ->values(),
                'son_siparisler' => Order::with('contact')
                    ->latest('id')
                    ->take(8)
                    ->get()
                    ->map(fn ($o) => [
                        'order_no' => $o->order_no,
                        'customer' => $o->customer_name ?: $o->contact?->name,
                        'status' => $o->status,
                        'status_label' => $o->statusLabel(),
                        'total' => (float) $o->total,
                        'created_at' => $o->created_at->toIso8601String(),
                    ]),
                'kategori_dagilimi' => $urunler
                    ->groupBy(fn ($p) => $p->category?->name ?? 'Diğer')
                    ->map(fn ($grup, $ad) => [
                        'kategori' => $ad,
                        'urun_sayisi' => $grup->count(),
                        'stok_degeri' => (float) $grup->sum(fn ($p) => $p->stock_quantity * $p->purchase_price),
                    ])
                    ->values(),
            ],
        ]);
    }
}
