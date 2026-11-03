@extends('layouts.app')
@section('title', 'Etkinlik Günlüğü')

@section('content')
    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <select name="action" class="input sm:w-44">
            <option value="">Tüm işlemler</option>
            @foreach (['created' => 'Oluşturma', 'updated' => 'Güncelleme', 'deleted' => 'Silme', 'restored' => 'Geri Yükleme'] as $value => $label)
                <option value="{{ $value }}" @selected($filters['action'] === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="model" class="input sm:w-44">
            <option value="">Tüm kayıt türleri</option>
            @foreach (['Contact' => 'Cariler', 'Product' => 'Ürünler', 'Invoice' => 'Faturalar', 'Payment' => 'Tahsilat/Ödeme', 'Expense' => 'Giderler', 'Quote' => 'Teklifler', 'RecurringInvoice' => 'Tekrarlayan', 'User' => 'Kullanıcılar', 'Company' => 'Şirket'] as $value => $label)
                <option value="{{ $value }}" @selected($filters['model'] === $value)>{{ $label }}</option>
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
                    <th>Kullanıcı</th>
                    <th>İşlem</th>
                    <th>Kayıt</th>
                    <th>Değişiklik</th>
                    <th>IP</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($logs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap">{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                        <td>
                            <span class="font-medium text-slate-700">{{ $log->user?->name ?? 'Sistem' }}</span>
                        </td>
                        <td>
                            <span class="{{ match($log->action) {
                                'created' => 'badge-green',
                                'updated' => 'badge-indigo',
                                'deleted' => 'badge-red',
                                'restored' => 'badge-amber',
                                default => 'badge-slate',
                            } }}">{{ $log->actionLabel() }}</span>
                        </td>
                        <td class="text-slate-600">{{ $log->description }}</td>
                        <td class="max-w-xs">
                            @if ($log->changes)
                                <span class="text-[11px] text-slate-500" title="{{ json_encode($log->changes, JSON_UNESCAPED_UNICODE) }}">
                                    {{ collect($log->changes)->keys()->implode(', ') }}
                                </span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="font-mono text-xs text-slate-400">{{ $log->ip }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-slate-400">Henüz denetim kaydı yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $logs->links() }}
        </div>
    </div>
@endsection
