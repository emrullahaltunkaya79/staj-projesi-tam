@extends('layouts.app')
@section('title', 'Stok Hareketleri')

@section('content')

    @php
        $chips = [];
        $q = request()->query();
        if (! empty($q['product_id'])) { $p = $products->firstWhere('id', (int) $q['product_id']); $chips[] = ['label' => 'Ürün', 'value' => $p?->name ?? $q['product_id'], 'url' => request()->fullUrlWithQuery(['product_id' => null, 'page' => null])]; }
        if (! empty($q['type'])) $chips[] = ['label' => 'Tip', 'value' => $q['type'], 'url' => request()->fullUrlWithQuery(['type' => null, 'page' => null])];
        if (! empty($q['warehouse_id'])) { $w = $warehouses->firstWhere('id', (int) $q['warehouse_id']); $chips[] = ['label' => 'Depo', 'value' => $w?->name ?? $q['warehouse_id'], 'url' => request()->fullUrlWithQuery(['warehouse_id' => null, 'page' => null])]; }
        if (! empty($q['direction'])) $chips[] = ['label' => 'Yön', 'value' => $q['direction'], 'url' => request()->fullUrlWithQuery(['direction' => null, 'page' => null])];
    @endphp
    @include('partials.chips')

    {{-- Filtre --}}
    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <select name="product_id" class="input sm:w-56">
            <option value="">Tüm ürünler</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected($filters['product_id'] == $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
        <select name="type" class="input sm:w-40">
            <option value="">Tüm hareket türleri</option>
            @foreach (['acilis' => 'Açılış', 'alis' => 'Alış', 'satis' => 'Satış', 'iade' => 'İade', 'duzeltme' => 'Düzeltme'] as $value => $label)
                <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="warehouse_id" class="input sm:w-40">
            <option value="">Tüm depolar</option>
            @foreach ($warehouses ?? [] as $wh)
                <option value="{{ $wh->id }}" @selected(($filters['warehouse_id'] ?? '') == $wh->id)>{{ $wh->name }}</option>
            @endforeach
        </select>
        <select name="direction" class="input sm:w-36">
            <option value="">Giriş + Çıkış</option>
            <option value="giris" @selected($filters['direction'] === 'giris')>Sadece Giriş</option>
            <option value="cikis" @selected($filters['direction'] === 'cikis')>Sadece Çıkış</option>
        </select>
        <button type="submit" class="btn-secondary !h-10 sm:w-28">Filtrele</button>
    </form>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Ürün</th>
                    <th>Tür</th>
                    <th>Yön</th>
                    <th class="!text-right">Miktar</th>
                    <th class="!text-right">İşlem Sonrası Stok</th>
                    <th>Kaynak</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($movements as $movement)
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap">{{ $movement->created_at->format('d.m.Y H:i') }}</td>
                        <td class="font-medium text-slate-700">{{ $movement->product_name }}</td>
                        <td>
                            <span class="{{ match($movement->type) {
                                'alis' => 'badge-amber',
                                'satis' => 'badge-indigo',
                                'iade' => 'badge-red',
                                'acilis' => 'badge-green',
                                default => 'badge-slate',
                            } }}">{{ $movement->typeLabel() }}</span>
                        </td>
                        <td>
                            @if ($movement->isIncoming())
                                <span class="font-semibold text-emerald-600">↑ Giriş</span>
                            @else
                                <span class="font-semibold text-red-600">↓ Çıkış</span>
                            @endif
                        </td>
                        <td class="text-right font-semibold">{{ rtrim(rtrim(number_format($movement->quantity, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="text-right tabular-nums">{{ rtrim(rtrim(number_format($movement->stock_after, 2, ',', '.'), '0'), ',') }}</td>
                        <td>
                            @if ($movement->invoice)
                                <a href="{{ route('faturalar.show', $movement->invoice) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                                    {{ $movement->invoice->invoice_no }}
                                </a>
                            @else
                                <span class="text-xs text-slate-400">{{ $movement->description }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    @include('partials.empty', ['colspan' => 7, 'icon' => 'swap', 'title' => 'Henüz stok hareketi yok', 'desc' => 'Hareketler fatura, sayım ve depo transferleriyle otomatik oluşur — manuel giriş gerekmez.'])
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $movements->links() }}
        </div>
    </div>
@endsection
