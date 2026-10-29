<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::withCount('stocks')->with(['stocks.product'])->orderBy('name')->get();

        // Her depo için özet kartları
        $summaries = $warehouses->map(function ($wh) {
            $stocks = $wh->stocks->where('stock_quantity', '>', 0);
            return (object) [
                'id' => $wh->id,
                'name' => $wh->name,
                'code' => $wh->code,
                'is_active' => $wh->is_active,
                'product_count' => $stocks->count(),
                'total_items' => $stocks->sum('stock_quantity'),
                'stock_value' => $stocks->sum(fn ($s) => $s->stock_quantity * $s->product->purchase_price),
                'critical_count' => $stocks->where('stock_quantity', '<', 5)->count(),
            ];
        });

        return view('warehouses.index', [
            'warehouses' => $warehouses,
            'summaries' => $summaries,
        ]);
    }

    /**
     * Depo bazlı stok listesini Excel olarak indirir.
     */
    public function export(Warehouse $warehouse)
    {
        $stocks = WarehouseStock::with('product')
            ->where('warehouse_id', $warehouse->id)
            ->where('stock_quantity', '>', 0)
            ->get()
            ->sortBy(fn ($s) => mb_strtolower($s->product->name))
            ->values();

        $export = new class($warehouse->name, $stocks) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function __construct(private string $name, private $stocks) {}

            public function headings(): array
            {
                return ['Kod', 'Ürün', 'Birim', 'Stok', 'Alış Fiyatı', 'Satış Fiyatı', 'Stok Değeri'];
            }

            public function array(): array
            {
                return $this->stocks->map(fn ($s) => [
                    $s->product->code,
                    $s->product->name,
                    $s->product->unit,
                    $s->stock_quantity,
                    (float) $s->product->purchase_price,
                    (float) $s->product->sale_price,
                    round($s->stock_quantity * $s->product->purchase_price, 2),
                ])->all();
            }
        };

        return \Maatwebsite\Excel\Facades\Excel::download(
            $export,
            'depo-stok-' . \Illuminate\Support\Str::slug($warehouse->name) . '-' . now()->format('Ymd') . '.xlsx'
        );
    }

    /**
     * Toplu stok dağıtım aracı: depoya atanmamış stoğu depolara paylaştırır.
     */
    public function distribute(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'assignments' => ['required', 'array'],
                'assignments.*' => ['array'],
                'assignments.*.*' => ['integer', 'min:0'],
            ], [
                'assignments.required' => 'En az bir dağıtım girilmelidir.',
            ]);

            DB::transaction(function () use ($validated, $request) {
                foreach ($validated['assignments'] as $productId => $warehouseQtys) {
                    $product = \App\Models\Product::findOrFail($productId);
                    $total = array_sum($warehouseQtys);

                    if ($total > $product->stock_quantity) {
                        throw ValidationException::withMessages([
                            'assignments' => "\"{$product->name}\" için dağıtım toplamı ({$total}) stoktan ({$product->stock_quantity}) fazla olamaz.",
                        ]);
                    }

                    foreach ($warehouseQtys as $warehouseId => $qty) {
                        if ($qty > 0) {
                            $warehouse = Warehouse::findOrFail($warehouseId);
                            $warehouse->adjustStock($productId, $qty);
                        }
                    }
                }
            });

            return redirect()->route('depolar.distribute')
                ->with('success', 'Stok dağıtımı tamamlandı.');
        }

        // GET: dağıtım ekranı
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $unassigned = \App\Models\Product::with('warehouses')->orderBy('name')->get()
            ->filter(fn ($p) => $p->stock_quantity > 0 && $p->totalWarehouseStock() < $p->stock_quantity)
            ->values();

        return view('warehouses.distribute', compact('warehouses', 'unassigned'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:10'],
            'address' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Depo adı zorunludur.',
        ]);

        Warehouse::create($validated);

        return back()->with('success', "'{$validated['name']}' deposu eklendi.");
    }

    public function destroy(Request $request, Warehouse $warehouse)
    {
        if (! $request->user()->isAdmin()) {
            return back()->with('error', 'Silme işlemi için yönetici yetkisi gerekiyor.');
        }

        if ($warehouse->stocks()->where('stock_quantity', '>', 0)->exists()) {
            return back()->with('error', 'Bu depoda stok bulunan ürünler var — önce stokları başka depoya taşıyın.');
        }

        $warehouse->delete();

        return back()->with('success', "'{$warehouse->name}' deposu silindi.");
    }

    /**
     * Belirli bir deponun stok görünümü: hangi ürün, ne kadar.
     */
    public function stock(Warehouse $warehouse)
    {
        $stocks = WarehouseStock::with('product.category')
            ->where('warehouse_id', $warehouse->id)
            ->where('stock_quantity', '>', 0)
            ->get()
            ->sortBy(fn ($s) => mb_strtolower($s->product->name))
            ->values();

        $allWarehouses = Warehouse::where('is_active', true)
            ->where('id', '!=', $warehouse->id)
            ->orderBy('name')
            ->get();

        return view('warehouses.stock', compact('warehouse', 'stocks', 'allWarehouses'));
    }

    /**
     * Depolar arası stok transferi.
     */
    public function transfer(Request $request, Warehouse $warehouse)
    {
        $from = $warehouse;
        $validated = $request->validate([
            'to_warehouse_id' => ['required', 'integer', function ($attribute, $value, $fail) use ($from) {
                if ((int) $value === $from->id) {
                    $fail('Hedef depo farklı olmalıdır.');
                }
            }],
            'product_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
        ], [
            'to_warehouse_id.required' => 'Hedef depo seçilmelidir.',
            'quantity.min' => 'Miktar en az 1 olmalıdır.',
        ]);

        $to = Warehouse::findOrFail($validated['to_warehouse_id']);
        $product = \App\Models\Product::findOrFail($validated['product_id']);
        $qty = $validated['quantity'];

        $current = $from->stockOf($product->id);
        if ($current < $qty) {
            return back()->with('error', "Yetersiz stok: {$from->name} deposunda {$product->name} için sadece {$current} adet var.");
        }

        DB::transaction(function () use ($from, $to, $product, $qty) {
            $from->adjustStock($product->id, -$qty);
            $to->adjustStock($product->id, +$qty);

            StockMovement::record($product, 'duzeltme', 'cikis', (float) $qty, $from->stockOf($product->id), null,
                "{$from->name} → {$to->name} transfer çıkışı");
            StockMovement::record($product, 'duzeltme', 'giris', (float) $qty, $to->stockOf($product->id), null,
                "{$from->name} → {$to->name} transfer girişi");
        });

        return back()->with('success', "{$qty} adet {$product->name}: {$from->name} → {$to->name} transferi tamamlandı.");
    }

    /**
     * Tüm depoların ürün bazında stok tablosu (matris görünümü).
     */
    public function matrix()
    {
        $warehouses = Warehouse::where('is_active', true)->with('stocks.product')->orderBy('name')->get();
        $products = \App\Models\Product::with('warehouses')->orderBy('name')->get();

        return view('warehouses.matrix', compact('warehouses', 'products'));
    }
}
