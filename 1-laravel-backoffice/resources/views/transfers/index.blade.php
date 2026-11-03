@extends('layouts.app')
@section('title', 'Kasa ↔ Banka Virmanları')

@section('actions')
    <a href="#yeni-virman" class="btn-primary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
        </svg>
        Yeni Virman
    </a>
@endsection

@section('content')
    @php
        $fmt = fn ($v) => number_format((float) $v, 2, ',', '.').' ₺';
    @endphp

    {{-- Yeni virman formu --}}
    <div class="card mb-4 p-5" id="yeni-virman">
        <h3 class="mb-1 font-bold text-slate-800">Para Transferi</h3>
        <p class="mb-4 text-sm text-slate-500">Kasa (nakit) ile Banka (havale) arasında virman — Raporlar'daki kasa durumu otomatik güncellenir.</p>

        <form method="POST" action="{{ route('virmanlar.store') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            @csrf
            <div>
                <label class="label" for="direction">Yön</label>
                <select id="direction" name="direction" class="input" required>
                    <option value="nakit_havale">Kasa → Banka</option>
                    <option value="havale_nakit">Banka → Kasa</option>
                </select>
            </div>
            <div>
                <label class="label" for="amount">Tutar (₺) <span class="text-red-500">*</span></label>
                <input type="number" id="amount" name="amount" min="0.01" step="0.01" required class="input">
            </div>
            <div>
                <label class="label" for="transfer_date">Tarih <span class="text-red-500">*</span></label>
                <input type="date" id="transfer_date" name="transfer_date" value="{{ old('transfer_date', now()->toDateString()) }}" required class="input">
            </div>
            <div>
                <label class="label" for="description">Açıklama</label>
                <input type="text" id="description" name="description" class="input" placeholder="Örn: bankaya para yatırma">
            </div>
            <div class="sm:col-span-4">
                <button type="submit" class="btn-primary">Transferi Kaydet</button>
            </div>
        </form>
    </div>

    {{-- Liste --}}
    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Yön</th>
                    <th>Açıklama</th>
                    <th class="!text-right">Tutar</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($transfers as $transfer)
                    <tr class="hover:bg-slate-50">
                        <td>{{ $transfer->transfer_date->format('d.m.Y') }}</td>
                        <td>
                            <span class="{{ $transfer->isToBank() ? 'badge-indigo' : 'badge-green' }}">{{ $transfer->directionLabel() }}</span>
                        </td>
                        <td class="text-slate-500">{{ $transfer->description ?: '—' }}</td>
                        <td class="text-right font-bold">{{ $fmt($transfer->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-10 text-center text-slate-400">Henüz virman kaydı yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $transfers->links() }}
        </div>
    </div>
@endsection
