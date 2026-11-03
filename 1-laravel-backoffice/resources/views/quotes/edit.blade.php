@extends('layouts.app')
@section('title', 'Teklif Düzenle: '.$quote->quote_no)

@section('content')
    <form method="POST" action="{{ route('teklifler.update', $quote) }}" id="invoice-form" class="max-w-5xl">
        @csrf @method('PUT')

        <div class="card mb-4 grid grid-cols-1 gap-4 p-6 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <label class="label" for="contact_id">Cari (Müşteri) <span class="text-red-500">*</span></label>
                <select id="contact_id" name="contact_id" class="input" required>
                    <option value="">— Cari seçin —</option>
                    @foreach ($contacts as $contact)
                        <option value="{{ $contact->id }}" @selected(old('contact_id', $quote->contact_id) == $contact->id)>
                            {{ $contact->name }}
                        </option>
                    @endforeach
                </select>
                @error('contact_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="issue_date">Teklif Tarihi <span class="text-red-500">*</span></label>
                <input type="date" id="issue_date" name="issue_date" value="{{ old('issue_date', $quote->issue_date->toDateString()) }}"
                       required class="input">
                @error('issue_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="valid_until">Geçerlilik Tarihi</label>
                <input type="date" id="valid_until" name="valid_until" value="{{ old('valid_until', $quote->valid_until?->toDateString()) }}" class="input">
                @error('valid_until') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="tax_rate">KDV Oranı <span class="text-red-500">*</span></label>
                <select id="tax_rate" name="tax_rate" class="input" required>
                    @foreach ([20 => '%20 (Standart)', 10 => '%10 (Orta)', 1 => '%1 (Düşük)', 0 => 'KDV Yok'] as $rate => $label)
                        <option value="{{ $rate }}" @selected(old('tax_rate', (int) $quote->tax_rate) == $rate)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('tax_rate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header flex items-center justify-between">
                <span>Teklif Kalemleri</span>
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

        <div class="card p-6">
            <label class="label" for="notes">Teklif Notu</label>
            <textarea id="notes" name="notes" rows="2" class="input">{{ old('notes', $quote->notes) }}</textarea>

            <div class="mt-5 flex items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn-primary">Değişiklikleri Kaydet</button>
                <a href="{{ route('teklifler.show', $quote) }}" class="btn-secondary">İptal</a>
            </div>
        </div>
    </form>

    <script>
        window.invoiceProducts = @json($products);
        window.invoiceFormConfig = { priceKey: 'sale_price', stockCheck: false };
        window.invoicePrefill = @json($prefill);
    </script>

    @push('scripts')
        <script src="{{ asset('js/invoice-form.js') }}"></script>
    @endpush
@endsection
