@extends('layouts.app')
@section('title', 'Giderler')

@section('actions')
    <button type="button" class="btn-primary" data-modal-url="{{ route('giderler.create') }}" data-modal-title="Gider Kaydı">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Gider
    </button>
@endsection

@section('content')

    @php
        $chips = [];
        if (! empty(request('category'))) $chips[] = ['label' => 'Kategori', 'value' => request('category'), 'url' => request()->fullUrlWithQuery(['category' => null, 'page' => null])];
    @endphp
    @include('partials.chips')

    @php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    @endphp

    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <select name="category" class="input sm:w-52">
            <option value="">Tüm kategoriler</option>
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ $category }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-secondary !h-10 sm:w-28">Filtrele</button>
    </form>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Kategori</th>
                    <th>Açıklama</th>
                    <th>Ödeme</th>
                    <th class="!text-right">Tutar</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($expenses as $expense)
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap">{{ $expense->expense_date->format('d.m.Y') }}</td>
                        <td>
                            <span class="badge-slate">{{ $expense->category }}</span>
                        </td>
                        <td class="max-w-xs truncate text-slate-500" title="{{ $expense->description }}">{{ $expense->description ?: '—' }}</td>
                        <td>
                            <span class="{{ $expense->isCash() ? 'badge-green' : 'badge-indigo' }}">{{ $expense->typeLabel() }}</span>
                        </td>
                        <td class="text-right font-semibold tabular-nums text-red-600">−{{ $fmt($expense->amount) }}</td>
                        <td>
                            @can('delete', $expense)
                                <div class="flex justify-end">
                                    <form method="POST" action="{{ route('giderler.destroy', $expense) }}"
                                          onsubmit="return confirm('Bu gider kaydını silmek istediğinize emin misiniz?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Sil</button>
                                    </form>
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    @include('partials.empty', ['colspan' => 6, 'icon' => 'trend', 'title' => 'Henüz gider yok', 'desc' => 'Kira, fatura, maaş gibi işletme giderlerini kaydedin; kasa ve raporlara yansır.', 'action' => 'Gider Kaydı Aç', 'actionUrl' => 'http://localhost/giderler/create', 'actionTitle' => 'Gider Kaydı'])
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $expenses->links() }}
        </div>
    </div>
@endsection
