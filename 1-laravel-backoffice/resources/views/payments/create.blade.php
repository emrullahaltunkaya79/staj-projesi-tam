@extends('layouts.app')
@section('title', 'Tahsilat Al')

@section('content')
    <form method="POST" action="{{ route('payments.store') }}" class="max-w-2xl">
        @csrf

        <div class="card p-6">
            <h2 class="mb-1 text-lg font-bold text-slate-800">Yeni Tahsilat</h2>
            <p class="mb-6 text-sm text-slate-500">Nakit veya havale yoluyla cariden tahsilat kaydedin; cari bakiyesi otomatik düşer.</p>

            <div class="space-y-5">
                <div>
                    <label class="label" for="contact_id">Cari <span class="text-red-500">*</span></label>
                    <select id="contact_id" name="contact_id" class="input" required>
                        <option value="">— Cari seçin —</option>
                        @foreach ($contacts as $contact)
                            <option value="{{ $contact['id'] }}" @selected($selectedContactId == $contact['id'])>
                                {{ $contact['name'] }}
                            </option>
                        @endforeach
                    </select>
                    <div id="balance-hint" class="mt-1.5 text-xs text-slate-400"></div>
                    @error('contact_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="amount">Tahsilat Tutarı (₺) <span class="text-red-500">*</span></label>
                    <input type="number" id="amount" name="amount" min="0.01" step="0.01"
                           value="{{ old('amount') }}" required class="input" placeholder="0,00">
                    @error('amount') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <span class="label">Tahsilat Türü <span class="text-red-500">*</span></span>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:text-indigo-700">
                            <input type="radio" name="payment_type" value="nakit" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                   @checked(old('payment_type', 'nakit') === 'nakit')>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Nakit
                        </label>
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:text-indigo-700">
                            <input type="radio" name="payment_type" value="havale" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                   @checked(old('payment_type') === 'havale')>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            Havale / EFT
                        </label>
                    </div>
                    @error('payment_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="description">Açıklama</label>
                    <textarea id="description" name="description" rows="2" class="input"
                              placeholder="Örn: 1. taksit, banka referansı vb. (opsiyonel)">{{ old('description') }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Tahsilatı Kaydet
                </button>
                <a href="{{ url()->previous() }}" class="btn-secondary">İptal</a>
            </div>
        </div>
    </form>

    {{-- Seçilen carinin güncel bakiyesini formda göster --}}
    <script>
        window.paymentContacts = @json($contacts);
    </script>
    <script>
        (function () {
            const select = document.getElementById('contact_id');
            const hint = document.getElementById('balance-hint');
            if (!select || !hint) return;

            const updateHint = () => {
                const contact = (window.paymentContacts || []).find((c) => String(c.id) === select.value);
                if (!contact) {
                    hint.textContent = '';
                    return;
                }
                if (contact.balance > 0) {
                    hint.textContent = 'Güncel bakiye: ' +
                        new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(contact.balance) +
                        ' ₺ — tahsilat bu tutarı aşamaz.';
                    hint.className = 'mt-1.5 text-xs font-medium text-slate-500';
                } else {
                    hint.textContent = 'Bu carinin tahsil edilecek bakiyesi yok.';
                    hint.className = 'mt-1.5 text-xs font-medium text-red-500';
                }
            };

            select.addEventListener('change', updateHint);
            updateHint();
        })();
    </script>
@endsection
