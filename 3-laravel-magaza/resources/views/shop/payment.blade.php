@extends('layouts.shop')
@section('title', 'CSPR ile Öde')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-4 text-sm text-slate-400">
            <a href="{{ route('shop.order', $orderNo) }}" class="font-semibold text-indigo-500 hover:text-indigo-600">← Siparişe dön</a>
        </div>

        @if ($error)
            <div class="flex flex-col items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-6 py-14 text-center">
                <span class="text-4xl">⛓️</span>
                <div class="text-base font-bold text-amber-800">Ödeme talimatı alınamadı</div>
                <p class="max-w-md text-sm text-amber-700">{{ $error }}</p>
            </div>
        @else
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="flex items-center justify-between bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-5 text-white">
                    <div>
                        <div class="text-sm font-black uppercase tracking-wide">CSPR ile Öde</div>
                        <div class="text-xs text-indigo-200">Casper Network · {{ $payment['network'] }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-black tabular-nums">{{ rtrim(rtrim(number_format($payment['amount_cspr'], 9, ',', '.'), '0'), ',') }} CSPR</div>
                        <div class="text-xs text-indigo-200">≈ {{ number_format($payment['amount_try'], 2, ',', '.') }} ₺ (1 CSPR = {{ number_format($payment['rate'], 2, ',', '.') }} ₺)</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-5">
                    <div class="flex flex-col items-center gap-2 sm:col-span-2">
                        <div class="rounded-2xl border-4 border-slate-100 p-2">
                            <img src="{{ $payment['qr_data_uri'] }}" alt="Ödeme QR" class="h-40 w-40">
                        </div>
                        <span class="text-[11px] font-semibold text-slate-400">Casper Wallet ile tara</span>
                        <span id="countdown" data-expires="{{ $payment['expires_at'] }}" class="rounded-full bg-slate-100 px-3 py-1 font-mono text-xs font-bold text-slate-600">--:--</span>
                    </div>

                    <div class="space-y-4 sm:col-span-3">
                        <div>
                            <div class="mb-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">Alıcı Adresi</div>
                            <div class="flex items-center gap-2">
                                <code class="min-w-0 flex-1 truncate rounded-xl bg-slate-900 px-3 py-2.5 font-mono text-[11px] text-emerald-300">{{ $payment['address'] }}</code>
                                <button type="button" onclick="kopyala(this, '{{ $payment['address'] }}')" class="btn-kopya shrink-0 rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-bold text-slate-500 hover:border-indigo-400 hover:text-indigo-600">Kopyala</button>
                            </div>
                        </div>

                        <div>
                            <div class="mb-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">MEMO (zorunlu — ödemeyi siparişe bağlar)</div>
                            <div class="flex items-center gap-2">
                                <code id="memo" class="min-w-0 flex-1 truncate rounded-xl bg-amber-50 border border-amber-200 px-3 py-2.5 font-mono text-xs font-bold text-amber-700">{{ $payment['memo'] }}</code>
                                <button type="button" onclick="kopyala(this, '{{ $payment['memo'] }}')" class="btn-kopya shrink-0 rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-bold text-slate-500 hover:border-indigo-400 hover:text-indigo-600">Kopyala</button>
                            </div>
                        </div>

                        <div id="status-box" class="flex items-center gap-2 rounded-xl bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-500">
                            <span class="relative flex h-2.5 w-2.5"><span class="absolute h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span><span class="relative h-2.5 w-2.5 rounded-full bg-amber-500"></span></span>
                            <span id="status-text">Ödeme bekleniyor — cüzdanınızdan gönderim yapın…</span>
                        </div>

                        <p class="text-[11px] leading-relaxed text-slate-400">
                            Doğru tutarda ve <strong>aynen bu memo ile</strong> gönderilen ödeme zincirde görüldüğünde siparişiniz
                            otomatik onaylanır ve faturanız kesilir. Bu ekranı kapatmanız gerekmez.
                        </p>

                        <form method="POST" action="{{ route('shop.order.pay', $orderNo) }}" class="border-t border-slate-100 pt-3">
                            @csrf
                            <button type="submit" class="text-[11px] font-semibold text-slate-300 underline-offset-2 hover:text-indigo-400 hover:underline">
                                Demo: ödemeyi manuel onayla (test)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        const MEMO = {!! json_encode($payment['memo'] ?? '') !!};
        const ORDER_NO = {!! json_encode($orderNo) !!};

        function kopyala(btn, metin) {
            navigator.clipboard.writeText(metin).then(() => {
                const eski = btn.textContent;
                btn.textContent = 'Kopyalandı ✓';
                setTimeout(() => btn.textContent = eski, 1500);
            });
        }

        // geri sayım
        const cd = document.getElementById('countdown');
        if (cd) {
            const bitis = new Date(cd.dataset.expires);
            const tick = () => {
                const kalan = Math.max(0, Math.floor((bitis - Date.now()) / 1000));
                const d = Math.floor(kalan / 60), s = kalan % 60;
                cd.textContent = d + ':' + String(s).padStart(2, '0');
                if (kalan === 0) clearInterval(zamanlayici);
            };
            const zamanlayici = setInterval(tick, 1000); tick();
        }

        // durum yoklaması
        async function yokla() {
            if (!MEMO) return;
            try {
                const r = await fetch(`/siparis/${ORDER_NO}/odeme/durum?memo=${encodeURIComponent(MEMO)}`);
                const d = await r.json();
                if (d.paid) {
                    location.href = '{{ route('shop.order', $orderNo) }}';
                }
            } catch (e) { /* sessiz */ }
        }
        setInterval(yokla, 5000);
    </script>
@endsection
