@extends('layouts.app')
@section('title', 'Özel Fiyat Listesi')

@section('actions')
    <a href="#yeni-fiyat" class="btn-primary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Özel Fiyat
    </a>
@endsection

@section('content')
    @php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    @endphp

    {{-- Filtre --}}
    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <select name="contact_id" class="input sm:w-56">
            <option value="">Tüm cariler</option>
            @foreach ($contacts as $contact)
                <option value="{{ $contact->id }}" @selected($filters['contact_id'] == $contact->id)>{{ $contact->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-secondary !h-10 sm:w-28">Filtrele</button>
    </form>

    {{-- Yeni fiyat formu --}}
    <div class="card mb-4 p-5" id="yeni-fiyat">
        <h3 class="mb-3 font-bold text-slate-800">Özel Fiyat Ekle</h3>
        <form method="POST" action="{{ route('fiyatlar.store') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            @csrf
            <div class="flex-1">
                <label class="label" for="contact_id">Cari</label>
                <select id="contact_id" name="contact_id" class="input" required>
                    <option value="">— Cari seçin —</option>
                    @foreach ($contacts as $contact)
                        <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label class="label" for="product_id">Ürün</label>
                <select id="product_id" name="product_id" class="input" required>
                    <option value="">— Ürün seçin —</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:w-32">
                <label class="label" for="price">Fiyat (₺)</label>
                <input type="number" id="price" name="price" min="0.01" step="0.01" required class="input">
            </div>
            <button type="submit" class="btn-primary !h-10">Kaydet</button>
        </form>
        <p class="mt-2 text-xs text-slate-400">Aynı cari-ürün çifti için varolan kayıt güncellenir. Fatura/teklif formunda ürün seçilince bu fiyat otomatik uygulanır.</p>
    </div>

    {{-- Liste --}}
    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Cari</th>
                    <th>Ürün</th>
                    <th class="!text-right">Standart Satış</th>
                    <th class="!text-right">Özel Fiyat</th>
                    <th class="!text-right">Fark</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($prices as $price)
                    @php
                        $diff = (float) $price->price - (float) $price->product->sale_price;
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="font-medium text-slate-700">{{ $price->contact->name }}</td>
                        <td>{{ $price->product->name }}</td>
                        <td class="text-right text-slate-400">{{ $fmt($price->product->sale_price) }}</td>
                        <td class="text-right font-bold {{ $diff < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $fmt($price->price) }}</td>
                        <td class="text-right text-xs {{ $diff < 0 ? 'text-rose-500' : 'text-emerald-500' }}">
                            {{ $diff > 0 ? '+' : '' }}{{ $fmt($diff) }}
                        </td>
                        <td>
                            @can('delete', $price)
                                <div class="flex justify-end">
                                    <form method="POST" action="{{ route('fiyatlar.destroy', $price) }}"
                                          onsubmit="return confirm('Bu özel fiyatı silmek istediğinize emin misiniz?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Sil</button>
                                    </form>
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    @include('partials.empty', ['colspan' => 6, 'icon' => 'tag', 'title' => 'Henüz özel fiyat yok', 'desc' => 'Yukarıdaki formdan cari bazlı özel fiyat tanımlayın — o cariye satışlarda otomatik uygulanır.'])
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($prices->hasPages())
            <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-700">
                {{ $prices->links() }}
            </div>
        @endif
    </div>
@endsection
