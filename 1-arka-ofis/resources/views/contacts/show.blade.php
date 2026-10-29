@extends('layouts.app')
@section('title', 'Cari Ekstre: '.$contact->name)

@php
    $isCustomer = $contact->isCustomer();
@endphp

@section('actions')
    <a href="{{ route('cariler.edit', $contact) }}" class="btn-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
        Düzenle
    </a>
    @if ($isCustomer)
        <a href="{{ route('payments.create', ['contact_id' => $contact->id]) }}" data-modal-url="{{ route('payments.create', ['contact_id' => $contact->id]) }}" data-modal-title="Yeni Tahsilat" class="btn-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Tahsilat Al
        </a>
    @else
        <a href="{{ route('payments.create_supplier', ['contact_id' => $contact->id]) }}" data-modal-url="{{ route('payments.create_supplier', ['contact_id' => $contact->id]) }}" data-modal-title="Tedarikçi Ödemesi" class="btn-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Ödeme Yap
        </a>
    @endif
@endsection

@section('content')
    @php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
        $balance = (float) $contact->balance;
    @endphp

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Cari bilgileri --}}
        <div class="card lg:col-span-2">
            <div class="card-header flex items-center justify-between">
                <span>Cari Bilgileri</span>
                <span class="{{ $contact->isCustomer() ? 'badge-indigo' : 'badge-slate' }}">{{ $contact->typeLabel() }}</span>
            </div>
            <div class="grid grid-cols-1 gap-x-6 gap-y-4 p-5 sm:grid-cols-2">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Ünvan</div>
                    <div class="mt-1 text-lg font-bold text-slate-800">{{ $contact->name }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Vergi / TC No</div>
                    <div class="mt-1 font-mono text-sm text-slate-700">{{ $contact->tax_number ?: '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Telefon</div>
                    <div class="mt-1 text-sm text-slate-700">{{ $contact->phone ?: '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">E-posta</div>
                    <div class="mt-1 text-sm text-slate-700">{{ $contact->email ?: '—' }}</div>
                </div>
                <div class="sm:col-span-2">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Adres</div>
                    <div class="mt-1 text-sm text-slate-700">{{ $contact->address ?: '—' }}</div>
                </div>
            </div>
        </div>

        {{-- Güncel bakiye / borç özeti --}}
        <div class="card p-5">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Güncel Bakiye</div>
            <div class="mt-2 text-3xl font-bold {{ $balance > 0 ? 'text-emerald-600' : ($balance < 0 ? 'text-red-600' : 'text-slate-700') }}">
                {{ $balance > 0 ? '+' : '' }}{{ $fmt($balance) }}
            </div>
            <div class="mt-1 text-sm {{ $balance > 0 ? 'text-emerald-600' : ($balance < 0 ? 'text-red-600' : 'text-slate-500') }}">
                @if ($balance > 0)
                    Cari size borçlu (alacak)
                @elseif ($balance < 0)
                    {{ $isCustomer ? 'Cariye borçlusunuz' : 'Tedarikçiye borçlusunuz' }}
                @else
                    Borç / alacak yok
                @endif
            </div>

            <dl class="mt-5 space-y-2.5 border-t border-slate-100 pt-4 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Kesilen fatura</dt>
                    <dd class="font-semibold text-slate-700">{{ $summary['invoice_count'] }} adet</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Toplam fatura tutarı</dt>
                    <dd class="font-semibold text-slate-700">{{ $fmt($summary['invoice_total']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Toplam {{ $isCustomer ? 'tahsilat' : 'ödeme' }}</dt>
                    <dd class="font-semibold text-emerald-600">{{ $fmt($summary['payment_total']) }}</dd>
                </div>
                <div class="border-t border-slate-100 pt-2.5"></div>
                <div class="pt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Yaşlandırma (kalan alacak)</div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Vadesi gelmemiş</dt>
                    <dd>{{ $fmt($summary['aging']['vadesi_gecmedi']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">1-30 gün gecikmiş</dt>
                    <dd class="{{ $summary['aging']['gun_1_30'] > 0 ? 'font-semibold text-red-600' : '' }}">{{ $fmt($summary['aging']['gun_1_30']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">31-60 gün gecikmiş</dt>
                    <dd class="{{ $summary['aging']['gun_31_60'] > 0 ? 'font-semibold text-red-600' : '' }}">{{ $fmt($summary['aging']['gun_31_60']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">60+ gün gecikmiş</dt>
                    <dd class="{{ $summary['aging']['gun_60_ustu'] > 0 ? 'font-semibold text-red-600' : '' }}">{{ $fmt($summary['aging']['gun_60_ustu']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Son işlem tarihi</dt>
                    <dd class="font-semibold text-slate-700">{{ $summary['last_invoice_date'] ? \Carbon\Carbon::parse($summary['last_invoice_date'])->format('d.m.Y') : '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Ekstre: geçmiş faturalar --}}
    <div class="card mt-6">
        <div class="card-header flex items-center justify-between">
            <span class="flex items-center gap-2">
                <svg class="h-5 w-5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Ekstre — {{ $contact->name }}
            </span>
            <span class="text-xs font-normal text-slate-400">Bu cariye kesilen faturalar</span>
        </div>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Fatura No</th>
                    <th>Tarih</th>
                    <th>Vade</th>
                    <th class="!text-right">Ara Toplam</th>
                    <th class="!text-right">KDV</th>
                    <th class="!text-right">Tutar</th>
                    <th class="!text-right">Kalan</th>
                    <th>Durum</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($invoices as $invoice)
                    @php
                        $state = $statuses[$invoice->id] ?? ['status' => 'acik', 'remaining' => (float) $invoice->total, 'days_overdue' => 0];
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td>
                            <a href="{{ route('faturalar.show', $invoice) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ $invoice->invoice_no }}</a>
                        </td>
                        <td>{{ $invoice->issue_date->format('d.m.Y') }}</td>
                        <td>
                            @if ($invoice->due_date)
                                <span class="{{ $state['days_overdue'] > 0 && $state['remaining'] > 0 ? 'font-semibold text-red-600' : 'text-slate-600' }}">
                                    {{ $invoice->due_date->format('d.m.Y') }}
                                </span>
                            @else
                                <span class="text-slate-400">açık hesap</span>
                            @endif
                        </td>
                        <td class="text-right">{{ $fmt($invoice->subtotal) }}</td>
                        <td class="text-right">{{ $fmt($invoice->tax_amount) }}</td>
                        <td class="text-right font-bold">{{ $fmt($invoice->total) }}</td>
                        <td class="text-right {{ $state['remaining'] > 0 ? 'font-semibold text-slate-700' : 'text-slate-400' }}">
                            {{ $state['remaining'] > 0 ? $fmt($state['remaining']) : '—' }}
                        </td>
                        <td>
                            @if ($state['days_overdue'] > 0 && $state['remaining'] > 0)
                                <span class="badge-red">{{ $state['days_overdue'] }} gün gecikti</span>
                            @elseif ($state['status'] === 'kapali')
                                <span class="badge-green">Kapandı</span>
                            @elseif ($state['status'] === 'kismi')
                                <span class="badge-amber">Kısmi ödendi</span>
                            @else
                                <span class="badge-amber">Açık</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-10 text-center text-slate-400">Bu cariye henüz fatura kesilmedi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($invoices->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

    {{-- {{ $isCustomer ? 'Tahsilat' : 'Ödeme' }} geçmişi --}}
    <div class="card mt-6">
        <div class="card-header flex items-center justify-between">
            <span class="flex items-center gap-2">
                <svg class="h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                {{ $isCustomer ? 'Tahsilat' : 'Ödeme' }} Geçmişi
            </span>
            <a href="{{ route($isCustomer ? 'payments.create' : 'payments.create_supplier', ['contact_id' => $contact->id]) }}"
               data-modal-url="{{ route($isCustomer ? 'payments.create' : 'payments.create_supplier', ['contact_id' => $contact->id]) }}"
               data-modal-title="{{ $isCustomer ? 'Yeni Tahsilat' : 'Tedarikçi Ödemesi' }}"
               class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">+ {{ $isCustomer ? 'Tahsilat' : 'Ödeme' }} Ekle</a>
        </div>
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Tür</th>
                    <th class="!text-right">Tutar</th>
                    <th>Açıklama</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($payments as $payment)
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap">{{ $payment->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            <span class="{{ $payment->isCash() ? 'badge-green' : 'badge-indigo' }}">{{ $payment->typeLabel() }}</span>
                        </td>
                        <td class="text-right font-semibold text-emerald-600">+{{ $fmt($payment->amount) }}</td>
                        <td class="text-slate-500">{{ $payment->description ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-10 text-center text-slate-400">Bu cariden henüz tahsilat yapılmadı.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-3 text-xs text-slate-400">
        Not: Tahsilatlar cari bakiyesinden otomatik düşer; fatura durumları en eski faturadan başlayarak (FIFO) kapatılır.
    </p>
@endsection
