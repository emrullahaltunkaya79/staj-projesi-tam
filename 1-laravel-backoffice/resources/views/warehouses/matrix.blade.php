@extends('layouts.app')
@section('title', 'Depo Stok Matrisi')

@section('actions')
    <a href="{{ route('depolar.index') }}" class="btn-secondary">← Depolar</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">Stok Matrisi — Ürün × Depo</div>
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th class="sticky left-0 bg-slate-50 dark:bg-slate-800 z-10">Ürün</th>
                    @foreach ($warehouses as $wh)
                        <th class="!text-center">{{ $wh->name }}</th>
                    @endforeach
                    <th class="!text-center">Toplam</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($products as $product)
                    @php $total = $product->totalWarehouseStock(); @endphp
                    @if ($total > 0)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                            <td class="sticky left-0 bg-white dark:bg-slate-800 z-10">
                                <div class="font-medium text-slate-700 dark:text-slate-200">{{ $product->name }}</div>
                                <div class="text-xs text-slate-400">{{ $product->code ?: '—' }} · Sistem stoğu: {{ $product->stock_quantity }}</div>
                            </td>
                            @foreach ($warehouses as $wh)
                                @php $qty = $wh->stockOf($product->id); @endphp
                                <td class="text-center">
                                    @if ($qty > 0)
                                        <span class="{{ $qty < 5 ? 'badge-amber' : 'badge-green' }}">{{ $qty }}</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-center font-bold">{{ $total }}</td>
                        </tr>
                    @endif
                @endforeach
                @foreach ($products as $product)
                    @if ($product->totalWarehouseStock() === 0 && $product->stock_quantity > 0)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                            <td class="sticky left-0 bg-white dark:bg-slate-800 z-10">
                                <div class="font-medium text-slate-700 dark:text-slate-200">{{ $product->name }}</div>
                                <div class="text-xs text-amber-500">Henüz depoya atanmamış (sistem stoğu: {{ $product->stock_quantity }})</div>
                            </td>
                            @foreach ($warehouses as $wh)
                                <td class="text-center text-slate-300">—</td>
                            @endforeach
                            <td class="text-center text-slate-300">0</td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="border-t border-slate-100 dark:border-slate-700 px-4 py-3 text-xs text-slate-400">
            Bu matris her ürünün hangi depoda kaç adet olduğunu gösterir. "Henüz depoya atanmamış" satırları ürün açılış stoğunun depolara dağıtılmadığını belirtir.
        </p>
    </div>
@endsection
