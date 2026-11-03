@extends('layouts.app')
@section('title', 'Stok Dağıtım Aracı')

@section('actions')
    <a href="{{ route('depolar.index') }}" class="btn-secondary">← Depolar</a>
@endsection

@section('content')
    @php $fmt = fn ($v) => number_format((float) $v, 2, ',', '.').' ₺'; @endphp

    <div class="card mb-4 p-5">
        <h3 class="mb-1 font-bold text-slate-800 dark:text-slate-100">Toplu Stok Dağıtımı</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Aşağıdaki ürünlerin stokları depolara tam olarak dağıtılmamış. Her ürün için depolara adet girin; toplam sistem stokunu aşamaz.
        </p>
    </div>

    @if ($unassigned->isEmpty())
        <div class="card p-10 text-center text-slate-400">Tüm stoklar depolara dağıtılmış. 👍</div>
    @else
        <form method="POST" action="{{ route('depolar.distribute.store') }}">
            @csrf
            <div class="card overflow-x-auto">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>Ürün</th>
                        <th class="!text-right">Sistem Stoğu</th>
                        <th class="!text-right">Depolarda</th>
                        <th class="!text-right">Atanmamış</th>
                        @foreach ($warehouses as $wh)
                            <th class="!text-center">{{ $wh->name }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($unassigned as $product)
                        @php $inWarehouse = $product->totalWarehouseStock(); @endphp
                        <tr>
                            <td>
                                <div class="font-medium text-slate-700 dark:text-slate-200">{{ $product->name }}</div>
                                <div class="text-xs text-slate-400">{{ $product->code ?: '—' }}</div>
                            </td>
                            <td class="text-right font-bold">{{ $product->stock_quantity }}</td>
                            <td class="text-right text-slate-500">{{ $inWarehouse }}</td>
                            <td class="text-right font-bold text-amber-600">{{ $product->stock_quantity - $inWarehouse }}</td>
                            @foreach ($warehouses as $wh)
                                <td class="text-center">
                                    <input type="number" name="assignments[{{ $product->id }}][{{ $wh->id }}]"
                                           value="0" min="0" max="{{ $product->stock_quantity }}"
                                           class="w-20 rounded-lg border-slate-300 text-center text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                                           style="min-height: 0; padding: 4px 8px;">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex items-center gap-3">
                <button type="submit" class="btn-primary">Dağıtımı Uygula</button>
                <span class="text-xs text-slate-400">Girilen adetler depo stoklarına eklenir; sistem stoğu değişmez.</span>
            </div>
        </form>
    @endif
@endsection
