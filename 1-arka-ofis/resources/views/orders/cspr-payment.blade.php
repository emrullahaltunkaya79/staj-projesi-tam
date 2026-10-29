@extends('layouts.app')
@section('title', 'CSPR Ödeme — '.$order->order_no)

@section('actions')
    <a href="{{ route('siparisler.show', $order) }}" class="btn-secondary">← Siparişe Dön</a>
@endsection

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="card overflow-hidden">
            {{-- Başlık --}}
            <div class="bg-gradient-to-br from-purple-700 to-indigo-900 px-6 py-5 text-white">
                <div class="text-xs font-bold uppercase tracking-widest text-purple-200">Casper Network Ödemesi</div>
                <div class="mt-1 flex items-center justify-between">
                    <div class="text-2xl font-black">{{ number_format((float) $payment->amount_cspr, 4) }} CSPR</div>
                    <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-bold">{{ strtoupper($network) }}</span>
                </div>
                <div class="mt-1 text-sm text-purple-200">≈ {{ number_format((float) $payment->amount_try, 2, ',', '.') }} ₺</div>
            </div>

            <div class="p-6">
                {{-- Durum --}}
                <div class="mb-5 flex items-center gap-3 rounded-xl bg-slate-50 dark:bg-slate-750 p-4">
                    @if ($payment->status === 'waiting')
                        <span class="relative flex h-3 w-3">
                            <span class="absolute h-full w-full animate-ping rounded-full bg-amber-400 opacity-60"></span>
                            <span class="relative h-3 w-3 rounded-full bg-amber-500"></span>
                        </span>
                        <span class="font-semibold text-amber-700 dark:text-amber-300">Ödeme bekleniyor…</span>
                    @elseif ($payment->status === 'confirmed')
                        <span class="flex h-3 w-3 rounded-full bg-emerald-500"></span>
                        <span class="font-semibold text-emerald-700 dark:text-emerald-300">Ödeme onaylandı ✓</span>
                    @elseif ($payment->status === 'expired')
                        <span class="h-3 w-3 rounded-full bg-red-500"></span>
                        <span class="font-semibold text-red-600">Süre doldu</span>
                    @endif
                </div>

                {{-- Adres + memo --}}
                <div class="space-y-4">
                    <div>
                        <div class="label">Alıcı Adresi (Casper {{ strtoupper($network) }})</div>
                        <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-750 p-3 font-mono text-[11px] break-all">
                            {{ $address ?: '— Cüzdan adresi tanımlı değil: .env → CSPR_RECEIVING_ADDRESS —' }}
                        </div>
                    </div>

                    <div>
                        <div class="label">Ödeme Memo (transfer açıklamasına aynen yazın)</div>
                        <div class="rounded-lg border-2 border-indigo-300 bg-indigo-50 dark:bg-indigo-950/40 p-3 font-mono text-sm font-bold text-indigo-800 dark:text-indigo-200 break-all">
                            {{ $payment->memo }}
                        </div>
                        <p class="mt-1 text-xs text-slate-400">⚠ Memo olmadan ödeme siparişe otomatik bağlanamaz.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-slate-50 dark:bg-slate-750 p-3 text-center">
                            <div class="text-xs text-slate-500">İstenen</div>
                            <div class="text-lg font-bold text-slate-800 dark:text-slate-100">{{ number_format((float) $payment->amount_cspr, 4) }} CSPR</div>
                        </div>
                        <div class="rounded-lg bg-slate-50 dark:bg-slate-750 p-3 text-center">
                            <div class="text-xs text-slate-500">Karşılığı</div>
                            <div class="text-lg font-bold text-slate-800 dark:text-slate-100">{{ number_format((float) $payment->amount_try, 2, ',', '.') }} ₺</div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between rounded-lg border border-slate-200 dark:border-slate-700 p-3 text-sm">
                        <span class="text-slate-500">Geçerlilik süresi</span>
                        <span class="font-semibold">{{ $payment->expires_at->format('d.m.Y H:i') }}</span>
                    </div>
                </div>

                {{-- Aksiyonlar --}}
                <div class="mt-6 flex flex-col gap-2">
                    @if ($payment->status === 'waiting')
                        <form method="POST" action="{{ route('siparisler.cspr.check', [$order->id, $payment->id]) }}">
                            @csrf
                            <button type="submit" class="btn-primary w-full">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Ödemeyi Kontrol Et
                            </button>
                        </form>
                        @if (config('app.debug'))
                            <form method="POST" action="{{ route('siparisler.cspr.confirm', [$order->id, $payment->id]) }}">
                                @csrf
                                <button type="submit" class="btn-secondary w-full text-xs">[TEST] Elle Onayla</button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <p class="mt-3 text-center text-xs text-slate-400">
            Ödeme blok zincirde 1-2 dakikada görünür. Süre bitince sipariş otomatik iptal olur, stok serbest kalır.
        </p>
    </div>
@endsection
