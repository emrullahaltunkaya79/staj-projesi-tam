@extends('layouts.app')
@section('title', 'Ürünler')

@section('actions')
    <button type="button" class="btn-secondary" data-modal-url="{{ route('urunler.stok-sayim.import.form') }}" data-modal-title="Stok Sayımı Yükle">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 14l5-5 5 5M12 9V3"/>
        </svg>
        Sayım Yükle
    </button>
    <button type="button" class="btn-secondary" data-modal-url="{{ route('urunler.import.form') }}" data-modal-title="Ürünler — İçe Aktar">İçe Aktar</button>
    <a href="{{ route('urunler.stok-sayim') }}" class="btn-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        Stok Sayım
    </a>
    <a href="{{ route('urunler.export') }}" class="btn-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
        </svg>
        Excel
    </a>
    <button type="button" class="btn-primary" data-modal-url="{{ route('urunler.create') }}" data-modal-title="Yeni Ürün">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Ürün
    </button>
@endsection

@section('content')

    @php
        $chips = [];
        $q = request()->query();
        if (! empty($q['search'])) $chips[] = ['label' => 'Arama', 'value' => $q['search'], 'url' => request()->fullUrlWithQuery(['search' => null, 'page' => null])];
        if (! empty($q['category'])) { $cat = $categories->firstWhere('id', (int) $q['category']); $chips[] = ['label' => 'Kategori', 'value' => $cat?->name ?? $q['category'], 'url' => request()->fullUrlWithQuery(['category' => null, 'page' => null])]; }
        if (! empty($q['low_stock'])) $chips[] = ['label' => 'Stok', 'value' => 'sadece kritik', 'url' => request()->fullUrlWithQuery(['low_stock' => null, 'page' => null])];
    @endphp
    @include('partials.chips')

    @php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    @endphp

    {{-- Filtre --}}
    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <input type="text" name="search" value="{{ $filters['search'] }}" class="input sm:max-w-xs"
               placeholder="Ürün adı veya kodu ara...">
        <select name="category" class="input sm:w-44">
            <option value="">Tüm kategoriler</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected($filters['category'] == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="low_stock" value="1" @checked($filters['low_stock']) class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            Sadece kritik stok (&lt; 5)
        </label>
        <button type="submit" class="btn-secondary !h-10 sm:w-28">Filtrele</button>
    </form>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th class="w-14"></th>
                    <th>Kod</th>
                    <th>Ürün Adı</th>
                    <th>Kategori</th>
                    <th>Birim</th>
                    <th class="!text-right">Alış Fiyatı</th>
                    <th class="!text-right">Satış Fiyatı</th>
                    <th class="!text-right">Stok</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($products as $product)
                    <tr class="hover:bg-slate-50">
                        <td>
                            @if ($product->primaryImageUri())
                                <img src="{{ $product->primaryImageUri() }}" alt="" class="h-10 w-10 rounded-lg border border-slate-200 object-cover dark:border-slate-700">
                            @else
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-300 dark:bg-slate-700 dark:text-slate-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="font-mono text-xs text-slate-500">{{ $product->code ?: '—' }}</div>
                            @if ($product->barcode)
                                <div class="mt-0.5 font-mono text-[10px] text-slate-400">{{ $product->barcode }}</div>
                            @endif
                        </td>
                        <td class="font-medium">
                            <a href="{{ route('urunler.show', $product) }}" data-modal-url="{{ route('urunler.show', $product) }}" data-modal-title="Ürün: {{ $product->name }}" data-modal-view="1" class="text-slate-700 hover:text-indigo-600 dark:text-slate-200">{{ $product->name }}</a>
                        </td>
                        <td>
                            @if ($product->category)
                                <span class="badge-slate">{{ $product->category->name }}</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td>{{ $product->unit }}</td>
                        <td class="text-right tabular-nums">{{ $fmt($product->purchase_price) }}</td>
                        <td class="text-right tabular-nums">{{ $fmt($product->sale_price) }}</td>
                        <td class="text-right tabular-nums">
                            <span class="{{ $product->stock_quantity == 0 ? 'badge-red' : ($product->isLowStock() ? 'badge-amber' : 'badge-green') }}">
                                {{ $product->stock_quantity }} {{ $product->unit }}
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('urunler.show', $product) }}" data-modal-url="{{ route('urunler.show', $product) }}" data-modal-title="Ürün: {{ $product->name }}" data-modal-view="1" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Detay</a>
                                <a href="{{ route('urunler.edit', $product) }}" data-modal-url="{{ route('urunler.edit', $product) }}" data-modal-title="Ürünü Düzenle" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Düzenle</a>
                                @can('delete', $product)
                                    <form method="POST" action="{{ route('urunler.destroy', $product) }}"
                                          onsubmit="return confirm('Bu ürünü silmek istediğinize emin misiniz?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Sil</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    @include('partials.empty', ['colspan' => 7, 'icon' => 'box', 'title' => 'Kayıtlı ürün yok', 'desc' => 'İlk ürününüzü ekleyin — stok, kategori ve barkodla birlikte kataloğunuz oluşur.', 'action' => 'Yeni Ürün Ekle', 'actionUrl' => 'http://localhost/urunler/create', 'actionTitle' => 'Yeni Ürün'])
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $products->links() }}
        </div>
    </div>
@endsection
