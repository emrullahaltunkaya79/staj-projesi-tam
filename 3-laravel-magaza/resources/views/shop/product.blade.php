@extends('layouts.shop')
@section('title', $product['name'])

@section('content')
    @php $fmt = fn ($v) => number_format((float) ($v ?? 0), 2, ',', '.').' ₺'; @endphp
    <div class="mb-4 text-sm text-slate-400">
        <a href="{{ route('shop.home') }}" class="font-semibold text-indigo-500 hover:text-indigo-600">← Vitrine dön</a>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white">
            @if (! empty($product['image_url']))
                <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" class="aspect-square w-full object-cover">
            @else
                <div class="flex aspect-square w-full items-center justify-center text-slate-300">
                    <svg class="h-24 w-24" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            @endif
        </div>

        <div class="flex flex-col">
            @if (! empty($product['category']))
                <span class="mb-2 w-fit rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-600">{{ $product['category'] }}</span>
            @endif
            <h1 class="text-3xl font-black tracking-tight text-slate-900">{{ $product['name'] }}</h1>
            <div class="mt-2 flex items-center gap-3 text-xs text-slate-400">
                @if (! empty($product['code']))<span class="font-mono">Kod: {{ $product['code'] }}</span>@endif
                @if (! empty($product['barcode']))<span class="font-mono">Barkod: {{ $product['barcode'] }}</span>@endif
            </div>

            <div class="mt-5 text-4xl font-black tabular-nums text-slate-900">{{ $fmt($product['sale_price'] ?? 0) }}</div>

            <div class="mt-4 flex items-center gap-2">
                @if (($product['available_quantity'] ?? 0) > 0)
                    <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">✓ Stokta — {{ $product['available_quantity'] }} {{ $product['unit'] ?? 'adet' }} müsait</span>
                @else
                    <span class="rounded-full bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600">Stokta yok</span>
                @endif
                @if (($product['reserved_quantity'] ?? 0) > 0)
                    <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-600">{{ $product['reserved_quantity'] }} adet sepette tutuluyor</span>
                @endif
            </div>

            @livewire('add-to-cart', [
                'productId' => $product['id'],
                'name' => $product['name'],
                'price' => (float) ($product['sale_price'] ?? 0),
                'available' => (int) ($product['available_quantity'] ?? 0),
                'image' => $product['image_url'] ?? null,
                'unit' => $product['unit'] ?? 'adet',
            ])

            <div class="mt-8 space-y-2 rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500">
                <div class="flex items-center gap-2"><span class="text-emerald-500">●</span> Sipariş verdiğinizde stok anında arka ofiste rezerve edilir (30 dk garanti)</div>
                <div class="flex items-center gap-2"><span class="text-indigo-500">●</span> Ödeme onayında fatura otomatik kesilir ve kargo süreci başlar</div>
                <div class="flex items-center gap-2"><span class="text-purple-500">●</span> Kargoya verildiğinde takip numarası bu sitede görünür</div>
            </div>
        </div>
    </div>
@endsection
