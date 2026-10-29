<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with(['product', 'invoice', 'warehouse'])->latest('id');

        if ($productId = $request->get('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($direction = $request->get('direction')) {
            $query->where('direction', $direction);
        }

        if ($warehouseId = $request->get('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        return view('movements.index', [
            'movements' => $query->paginate(9)->withQueryString(),
            'products' => Product::orderBy('name')->get(),
            'warehouses' => \App\Models\Warehouse::where('is_active', true)->orderBy('name')->get(),
            'filters' => [
                'product_id' => $productId,
                'type' => $type,
                'direction' => $direction,
                'warehouse_id' => $warehouseId,
            ],
        ]);
    }
}
