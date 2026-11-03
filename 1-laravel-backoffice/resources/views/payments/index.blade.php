@extends('layouts.app')
@section('title', 'Tahsilatlar')

@section('actions')
    <button type="button" class="btn-primary" data-modal-url="{{ route('payments.create') }}" data-modal-title="Yeni Tahsilat">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Tahsilat
    </button>
@endsection

@section('content')
    @php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    @endphp

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Cari</th>
                    <th>Tür</th>
                    <th>Yön</th>
                    <th class="!text-right">Tutar</th>
                    <th>Açıklama</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($payments as $payment)
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap">{{ $payment->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            <a href="{{ route('cariler.show', $payment->contact) }}" class="font-medium text-indigo-600 hover:text-indigo-700">
                                {{ $payment->contact->name }}
                            </a>
                        </td>
                        <td>
                            <span class="{{ $payment->isCash() ? 'badge-green' : 'badge-indigo' }}">{{ $payment->typeLabel() }}</span>
                        </td>
                        <td>
                            <span class="{{ $payment->isCollection() ? 'text-xs font-semibold text-emerald-600' : 'text-xs font-semibold text-amber-600' }}">
                                {{ $payment->directionLabel() }}
                            </span>
                        </td>
                        <td class="text-right font-semibold tabular-nums {{ $payment->isCollection() ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ $payment->isCollection() ? '+' : '−' }}{{ $fmt($payment->amount) }}
                        </td>
                        <td class="max-w-xs truncate text-slate-500" title="{{ $payment->description }}">{{ $payment->description ?: '—' }}</td>
                    </tr>
                @empty
                    @include('partials.empty', ['colspan' => 6, 'icon' => 'cash', 'title' => 'Henüz tahsilat yok', 'desc' => 'Cari bakiyelerini kapatmak için ilk tahsilatı kaydedin — nakit veya banka.', 'action' => 'Yeni Tahsilat Gir', 'actionUrl' => 'http://localhost/tahsilatlar/create', 'actionTitle' => 'Yeni Tahsilat'])
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $payments->links() }}
        </div>
    </div>
@endsection
