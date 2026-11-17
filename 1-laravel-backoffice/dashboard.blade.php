@extends('layouts.app')
@section('title', 'Yönetim Paneli')

@section('actions')
    <button type="button" class="btn-primary" data-modal-url="{{ route('faturalar.create') }}" data-modal-title="Yeni Satış Faturası" data-modal-wide="1">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Fatura
    </button>
@endsection

@section('content')
    @php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
        $fmtKisa = function ($value) {
            $value = (float) $value;
            $isaret = $value < 0 ? '-' : '';
            $value = abs($value);
            if ($value >= 1_000_000) return $isaret.rtrim(rtrim(number_format($value / 1_000_000, 1, ',', '.'), '0'), ',').' M ₺';
            if ($value >= 1_000) return $isaret.rtrim(rtrim(number_format($value / 1_000, 1, ',', '.'), '0'), ',').' B ₺';
            return $isaret.number_format($value, 0, ',', '.').' ₺';
        };
    @endphp

    {{-- ═══════════ 1) KPI KARTLARI — 4 eşit kolon ═══════════ --}}
    @php
        // Büyük rakam + para birimi eki ayrı işlenir: sayı kibar (text-3xl), ek uyumlu küçük
        $kira = function ($value) {
            $v = (float) $value;
            $isaret = $v < 0 ? '-' : '';
            $v = abs($v);
            if ($v >= 1_000_000) {
                $m = rtrim(rtrim(number_format($v / 1_000_000, 1, ',', '.'), '0'), ',');
                return [$isaret.$m, 'M ₺'];
            }
            if ($v >= 1_000) {
                $m = rtrim(rtrim(number_format($v / 1_000, 1, ',', '.'), '0'), ',');
                return [$isaret.$m, 'B ₺'];
            }
            return [$isaret.number_format($v, 0, ',', '.'), '₺'];
        };
        [$cn, $ce] = $kira($ciro['aylik']);
        [$kn, $ke] = $kira($netKar['aylik']);
        [$an, $ae] = $kira($alacak['amount']);
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" role="list" aria-label="Kritik göstergeler">

        {{-- 1.1 Toplam Ciro: fiziksel + e-ticaret kırılımlı --}}
        <div class="card flex h-full flex-col justify-between p-6">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Bu Ay Ciro</div>
                    <div class="mt-1.5 flex items-baseline gap-1.5">
                        <span class="text-2xl xl:text-3xl leading-none font-extrabold tabular-nums text-slate-800 dark:text-slate-100">{{ $cn }}</span>
                        <span class="text-sm font-bold text-slate-400">{{ $ce }}</span>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 font-medium dark:text-slate-400">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                            Fiziksel/B2B <strong class="tabular-nums text-slate-700 dark:text-slate-200">{{ $fmtKisa($ciro['fiziksel']) }}</strong>
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-violet-500"></span>
                            E-Ticaret <strong class="tabular-nums text-violet-600 dark:text-violet-400">{{ $fmtKisa($ciro['eticaret']) }}</strong>
                        </span>
                    </div>
                    @if ($ciro['degisim'] !== null)
                        <div class="mt-3 inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold {{ $ciro['degisim'] >= 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-300' }}">
                            {{ $ciro['degisim'] >= 0 ? '▲' : '▼' }} geçen aya göre %{{ abs($ciro['degisim']) }}
                        </div>
                    @endif
                </div>
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13l2.5 2.5L9 12l3 3 4.5-5.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- 1.2 E-Ticaret Siparişleri: bekleyen (canlı) + onaylı --}}
        <div class="card flex h-full flex-col justify-between p-6">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">E-Ticaret Siparişleri</div>
                    <div class="mt-1.5 flex items-baseline gap-2">
                        <span class="relative inline-flex">
                            <span data-ozet="bekleyen" class="text-2xl xl:text-3xl leading-none font-extrabold tabular-nums {{ $siparis['bekleyen'] > 0 ? 'text-amber-600' : 'text-slate-800 dark:text-slate-100' }}">{{ $siparis['bekleyen'] }}</span>
                            @if ($siparis['bekleyen'] > 0)
                                <span class="absolute -right-3 top-0.5 flex h-2.5 w-2.5">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-60"></span>
                                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                </span>
                            @endif
                        </span>
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">bekliyor · {{ $fmtKisa($siparis['bekleyen_tutar']) }}</span>
                    </div>
                    <div class="mt-3 text-xs text-slate-500 font-medium dark:text-slate-400">
                        Onaylı: <strong data-ozet="onayli" class="text-indigo-600 dark:text-indigo-400">{{ $siparis['onayli'] }}</strong>
                        @if ($siparis['onayli'] > 0) · {{ $fmtKisa($siparis['onayli_tutar']) }} @endif
                    </div>
                </div>
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-violet-50 text-violet-600 dark:bg-violet-950/50 dark:text-violet-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- 1.3 Kargo & Sevk --}}
        <div class="card flex h-full flex-col justify-between p-6">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Kargo & Sevk</div>
                    <div class="mt-1.5 flex items-baseline gap-2">
                        <span data-ozet="sevk_bugun" class="text-2xl xl:text-3xl leading-none font-extrabold tabular-nums text-slate-800 dark:text-slate-100">{{ $kargo['bugun'] }}</span>
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">bugün sevk</span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500 font-medium dark:text-slate-400">
                        <span>Bu hafta: <strong data-ozet="sevk_hafta" class="text-slate-700 dark:text-slate-200">{{ $kargo['hafta'] }}</strong></span>
                        <span>Yolda: <strong data-ozet="yolda" class="text-indigo-600 dark:text-indigo-400">{{ $kargo['yolda'] }}</strong></span>
                    </div>
                </div>
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-sky-50 text-sky-600 dark:bg-sky-950/50 dark:text-sky-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H10a1 1 0 00-1 1v10M9 21h6M3 5h18v10a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm10 3h4"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- 1.4 Net Kâr --}}
        <div class="card flex h-full flex-col justify-between p-6">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Net Kâr <span class="font-medium normal-case text-slate-400">(tahmini)</span></div>
                    <div class="mt-1.5 flex items-baseline gap-1.5">
                        <span class="text-2xl xl:text-3xl leading-none font-extrabold tabular-nums {{ $netKar['aylik'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ $kn }}</span>
                        <span class="text-sm font-bold text-slate-400">{{ $ke }}</span>
                    </div>
                    @if ($netKar['marj'] !== null)
                        <div class="mt-2 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $netKar['marj'] >= 10 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' : ($netKar['marj'] >= 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300' : 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-300') }}">
                            Marj %{{ $netKar['marj'] }}
                        </div>
                    @endif
                    <div class="mt-3 text-xs text-slate-500 font-medium dark:text-slate-400">
                        Maliyet −{{ $fmtKisa($netKar['maliyet']) }} · Kargo −{{ $fmtKisa($netKar['kargo']) }} · Komisyon −{{ $fmtKisa($netKar['komisyon']) }}
                    </div>
                </div>
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ 1b) FİNANSAL ALT BANT — vadesi geçen (1/3) + kasa (2/3) ═══════════ --}}
    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">

        {{-- Vadesi geçen alacak/borç --}}
        <a href="{{ route('faturalar.index') }}" class="card flex h-full flex-col justify-between p-6 transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Vadesi Geçen Alacaklar</div>
                    <div class="mt-1.5 flex items-baseline gap-1.5">
                        <span class="text-2xl xl:text-3xl leading-none font-extrabold tabular-nums {{ $alacak['count'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-800 dark:text-slate-100' }}">{{ $an }}</span>
                        <span class="text-sm font-bold text-slate-400">{{ $ae }}</span>
                    </div>
                    <div class="mt-3 text-xs text-slate-500 font-medium dark:text-slate-400">
                        {{ $alacak['count'] }} fatura vadesi geçti · Borç: <strong class="text-amber-600 dark:text-amber-400">{{ $fmtKisa($borclar) }}</strong>
                    </div>
                </div>
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-500 dark:bg-red-950/50 dark:text-red-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </a>

        {{-- Kasa & bu ay nakit akışı --}}
        <div class="card flex h-full flex-col justify-between p-6 xl:col-span-2">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Kasa Durumu</div>
                    <div class="mt-2 grid grid-cols-2 gap-4">
                        <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Nakit</div>
                            <div class="mt-0.5 text-lg font-extrabold tabular-nums text-slate-800 dark:text-slate-100">{{ $fmtKisa($kasa['nakit']) }}</div>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Banka</div>
                            <div class="mt-0.5 text-lg font-extrabold tabular-nums text-slate-800 dark:text-slate-100">{{ $fmtKisa($kasa['banka']) }}</div>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 font-medium dark:text-slate-400">
                        <span>Bu ay tahsilat: <strong class="text-emerald-600 dark:text-emerald-400">+{{ $fmtKisa($nakitAkisi['tahsilat']) }}</strong></span>
                        <span>Gider: <strong class="text-red-600 dark:text-red-400">−{{ $fmtKisa($nakitAkisi['gider']) }}</strong></span>
                        <span>Net: <strong class="text-slate-800 dark:text-slate-100">{{ ($nakitAkisi['net'] >= 0 ? '+' : '').$fmtKisa($nakitAkisi['net']) }}</strong></span>
                    </div>
                </div>
                <div class="flex shrink-0 flex-col gap-2">
                    <a href="{{ route('payments.create') }}" data-modal-url="{{ route('payments.create') }}" data-modal-title="Yeni Tahsilat" class="inline-flex items-center justify-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-emerald-300 hover:text-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">+ Tahsilat</a>
                    <a href="{{ route('giderler.create') }}" data-modal-url="{{ route('giderler.create') }}" data-modal-title="Gider Kaydı" class="inline-flex items-center justify-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-red-300 hover:text-red-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">+ Gider</a>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ 2) GRAFİKLER ═══════════ --}}
    <div class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-3">
        {{-- Satış trendi: fiziksel vs e-ticaret (30 gün) --}}
        <div class="card xl:col-span-2">
            <div class="card-header flex items-center justify-between">
                <span class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-200">
                    <svg class="h-4.5 w-4.5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Satış Trendi — Son 30 Gün
                </span>
                <span class="flex items-center gap-4 text-[11px] font-semibold text-slate-400">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-slate-400"></span>Fiziksel/B2B</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-violet-500"></span>E-Ticaret</span>
                </span>
            </div>
            <div class="relative h-72 p-5">
                <canvas id="grafikTrend" aria-label="Son 30 günlük satış trendi grafiği" role="img"></canvas>
            </div>
        </div>

        {{-- Kargo / gönderi durumu dağılımı --}}
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <span class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-200">
                    <svg class="h-4 w-4 text-sky-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H10a1 1 0 00-1 1v10M9 21h6M3 5h18v10a2 2 0 01-2 2H5a2 2 0 01-2-2V5z"/></svg>
                    Gönderi Durumu
                </span>
                <span class="text-[11px] font-semibold text-slate-400">aktif siparişler</span>
            </div>
            <div class="relative mx-auto h-72 p-5">
                <canvas id="grafikDurum" aria-label="Gönderi durum dağılımı grafiği" role="img"></canvas>
            </div>
        </div>

        {{-- En çok satan ürünler --}}
        <div class="card xl:col-span-3">
            <div class="card-header flex items-center justify-between">
                <span class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-200">
                    <svg class="h-4 w-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h4m0 0l-4-4m4 4L13 3m5 12a9 9 0 11-18 0 9 9 0 0118 0z" transform="rotate(90 12 12)"/></svg>
                    En Çok Satan Ürünler — Bu Ay (adet)
                </span>
                <a href="{{ route('urunler.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Ürünlere git →</a>
            </div>
            <div class="relative h-64 p-5">
                <canvas id="grafikUrun" aria-label="En çok satan ürünler grafiği" role="img"></canvas>
            </div>
        </div>
    </div>

    {{-- ═══════════ 3) OPERASYONEL AKIŞ ═══════════ --}}
    <div class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-3">
        {{-- Son e-ticaret siparişleri --}}
        <div class="card xl:col-span-2">
            <div class="card-header flex items-center justify-between">
                <span class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-200">
                    <svg class="h-4.5 w-4.5 text-violet-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    Son E-Ticaret Siparişleri
                </span>
                <a href="{{ route('siparisler.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Tümü →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>Sipariş</th>
                        <th>Müşteri</th>
                        <th>Kanal</th>
                        <th>Kargo</th>
                        <th>Durum</th>
                        <th class="!text-right">Tutar</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($sonSiparisler as $order)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/40">
                            <td>
                                <a href="{{ route('siparisler.show', $order) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ $order->order_no }}</a>
                                <div class="text-[10px] text-slate-400">{{ $order->created_at->format('d.m H:i') }}</div>
                            </td>
                            <td class="max-w-40 truncate">{{ $order->customer_name ?: ($order->contact?->name ?? '—') }}</td>
                            <td><span class="inline-block rounded-full px-2 py-0.5 text-[10px] font-bold {{ $order->kanalRozetClass() }}">{{ $order->kanalEtiketi() }}</span></td>
                            <td class="text-xs text-slate-500 dark:text-slate-400">{{ $order->carrier ?: ($order->status === 'pending' ? '—' : 'Bekliyor') }}</td>
                            <td><span class="{{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span></td>
                            <td class="text-right font-semibold tabular-nums">{{ $fmt($order->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-slate-400">Henüz e-ticaret siparişi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Hızlı işlem menüsü + kritik stok --}}
        <div class="space-y-4">
            <div class="card p-4">
                <div class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Hızlı İşlem</div>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" data-modal-url="{{ route('faturalar.create') }}" data-modal-title="Yeni Satış Faturası" data-modal-wide="1"
                            class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2 py-3.5 text-center transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-indigo-600">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-300">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </span>
                        <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300">Yeni Fatura Kes</span>
                    </button>
                    <a href="{{ route('siparisler.index') }}"
                       class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2 py-3.5 text-center transition hover:-translate-y-0.5 hover:border-sky-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-sky-600">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-950/50 dark:text-sky-300">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H10a1 1 0 00-1 1v10M9 21h6M3 5h18v10a2 2 0 01-2 2H5a2 2 0 01-2-2V5z"/></svg>
                        </span>
                        <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300">Kargo / Sevk</span>
                    </a>
                    <a href="{{ route('urunler.index') }}"
                       class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2 py-3.5 text-center transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-emerald-600">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </span>
                        <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300">Stok Güncelle</span>
                    </a>
                    <button type="button" data-modal-url="{{ route('payments.create') }}" data-modal-title="Yeni Tahsilat"
                            class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2 py-3.5 text-center transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-amber-600">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-300">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </span>
                        <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300">Manuel Tahsilat</span>
                    </button>
                </div>
            </div>

            <div class="card p-4">
                <div class="mb-3 flex items-center justify-between">
                    <span class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Kritik Stok
                    </span>
                    <a href="{{ route('urunler.index') }}" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700">Ürünler →</a>
                </div>
                <ul class="space-y-1.5">
                    @forelse ($kritikStok as $product)
                        <li class="rounded-lg border border-red-100 bg-red-50/60 px-3 py-2 dark:border-red-900/40 dark:bg-red-950/20">
                            <div class="flex items-center justify-between gap-2">
                                <a href="{{ route('urunler.edit', $product) }}" class="min-w-0 flex-1 truncate text-xs font-semibold text-slate-700 hover:text-indigo-600 dark:text-slate-200">
                                    {{ Str::limit($product->name, 26) }}
                                </a>
                                <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-bold text-red-700 dark:text-red-300">
                                    {{ $product->stock_quantity }} / min {{ $product->min_stock_level }}
                                </span>
                            </div>
                            <div class="mt-1.5 flex items-center justify-between gap-2">
                                <span class="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700 dark:bg-red-950/50 dark:text-red-300">⚠ Kritik</span>
                                @php $adet = $product->onerilenSiparisAdedi(); @endphp
                                <a href="{{ route('alislar.create', array_filter([
                                        'tedarikci' => $product->supplier_id,
                                        'urun' => $product->id,
                                        'adet' => $adet > 0 ? $adet : null,
                                    ])) }}"
                                   class="inline-flex items-center gap-1 rounded-full bg-black px-2.5 py-1 text-[10px] font-bold text-white transition hover:bg-black/80 dark:bg-white dark:text-black dark:hover:bg-white/80">
                                    🛒 Tedarikçiye Sipariş Oluştur{{ $adet > 0 ? " ({$adet} adet)" : '' }}
                                </a>
                            </div>
                        </li>
                    @empty
                        <li class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">Kritik seviyede ürün yok 👍</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- ═══════════ Chart.js ═══════════ --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    <script>
        // ---- Canlı sayaçlar: 30 sn'de bir sipariş/kargo sayaçlarını tazeler ----
        document.addEventListener('DOMContentLoaded', function () {
            const sayacGetir = () => fetch('/panel/ozet', { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => { if (!r.ok) throw 0; return r.json(); })
                .then(d => {
                    const v = d.data;
                    document.querySelectorAll('[data-ozet]').forEach(el => {
                        const deger = v[el.dataset.ozet];
                        if (deger !== undefined) el.textContent = deger;
                    });
                })
                .catch(() => {});
            sayacGetir();
            setInterval(sayacGetir, 30000);
        });

        (function () {
            let trendGrafik, durumGrafik, urunGrafik;

            const temaKoyuMu = () => document.documentElement.classList.contains('dark');
            const renkler = () => temaKoyuMu()
                ? { kalem: '#cbd5e1', izgara: 'rgba(148,163,184,.15)', koyuMetin: '#f1f5f9' }
                : { kalem: '#475569', izgara: 'rgba(100,116,139,.15)', koyuMetin: '#0f172a' };

            const fmtKisa = (v) => {
                v = Math.abs(v);
                if (v >= 1_000_000) return (v / 1_000_000).toFixed(1).replace('.', ',') + 'M';
                if (v >= 1_000) return (v / 1_000).toFixed(1).replace('.', ',') + 'B';
                return String(Math.round(v));
            };

            function grafikleriCiz() {
                if (typeof Chart === 'undefined') { setTimeout(grafikleriCiz, 150); return; }
                const r = renkler();
                const yaygin = { responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: r.kalem, boxWidth: 12, font: { size: 11 } } } } };

                [trendGrafik, durumGrafik, urunGrafik].forEach(g => g && g.destroy());

                trendGrafik = new Chart(document.getElementById('grafikTrend'), {
                    type: 'line',
                    data: {
                        labels: @json($grafik['trendEtiket']),
                        datasets: [
                            { label: 'Fiziksel/B2B', data: @json($grafik['trendFiziksel']), borderColor: '#94a3b8', backgroundColor: 'rgba(148,163,184,.08)', fill: true, tension: 0.35, pointRadius: 2, borderWidth: 2 },
                            { label: 'E-Ticaret', data: @json($grafik['trendEticaret']), borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,.10)', fill: true, tension: 0.35, pointRadius: 2, borderWidth: 2 },
                        ],
                    },
                    options: { ...yaygin,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: { ticks: { color: r.kalem, maxTicksLimit: 10, font: { size: 10 } }, grid: { display: false } },
                            y: { ticks: { color: r.kalem, callback: fmtKisa, font: { size: 10 } }, grid: { color: r.izgara } },
                        },
                        plugins: { legend: { labels: { color: r.kalem, boxWidth: 12, font: { size: 11 } } },
                            tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + new Intl.NumberFormat('tr-TR').format(c.parsed.y) + ' ₺' } } },
                    },
                });

                const durum = @json($grafik['durumDagilim']);
                durumGrafik = new Chart(document.getElementById('grafikDurum'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Hazırlanıyor', 'Kargoya Verildi', 'Teslim Edildi', 'İade / Sorunlu'],
                        datasets: [{ data: [durum.hazirlaniyor, durum.kargoda, durum.teslim, durum.sorunlu],
                            backgroundColor: ['#6366f1', '#38bdf8', '#10b981', '#f59e0b'], borderWidth: 2,
                            borderColor: temaKoyuMu() ? '#1e293b' : '#ffffff' }],
                    },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '62%',
                        plugins: { legend: { position: 'bottom', labels: { color: r.kalem, boxWidth: 12, font: { size: 11 } } } } },
                });

                const urunler = @json($grafik['enCokSatan']);
                urunGrafik = new Chart(document.getElementById('grafikUrun'), {
                    type: 'bar',
                    data: {
                        labels: urunler.map(u => u.ad.length > 22 ? u.ad.slice(0, 21) + '…' : u.ad),
                        datasets: [{ label: 'Adet', data: urunler.map(u => Number(u.adet)),
                            backgroundColor: 'rgba(99,102,241,.75)', hoverBackgroundColor: '#6366f1',
                            borderRadius: 6, maxBarThickness: 38 }],
                    },
                    options: { responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false },
                            tooltip: { callbacks: { afterLabel: (c) => new Intl.NumberFormat('tr-TR').format(Number(urunler[c.dataIndex].tutar)) + ' ₺' } } },
                        scales: {
                            x: { ticks: { color: r.kalem, font: { size: 10 } }, grid: { display: false } },
                            y: { beginAtZero: true, ticks: { color: r.kalem, precision: 0, font: { size: 10 } }, grid: { color: r.izgara } },
                        } },
                });
            }

            document.addEventListener('DOMContentLoaded', grafikleriCiz);

            // Dark/Light geçişinde grafikleri tema renkleriyle yeniden çiz
            new MutationObserver(() => grafikleriCiz()).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        })();
    </script>
@endsection
