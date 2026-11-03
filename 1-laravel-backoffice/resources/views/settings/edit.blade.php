@extends('layouts.app')
@section('title', 'Şirket Ayarları')

@section('content')
    <form method="POST" action="{{ route('ayarlar.update') }}" enctype="multipart/form-data" class="max-w-2xl">
        @csrf @method('PUT')

        <div class="card p-6">
            <h2 class="mb-1 text-lg font-bold text-slate-800">Şirket Ayarları</h2>
            <p class="mb-6 text-sm text-slate-500">Bu bilgiler kesilen faturalarda, PDF'lerde ve tekliflerde görünür.</p>

            <div class="mb-6 rounded-lg border border-slate-200 bg-slate-50 px-4 py-4">
                <div class="label !mb-2">Mevcut Logo</div>
                <div class="flex items-center gap-4">
                    @if ($company->logoUrl())
                        <img src="{{ $company->logoUrl() }}" alt="{{ $company->name }}" class="h-14 rounded-lg border border-slate-200 bg-white p-1">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remove_logo" value="1" class="h-4 w-4 rounded border-slate-300 text-red-600 focus:ring-red-500">
                            Logoyu kaldır
                        </label>
                    @else
                        <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-slate-200 text-lg font-black text-slate-400">ÖM</div>
                        <span class="text-sm text-slate-500">Logo yüklenmemiş — yüklerseniz faturalarda "ÖM" yerine görünür.</span>
                    @endif
                </div>
                <div class="mt-4">
                    <label class="label" for="logo">Yeni Logo (JPG / PNG / WebP, en fazla 4 MB)</label>
                    <input type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp"
                           class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                    @error('logo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label" for="name">Şirket Adı <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $company->name) }}" required class="input">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="tax_number">Vergi No</label>
                    <input type="text" id="tax_number" name="tax_number" value="{{ old('tax_number', $company->tax_number) }}" class="input">
                    @error('tax_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="phone">Telefon</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $company->phone) }}" class="input">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="label" for="address">Adres</label>
                    <textarea id="address" name="address" rows="3" class="input">{{ old('address', $company->address) }}</textarea>
                    @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn-primary">Kaydet</button>
                <span class="ml-auto"></span>
            </div>
        </div>
    </form>

    {{-- Ödeme & Entegrasyon --}}
    <div class="card mt-4 p-5">
        <h3 class="mb-1 font-bold text-slate-800 dark:text-slate-100">Ödeme & Entegrasyon</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">CSPR kripto ödeme ve e-ticaret webhook ayarları — değişiklikler anında geçerli olur.</p>

        <form method="POST" action="{{ route('ayarlar.integration') }}">
            @csrf
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label" for="casper_address">CSPR Alıcı Adresi</label>
                    <input type="text" id="casper_address" name="casper_address"
                           value="{{ old('casper_address', \App\Models\Setting::casperAddress()) }}"
                           class="input font-mono text-xs" placeholder="Casper cüzdan adresi">
                </div>

                <div>
                    <label class="label" for="casper_network">Casper Ağı</label>
                    <select id="casper_network" name="casper_network" class="input">
                        <option value="testnet" @selected(\App\Models\Setting::casperNetwork() === 'testnet')>Testnet (deneme)</option>
                        <option value="mainnet" @selected(\App\Models\Setting::casperNetwork() === 'mainnet')>Mainnet (gerçek)</option>
                    </select>
                </div>

                <div>
                    <label class="label" for="casper_rate">CSPR/TL Kuru</label>
                    <input type="number" id="casper_rate" name="casper_rate" step="0.0001" min="0.0001"
                           value="{{ old('casper_rate', \App\Models\Setting::casperRate()) }}" required class="input">
                </div>

                <div class="sm:col-span-2">
                    <label class="label" for="ecommerce_webhook_url">E-ticaret Webhook URL</label>
                    <input type="url" id="ecommerce_webhook_url" name="ecommerce_webhook_url"
                           value="{{ old('ecommerce_webhook_url', \App\Models\Setting::webhookUrl()) }}"
                           class="input font-mono text-xs" placeholder="https://siteniz.com/webhooks/panel">
                    <p class="mt-1 text-xs text-slate-400">Stok/sipariş olayları bu adrese HMAC imzalı POST ile gönderilir.</p>
                </div>
            </div>

            <div class="mt-5 flex items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn-primary">Entegrasyonu Kaydet</button>
            </div>
        </form>
    </div>

    {{-- Yedekleme --}}
    <div class="card mt-4 p-6">
        <h3 class="mb-1 font-bold text-slate-800 dark:text-slate-100">Yedekleme</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Veritabanı yedeği (zip) storage/app/backups altına yazılır; haftalık Schedule da ayarlıdır.</p>
        <form method="POST" action="{{ route('ayarlar.backup') }}">
            @csrf
            <button type="submit" class="btn-secondary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Şimdi Yedekle
            </button>
        </form>
    </div>
@endsection
