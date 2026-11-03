@extends('layouts.app')
@section('title', 'Ürün: '.$product->name)

@section('actions')
    <a href="{{ route('urunler.index') }}" class="btn-secondary">← Ürünler</a>
    <a href="{{ route('urunler.edit', $product) }}" class="btn-primary">Düzenle</a>
@endsection

@section('content')
    @php $fmt = fn ($v) => number_format((float) $v, 2, ',', '.').' ₺'; @endphp

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Görsel galeri + yükleme --}}
        <div class="card p-5 lg:col-span-2">
            <div class="card-header border-b-0 pb-0">Görseller</div>

            @if ($product->images->count())
                <div class="grid grid-cols-2 gap-3 px-5 pt-4 sm:grid-cols-4">
                    @foreach ($product->images as $image)
                        <div class="group relative">
                            <img src="{{ $image->dataUri() }}" alt="{{ $product->name }}"
                                 class="aspect-square w-full rounded-xl border border-slate-200 object-cover dark:border-slate-700">
                            @if ($image->is_primary)
                                <span class="absolute left-1.5 top-1.5 rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-bold text-white">Ana</span>
                            @endif
                            @can('delete', $product)
                                <form method="POST" action="{{ route('urunler.gorsel.destroy', [$product, $image]) }}" class="absolute right-1.5 top-1.5">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="flex h-6 w-6 items-center justify-center rounded-full bg-red-600/90 text-xs text-white opacity-0 transition group-hover:opacity-100" title="Sil">×</button>
                                </form>
                            @endcan
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-5 py-6 text-center text-sm text-slate-400">Henüz görsel yüklenmemiş.</div>
            @endif

            <form method="POST" action="{{ route('urunler.gorsel.upload', $product) }}" enctype="multipart/form-data" class="border-t border-slate-100 dark:border-slate-700 mt-4 p-5">
                @csrf
                <label class="label" for="images">Görsel Yükle (JPG/PNG/WebP, en fazla 4 MB — çoklu seçilebilir)</label>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <input type="file" id="images" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp"
                           class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                    <button type="submit" class="btn-primary shrink-0">Yükle</button>
                </div>
                @error('images.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </form>
        </div>

        {{-- Ürün bilgisi --}}
        <div class="card p-5">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Ürün Bilgisi</span>
                @if ($product->category)
                    <span class="badge-slate">{{ $product->category->name }}</span>
                @endif
            </div>
            <div class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ $product->name }}</div>
            <div class="mt-1 font-mono text-xs text-slate-400">
                {{ $product->code ?: '—' }}
                @if ($product->barcode)
                    <span class="ml-2 text-slate-500">▐|▍ {{ $product->barcode }}</span>
                @endif
            </div>

            <dl class="mt-4 space-y-2.5 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Alış</dt><dd class="font-semibold">{{ $fmt($product->purchase_price) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Satış</dt><dd class="font-semibold">{{ $fmt($product->sale_price) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Birim kâr</dt><dd class="font-bold text-emerald-600">{{ $fmt($product->sale_price - $product->purchase_price) }}</dd></div>
                <div class="flex justify-between border-t border-slate-100 dark:border-slate-700 pt-2.5">
                    <dt class="text-slate-500">Fiziksel stok</dt>
                    <dd class="font-bold">{{ $product->stock_quantity }} {{ $product->unit }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Rezerve</dt>
                    <dd class="{{ $product->reserved_quantity > 0 ? 'font-bold text-amber-600' : 'text-slate-400' }}">{{ $product->reserved_quantity }}</dd>
                </div>
                <div class="flex justify-between border-t border-slate-100 dark:border-slate-700 pt-2.5">
                    <dt class="font-semibold text-slate-600">Müsait</dt>
                    <dd class="font-bold text-indigo-600">{{ $product->availableQuantity() }} {{ $product->unit }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Depo dağılımı --}}
    <div class="card mt-6 p-5">
        <div class="card-header border-b-0 p-0 pb-3">Depo Dağılımı</div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @forelse ($product->warehouses as $wh)
                <div class="rounded-xl border border-slate-200 p-3 text-center dark:border-slate-700">
                    <div class="truncate text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $wh->name }}</div>
                    <div class="mt-1 text-xl font-black text-slate-800 dark:text-slate-100">
                        {{ $wh->pivot->stock_quantity }}
                    </div>
                </div>
            @empty
                <div class="text-sm text-slate-400">Ürün henüz bir depoya atanmamış.</div>
            @endforelse
        </div>
    </div>
@endsection
