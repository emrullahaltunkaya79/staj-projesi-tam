@extends('layouts.app')
@section('title', $warehouse->name . ' — Depo Stoğu')

@section('actions')
    <a href="{{ route('depolar.index') }}" class="btn-secondary">← Depolar</a>
@endsection

@section('content')
    @php
        $fmt = fn ($v) => number_format((float) $v, 2, ',', '.').' ₺';
    @endphp

    <div class="mb-4 flex items-center gap-3">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 text-lg font-black text-white">
            {{ mb_substr($warehouse->name, 0, 1) }}
        </div>
        <div>
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">{{ $warehouse->name }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ $warehouse->code ? $warehouse->code . ' · ' : '' }}{{ $stocks->total() }} ürün · Toplam {{ $stocks->getCollection()->sum('stock_quantity') }} adet · Sayfa {{ $stocks->currentPage() }} / {{ $stocks->lastPage() }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Stok listesi --}}
        <div class="card xl:col-span-2">
            <div class="card-header">Depo Stoğu</div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>Ürün</th>
                        <th>Kategori</th>
                        <th class="!text-right">Stok</th>
                        <th class="!text-right">Stok Değeri</th>
                        <th>Transfer</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($stocks as $row)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                            <td>
                                <div class="font-medium text-slate-700 dark:text-slate-200">{{ $row->product->name }}</div>
                                <div class="text-xs text-slate-400">{{ $row->product->code ?: '—' }}</div>
                            </td>
                            <td>
                                @if ($row->product->category)
                                    <span class="badge-slate">{{ $row->product->category->name }}</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <span class="{{ $row->stock_quantity < 5 ? 'badge-amber' : 'badge-green' }}">
                                    {{ $row->stock_quantity }} {{ $row->product->unit }}
                                </span>
                            </td>
                            <td class="text-right font-semibold">{{ $fmt($row->stock_quantity * $row->product->purchase_price) }}</td>
                            <td>
                                @if (count($allWarehouses) > 0)
                                    <form method="POST" action="{{ route('depolar.transfer', $warehouse) }}"
                                          class="flex items-center gap-1" style="min-height:0;">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $row->product_id }}">
                                        <select name="to_warehouse_id" class="rounded-lg border-slate-300 text-xs dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200" style="min-height:0;padding:4px 8px;">
                                            @foreach ($allWarehouses as $target)
                                                <option value="{{ $target->id }}">{{ $target->name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" name="quantity" value="1" min="1" max="{{ $row->stock_quantity }}"
                                               class="w-16 rounded-lg border-slate-300 text-xs dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200" style="min-height:0;padding:4px 8px;">
                                        <button type="submit" class="rounded-lg bg-indigo-50 px-2 py-1 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100">→</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-slate-400">Bu depoda stok yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($stocks->lastPage() > 1)
                <div class="mt-4 flex items-center justify-between gap-3">
                    <p class="whitespace-nowrap text-xs text-slate-400">Sayfa {{ $stocks->currentPage() }} / {{ $stocks->lastPage() }} — sayfa başına 6 ürün</p>
                    <div class="flex items-center gap-1.5">
                        @if ($stocks->onFirstPage())
                            <span class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-300 dark:border-slate-700 dark:text-slate-600">‹ Önceki</span>
                        @else
                            <a href="{{ $stocks->previousPageUrl() }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:border-slate-900 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-400">‹ Önceki</a>
                        @endif
                        @foreach (range(1, $stocks->lastPage()) as $n)
                            <a href="{{ $stocks->url($n) }}"
                               class="rounded-lg px-2.5 py-1.5 text-xs font-bold tabular-nums transition {{ $n === $stocks->currentPage() ? 'bg-slate-900 text-white dark:bg-indigo-600' : 'border border-slate-200 text-slate-500 hover:border-slate-400 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:text-white' }}">{{ $n }}</a>
                        @endforeach
                        @if ($stocks->hasMorePages())
                            <a href="{{ $stocks->nextPageUrl() }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:border-slate-900 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-400">Sonraki ›</a>
                        @else
                            <span class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-300 dark:border-slate-700 dark:text-slate-600">Sonraki ›</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Özet --}}
        <div class="card p-5">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Depo Özeti</div>
            <dl class="mt-3 space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Ürün çeşidi</dt>
                    <dd class="font-bold">{{ $stocks->total() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Toplam adet</dt>
                    <dd class="font-bold">{{ $stocks->getCollection()->sum('stock_quantity') }}</dd>
                </div>
                <div class="flex justify-between border-t border-slate-100 dark:border-slate-700 pt-3">
                    <dt class="text-slate-500">Stok değeri (alış)</dt>
                    <dd class="font-bold">{{ $fmt($stocks->getCollection()->sum(fn ($s) => $s->stock_quantity * ($s->product?->purchase_price ?? 0))) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Stok değeri (satış)</dt>
                    <dd class="font-bold">{{ $fmt($stocks->getCollection()->sum(fn ($s) => $s->stock_quantity * ($s->product?->sale_price ?? 0))) }}</dd>
                </div>
            </dl>
        </div>
    </div>
@endsection
