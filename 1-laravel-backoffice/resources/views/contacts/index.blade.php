@extends('layouts.app')
@section('title', 'Cariler')

@section('actions')
    <button type="button" class="btn-secondary" data-modal-url="{{ route('cariler.import.form') }}" data-modal-title="Cariler — İçe Aktar">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 14l5-5 5 5M12 9V3"/>
        </svg>
        İçe Aktar
    </button>
    <a href="{{ route('cariler.export') }}" class="btn-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
        </svg>
        Excel
    </a>
    <button type="button" class="btn-primary" data-modal-url="{{ route('cariler.create') }}" data-modal-title="Yeni Cari">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Cari
    </button>
@endsection

@section('content')
    @php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    @endphp

    {{-- Filtre --}}
    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <input type="text" name="search" value="{{ $filters['search'] }}" class="input sm:max-w-xs"
               placeholder="Cari adı, telefon veya e-posta ara...">
        <select name="type" class="input sm:w-44">
            <option value="">Tümü (Müşteri + Tedarikçi)</option>
            <option value="musteri" @selected($filters['type'] === 'musteri')>Müşteriler</option>
            <option value="tedarikci" @selected($filters['type'] === 'tedarikci')>Tedarikçiler</option>
        </select>
        <button type="submit" class="btn-secondary !h-10 sm:w-28">Filtrele</button>
    </form>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Cari Adı</th>
                    <th>Tip</th>
                    <th>Telefon</th>
                    <th>E-posta</th>
                    <th class="!text-right">Bakiye</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($contacts as $contact)
                    <tr class="hover:bg-slate-50">
                        <td>
                            <a href="{{ route('cariler.show', $contact) }}" data-modal-url="{{ route('cariler.show', $contact) }}" data-modal-title="{{ $contact->name }}" data-modal-view="1" class="font-medium text-slate-700 hover:text-indigo-600">
                                {{ $contact->name }}
                            </a>
                            @if ($contact->tax_number)
                                <div class="text-xs text-slate-400">VKN: {{ $contact->tax_number }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="{{ $contact->isCustomer() ? 'badge-indigo' : 'badge-slate' }}">{{ $contact->typeLabel() }}</span>
                        </td>
                        <td>{{ $contact->phone ?: '—' }}</td>
                        <td>{{ $contact->email ?: '—' }}</td>
                        <td class="text-right font-semibold tabular-nums {{ $contact->balance > 0 ? 'text-emerald-600' : ($contact->balance < 0 ? 'text-red-600' : 'text-slate-500') }}">
                            {{ $contact->balance > 0 ? '+' : '' }}{{ $fmt($contact->balance) }}
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('cariler.show', $contact) }}"
                                   data-modal-url="{{ route('cariler.show', $contact) }}" data-modal-title="{{ $contact->name }}" data-modal-view="1"
                                   class="btn-secondary !px-2.5 !py-1.5 text-xs"
                                   title="Cari detay ve ekstre kartını aç">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                    Detay / Ekstre
                                </a>
                                <a href="{{ route('cariler.edit', $contact) }}" data-modal-url="{{ route('cariler.edit', $contact) }}" data-modal-title="Cariyi Düzenle — {{ $contact->name }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Düzenle</a>
                                @can('delete', $contact)
                                    <form method="POST" action="{{ route('cariler.destroy', $contact) }}"
                                          onsubmit="return confirm('Bu cariyi silmek istediğinize emin misiniz?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Sil</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    @include('partials.empty', ['colspan' => 6, 'icon' => 'users', 'title' => 'Kayıtlı cari yok', 'desc' => 'Müşteri ve tedarikçilerinizi ekleyerek başlayın; ekstre ve faturalar cari üzerinden işler.', 'action' => 'Yeni Cari Ekle', 'actionUrl' => 'http://localhost/cariler/create', 'actionTitle' => 'Yeni Cari'])
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $contacts->links() }}
        </div>
    </div>

    <p class="mt-3 text-xs text-slate-400">
        Bakiye pozitifse cari size borçludur (alacak), negatifse siz cariye borçlusunuz. Bakiye, kesilen faturalarla otomatik güncellenir.
    </p>
@endsection
