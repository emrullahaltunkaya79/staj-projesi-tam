@extends('layouts.app')
@section('title', 'Yeni Satış Faturası')

@section('content')
    <form method="POST" action="{{ route('faturalar.store') }}" id="invoice-form" class="max-w-5xl">
        @csrf

        {{-- Fatura künyesi — tüm kutular eşit boyutta --}}
        <div class="card mb-4 grid grid-cols-1 gap-4 p-6 sm:grid-cols-2 xl:grid-cols-5">
            <div>
                <label class="label" for="contact_id">Cari (Müşteri) <span class="text-red-500">*</span></label>
                <select id="contact_id" name="contact_id" class="input" required>
                    <option value="">— Cari seçin —</option>
                    @foreach ($contacts as $contact)
                        <option value="{{ $contact->id }}" @selected(old('contact_id') == $contact->id)>
                            {{ $contact->name }}
                        </option>
                    @endforeach
                </select>
                @error('contact_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="warehouse_id">Depo</label>
                <select id="warehouse_id" name="warehouse_id" class="input">
                    <option value="">— Tümü (depoya bağlı değil) —</option>
                    @foreach ($warehouses as $wh)
                        <option value="{{ $wh->id }}" @selected(old('warehouse_id') == $wh->id)>{{ $wh->name }}</option>
                    @endforeach
                </select>
                @error('warehouse_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="issue_date">Fatura Tarihi <span class="text-red-500">*</span></label>
                <input type="date" id="issue_date" name="issue_date" value="{{ old('issue_date', now()->toDateString()) }}"
                       required class="input">
                @error('issue_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="due_date">Vade Tarihi</label>
                <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}" class="input">
                @error('due_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="tax_rate">KDV Oranı <span class="text-red-500">*</span></label>
                <select id="tax_rate" name="tax_rate" class="input" required>
                    @foreach ([20 => '%20 (Standart)', 10 => '%10 (Orta)', 1 => '%1 (Düşük)', 0 => 'KDV Yok'] as $rate => $label)
                        <option value="{{ $rate }}" @selected(old('tax_rate', 20) == $rate)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('tax_rate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Kalemler --}}
        <div class="card mb-4">
            <div class="card-header flex items-center justify-between">
                <span>Fatura Kalemleri</span>
                <button type="button" id="add-row" class="btn-secondary !py-1.5 !px-3 text-xs">+ Kalem Ekle</button>
            </div>

            <div class="overflow-x-auto p-4">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                    <tr class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-2 pb-2 w-[38%]">Ürün</th>
                        <th class="px-2 pb-2 w-[16%]">Miktar</th>
                        <th class="px-2 pb-2 w-[18%]">Birim Fiyat (₺)</th>
                        <th class="px-2 pb-2 w-[18%] text-right">Tutar</th>
                        <th class="px-2 pb-2 w-[10%]"></th>
                    </tr>
                    </thead>
                    <tbody id="invoice-items"></tbody>
                </table>
                @error('items') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Toplamlar --}}
            <div class="flex justify-end border-t border-slate-200 p-5">
                <div class="w-full max-w-xs space-y-2">
                    <div class="flex justify-between text-sm text-slate-600">
                        <span>Ara Toplam</span>
                        <span id="subtotal">0,00 ₺</span>
                    </div>
                    <div class="flex justify-between text-sm text-slate-600">
                        <span>KDV</span>
                        <span id="tax-amount">0,00 ₺</span>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-lg font-bold text-slate-800">
                        <span>Genel Toplam</span>
                        <span id="grand-total">0,00 ₺</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Not ve kaydet --}}
        <div class="card p-6">
            <label class="label" for="notes">Fatura Notu</label>
            <textarea id="notes" name="notes" rows="2" class="input"
                      placeholder="Ödeme vadesi, teslim bilgisi vb. (opsiyonel)">{{ old('notes') }}</textarea>

            <div class="mt-5 flex items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn-primary" id="submit-btn">
                    Faturayı Kes
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>
                <a href="{{ route('faturalar.index') }}" class="btn-secondary">İptal</a>
                <span class="ml-auto text-xs text-slate-400">Fatura kesildiğinde stoklar düşer, cari bakiyesi artar.</span>
            </div>
        </div>
    </form>

    {{-- Ürün verisi (fiyat / stok) JS tarafına taşınır --}}
    <script>
        window.invoiceProducts = @json($products);
    </script>

    @push('scripts')
        <script src="{{ asset('js/invoice-form.js') }}"></script>
    @endpush
@endsection
