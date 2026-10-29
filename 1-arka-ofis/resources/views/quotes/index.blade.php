@extends('layouts.app')
@section('title', 'Teklifler')

@section('actions')
    <button type="button" class="btn-primary" data-modal-url="{{ route('teklifler.create') }}" data-modal-title="Yeni Teklif" data-modal-wide="1">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Teklif
    </button>
@endsection

@section('content')
    @php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    @endphp

    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <select name="status" class="input sm:w-48">
            <option value="">Tüm durumlar</option>
            @foreach (['acik' => 'Açık', 'onaylandi' => 'Onaylandı', 'reddedildi' => 'Reddedildi', 'faturalandi' => 'Faturalandı'] as $value => $label)
                <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-secondary !h-10 sm:w-28">Filtrele</button>
    </form>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Teklif No</th>
                    <th>Cari</th>
                    <th>Tarih</th>
                    <th>Geçerlilik</th>
                    <th class="!text-right">Tutar</th>
                    <th>Durum</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($quotes as $quote)
                    <tr class="hover:bg-slate-50">
                        <td>
                            <a href="{{ route('teklifler.show', $quote) }}" data-modal-url="{{ route('teklifler.show', $quote) }}" data-modal-title="Teklif {{ $quote->quote_no }}" data-modal-view="1" data-modal-wide="1" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ $quote->quote_no }}</a>
                        </td>
                        <td>{{ $quote->contact->name }}</td>
                        <td>{{ $quote->issue_date->format('d.m.Y') }}</td>
                        <td>{{ $quote->valid_until?->format('d.m.Y') ?? '—' }}</td>
                        <td class="text-right font-semibold tabular-nums">{{ $fmt($quote->total) }}</td>
                        <td><span class="{{ $quote->statusBadgeClass() }}">{{ $quote->statusLabel() }}</span></td>
                        <td class="text-right tabular-nums">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('teklifler.show', $quote) }}" data-modal-url="{{ route('teklifler.show', $quote) }}" data-modal-title="Teklif {{ $quote->quote_no }}" data-modal-view="1" data-modal-wide="1" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Görüntüle</a>
                                @if ($quote->status === 'acik')
                                    <a href="{{ route('teklifler.edit', $quote) }}" data-modal-url="{{ route('teklifler.edit', $quote) }}" data-modal-title="Teklifi Düzenle — {{ $quote->quote_no }}" data-modal-wide="1" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Düzenle</a>
                                @endif
                                @can('delete', $quote)
                                    <form method="POST" action="{{ route('teklifler.destroy', $quote) }}"
                                          onsubmit="return confirm('Bu teklifi silmek istediğinize emin misiniz? (Geri dönüşüm kutusuna taşınır)');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Sil</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    @include('partials.empty', ['colspan' => 7, 'icon' => 'quote', 'title' => 'Henüz teklif yok', 'desc' => 'İlk teklifinizi hazırlayın; onaylanınca tek tıkla faturaya dönüşür.', 'action' => 'Yeni Teklif Hazırla', 'actionUrl' => 'http://localhost/teklifler/create', 'actionTitle' => 'Yeni Teklif', 'wide' => 1])
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $quotes->links() }}
        </div>
    </div>
@endsection
