@extends('layouts.shop')
@section('title', 'Sipariş '.$order['order_no'])

@section('content')
    @php
        $fmt = fn ($v) => number_format((float) $v, 2, ',', '.').' ₺';
        $adimlar = ['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'preparing' => 'Hazırlanıyor', 'shipped' => 'Kargoda', 'delivered' => 'Teslim'];
        $adimKeys = array_keys($adimlar);
        $aktifIndex = array_search($order['status'], $adimKeys);
        $yeni = request('yeni') === '1';
    @endphp

    @if ($yeni)
        <div class="mb-6 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4">
            <span class="text-2xl">🎉</span>
            <div>
                <div class="text-sm font-black text-emerald-800">Siparişiniz alındı — stoklar sizin adınıza rezerve edildi!</div>
                <div class="text-xs text-emerald-600">Sipariş numaranız: <strong class="font-mono">{{ $order['order_no'] }}</strong> · Ödeme için {{ now()->addMinutes(30)->format('H:i') }} tarihine kadar süreniz var (rezerv 30 dk).</div>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3.5 text-sm font-semibold text-emerald-800">✓ {{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-3.5 text-sm font-semibold text-red-700">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            {{-- Durum akışı --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm font-bold text-slate-800">
                        <span class="font-mono text-xs text-slate-400">#</span>{{ $order['order_no'] }}
                    </div>
                    @if ($order['status'] === 'cancelled')
                        <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600">İptal edildi</span>
                    @else
                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-600">{{ $order['status_label'] ?? $adimlar[$order['status']] }}</span>
                    @endif
                </div>

                @if ($order['status'] !== 'cancelled')
                    <div class="flex items-start">
                        @foreach ($adimlar as $key => $label)
                            @php $i = $loop->index; $tam = $i < $aktifIndex; $aktif = $i === $aktifIndex; @endphp
                            @if (! $loop->first)
                                <div class="mt-[18px] h-0.5 flex-1 {{ $tam || $aktif ? 'bg-indigo-500' : 'bg-slate-200' }}"></div>
                            @endif
                            <div class="flex w-16 shrink-0 flex-col items-center gap-1.5 sm:w-20">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs {{ $aktif ? 'border-indigo-600 bg-indigo-600 text-white' : ($tam ? 'border-indigo-400 bg-indigo-50 text-indigo-500' : 'border-slate-200 bg-white text-slate-300') }}">
                                    @if ($tam) ✓ @else {{ $i + 1 }} @endif
                                </div>
                                <span class="text-center text-[10px] font-bold leading-tight {{ $aktif ? 'text-indigo-600' : ($tam ? 'text-slate-500' : 'text-slate-300') }}">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Kalemler --}}
            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-5 py-3.5 text-sm font-bold text-slate-800">Sipariş İçeriği</div>
                <table class="w-full text-sm">
                    <tbody>
                    @foreach ($order['items'] ?? [] as $item)
                        <tr class="border-b border-slate-50 last:border-0">
                            <td class="px-5 py-3.5 font-semibold text-slate-700">{{ $item['product_name'] ?? 'Ürün' }}</td>
                            <td class="px-3 py-3.5 text-center text-slate-400">{{ ($item['quantity'] + 0) }} adet</td>
                            <td class="px-3 py-3.5 text-right text-slate-400 tabular-nums">{{ $fmt($item['unit_price'] ?? 0) }}</td>
                            <td class="px-5 py-3.5 text-right font-bold tabular-nums text-slate-800">{{ $fmt($item['line_total'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="h-fit space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-bold text-slate-800">Ödeme</div>
                @if ($order['status'] === 'pending')
                    <div class="mt-2 text-xs text-amber-600 font-semibold">Rezerv {{ \Carbon\Carbon::parse($order['expires_at'])->format('H:i') }}'e kadar geçerli</div>
                    <a href="{{ route('shop.payment', $order['order_no']) }}" class="mt-3 flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 text-sm font-bold text-white shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-500 active:scale-[.98]">⛓️ CSPR ile Öde</a><form method="POST" action="{{ route('shop.order.pay', $order['order_no']) }}" class="mt-2 hidden">
                        @csrf
                        <button type="submit" class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 text-sm font-bold text-white shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-500 active:scale-[.98]">
                            ⛓️ CSPR ile Öde
                        </button>
                    </form>
                    <form method="POST" action="{{ route('shop.order.cancel', $order['order_no']) }}" class="mt-2"
                          onsubmit="return confirm('Siparişi iptal etmek istediğinize emin misiniz?');">
                        @csrf
                        <button type="submit" class="h-10 w-full rounded-xl border border-slate-200 text-xs font-semibold text-slate-400 transition hover:border-red-200 hover:text-red-500">Siparişi İptal Et</button>
                    </form>
                @elseif ($order['status'] !== 'cancelled')
                    <div class="mt-3 flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">✓ Ödendi — Fatura: {{ $order['invoice_no'] ?? '—' }}</div>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm">
                <div class="font-bold text-slate-800">Kargo</div>
                @if (! empty($order['tracking_no']))
                    <div class="mt-2 text-xs text-slate-400">{{ $order['carrier'] ?? '' }}</div>
                    <div class="mt-1 font-mono text-sm font-bold text-slate-700">{{ $order['tracking_no'] }}</div>
                    @if (! empty($order['tracking_url']))
                        <a href="{{ $order['tracking_url'] }}" target="_blank" rel="noopener"
                           class="mt-3 flex h-10 items-center justify-center rounded-xl bg-slate-900 text-xs font-bold text-white transition hover:bg-slate-700">Kargomu Takip Et →</a>
                    @endif
                @else
                    <div class="mt-2 text-xs text-slate-400">Kargo bilgisi henüz girilmedi — arka ofis kargoladığında burada görünecek.</div>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm">
                <div class="font-bold text-slate-800">Toplam</div>
                <div class="mt-2 text-2xl font-black tabular-nums text-slate-900">{{ $fmt($order['total'] ?? 0) }}</div>
                <a href="{{ route('shop.home') }}" class="mt-4 block text-center text-xs font-semibold text-indigo-500 hover:text-indigo-600">← Alışverişe devam et</a>
            </div>
        </div>
    </div>
@endsection
