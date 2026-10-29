@extends('layouts.app')
@section('title', 'Webhook Teslimatları')

@section('actions')
    <a href="{{ route('sistem.index') }}" class="btn-secondary">Sistem Sağlığı</a>
@endsection

@section('content')
    {{-- Özet kartları --}}
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="card p-5">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Toplam Gönderim</div>
            <div class="mt-1 text-3xl font-bold text-slate-800">{{ $summary['toplam'] }}</div>
            <div class="mt-1 text-xs text-slate-400">Entegrasyona gönderilen tüm olaylar</div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Başarılı</div>
            <div class="mt-1 text-3xl font-bold text-emerald-600">{{ $summary['basarili'] }}</div>
            <div class="mt-1 text-xs text-slate-400">2xx yanıtı alanlar</div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Başarısız</div>
            <div class="mt-1 text-3xl font-bold {{ $summary['basarisiz'] > 0 ? 'text-red-600' : 'text-slate-400' }}">{{ $summary['basarisiz'] }}</div>
            <div class="mt-1 text-xs text-slate-400">Yeniden gönderilebilir</div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Son 24 Saat</div>
            <div class="mt-1 text-3xl font-bold text-indigo-600">{{ $summary['son_24s'] }}</div>
            <div class="mt-1 text-xs text-slate-400">Günlük trafik</div>
        </div>
    </div>

    {{-- Teslimat listesi --}}
    <div class="card mt-6">
        <div class="card-header flex items-center justify-between">
            <span class="flex items-center gap-2">
                <svg class="h-5 w-5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Teslimat Geçmişi
            </span>
            <div class="flex items-center gap-1.5">
                <a href="{{ route('webhooklar.index') }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ ! $filter ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700' }}">Tümü</a>
                <a href="{{ route('webhooklar.index', ['durum' => 'basarisiz']) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $filter === 'basarisiz' ? 'bg-red-600 text-white' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700' }}">Başarısız</a>
                <a href="{{ route('webhooklar.index', ['durum' => 'basarili']) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $filter === 'basarili' ? 'bg-emerald-600 text-white' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700' }}">Başarılı</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Olay</th>
                    <th>Hedef URL</th>
                    <th>Durum</th>
                    <th>Yanıt</th>
                    <th>Deneme</th>
                    <th>Zaman</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($deliveries as $delivery)
                    <tr class="{{ $delivery->success ? 'hover:bg-slate-50' : 'bg-red-50/50' }}">
                        <td>
                            <span class="font-mono text-xs font-bold text-indigo-600">{{ $delivery->event }}</span>
                            <details class="mt-1">
                                <summary class="cursor-pointer text-[11px] text-slate-400 hover:text-slate-600">payload</summary>
                                <pre class="mt-1 max-w-md overflow-x-auto rounded-lg bg-slate-900 p-3 text-[11px] leading-relaxed text-emerald-300">{{ json_encode($delivery->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </details>
                        </td>
                        <td class="max-w-[220px] truncate font-mono text-xs text-slate-500" title="{{ $delivery->url }}">{{ $delivery->url }}</td>
                        <td>
                            @if ($delivery->success)
                                <span class="badge-green">Başarılı</span>
                            @else
                                <span class="badge-red">Başarısız</span>
                            @endif
                        </td>
                        <td>
                            <span class="font-mono text-xs font-semibold {{ $delivery->response_code >= 200 && $delivery->response_code < 300 ? 'text-emerald-600' : 'text-red-600' }}">{{ $delivery->response_code ?: '—' }}</span>
                        </td>
                        <td class="text-center">{{ $delivery->attempts }}×</td>
                        <td class="text-xs text-slate-500">{{ $delivery->delivered_at?->format('d.m.Y H:i:s') ?? '—' }}</td>
                        <td class="text-right">
                            @if (! $delivery->success)
                                <form method="POST" action="{{ route('webhooklar.retry', $delivery) }}">
                                    @csrf
                                    <button type="submit" class="btn-secondary !py-1.5 !px-3 text-xs">Yeniden Gönder</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center text-slate-400">
                            Henüz webhook gönderimi yok.
                            Sipariş ödendiğinde/iptal edildiğinde ve stok değiştiğinde otomatik gönderilir
                            (Ayarlar → Entegrasyon'dan URL tanımlayın).
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($deliveries->hasPages())
            <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-700">
                {{ $deliveries->links() }}
            </div>
        @endif
    </div>
@endsection
