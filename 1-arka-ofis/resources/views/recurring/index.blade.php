@extends('layouts.app')
@section('title', 'Tekrarlayan Faturalar')

@section('actions')
    <button type="button" class="btn-primary" data-modal-url="{{ route('tekrarlayan.create') }}" data-modal-title="Yeni Tekrarlayan Fatura Kuralı" data-modal-wide="1">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Kural
    </button>
@endsection

@section('content')
    @php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    @endphp

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Kural</th>
                    <th>Cari</th>
                    <th>Kesim Günü</th>
                    <th class="!text-right">Kalem</th>
                    <th class="!text-right">Tahmini Tutar</th>
                    <th>Son Kesim</th>
                    <th>Durum</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rules as $rule)
                    @php
                        $net = collect($rule->items)->sum(fn ($i) => $i['quantity'] * $i['unit_price']);
                        $total = $net * (1 + (float) $rule->tax_rate / 100);
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="font-medium text-slate-700">{{ $rule->title }}</td>
                        <td>{{ $rule->contact->name }}</td>
                        <td>Her ayın {{ $rule->day_of_month }}. günü</td>
                        <td class="text-right">{{ count($rule->items) }} ürün</td>
                        <td class="text-right font-semibold">{{ $fmt($total) }}</td>
                        <td>{{ $rule->last_run_at?->format('d.m.Y') ?? '—' }}</td>
                        <td>
                            <span class="{{ $rule->is_active ? 'badge-green' : 'badge-slate' }}">
                                {{ $rule->is_active ? 'Aktif' : 'Duraklatıldı' }}
                            </span>
                        </td>
                        <td>
                            @can('delete', $rule)
                                <div class="flex justify-end">
                                    <form method="POST" action="{{ route('tekrarlayan.destroy', $rule) }}"
                                          onsubmit="return confirm('Bu kuralı silmek istediğinize emin misiniz?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Sil</button>
                                    </form>
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-10 text-center text-slate-400">Henüz tekrarlayan fatura kuralı yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $rules->links() }}
        </div>
    </div>

    <p class="mt-3 text-xs text-slate-400">
        Kurallar her gün saat 00:05'te <code>tekrarlayan:faturalar-kes</code> komutuyla işlenir; ayın günü gelen kurallar otomatik satış faturası keser (stok kontrolü dahil).
    </p>
@endsection
