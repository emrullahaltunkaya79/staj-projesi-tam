@extends('layouts.app')
@section('title', 'Ödeme Takvimi')

@section('content')
    @php
        $fmt = fn ($v) => number_format((float) $v, 2, ',', '.').' ₺';
        $MONTHS = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
        $WD = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
        $lead = ($cursor->dayOfWeek + 6) % 7;
        $days = $cursor->daysInMonth;
        $todayStr = now()->toDateString();

        // Önceki / sonraki ay linkleri
        $prevAy = $ay == 1 ? 12 : $ay - 1;
        $prevYil = $ay == 1 ? $yil - 1 : $yil;
        $nextAy = $ay == 12 ? 1 : $ay + 1;
        $nextYil = $ay == 12 ? $yil + 1 : $yil;
        $qs = fn ($a, $y, $g = null) => route('takvim.index') . '?ay=' . $a . '&yil=' . $y . ($g ? '&gun=' . $g : '');
    @endphp

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- SOL: Takvim (geniş) --}}
        <div class="xl:col-span-2">

        {{-- Ay başlığı --}}
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ $qs($prevAy, $prevYil) }}"
               class="flex h-9 w-9 items-center justify-center rounded-full text-slate-400 transition hover:bg-white hover:text-slate-800" title="Önceki ay">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="text-center">
                <div class="text-lg font-bold text-slate-800">{{ $MONTHS[$ay - 1] }} {{ $yil }}</div>
                <a href="{{ route('takvim.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Bugün</a>
            </div>
            <a href="{{ $qs($nextAy, $nextYil) }}"
               class="flex h-9 w-9 items-center justify-center rounded-full text-slate-400 transition hover:bg-white hover:text-slate-800" title="Sonraki ay">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        {{-- Takvim ızgarası: kesin 7 sütun --}}
        <div class="card p-5">
            <div class="mb-2 grid grid-cols-7 gap-1 text-center text-[11px] font-bold text-slate-400">
                @foreach ($WD as $i => $d)
                    <div class="{{ $i >= 5 ? 'text-rose-300' : '' }}">{{ $d }}</div>
                @endforeach
            </div>

            <div class="grid grid-cols-7 gap-1">
                {{-- Boş hücreler: ayın ilk gününden önceki hafta günleri --}}
                @for ($i = 0; $i < $lead; $i++)
                    <div class="flex h-11 items-center justify-center"></div>
                @endfor

                {{-- Ayın günleri --}}
                @for ($day = 1; $day <= $days; $day++)
                    @php
                        $key = $cursor->copy()->day($day)->toDateString();
                        $events = $dayEvents[$key] ?? [];
                        $hasOverdue = collect($events)->contains('tip', 'geciken');
                        $hasDue = collect($events)->contains('tip', 'vade');
                        $hasRecurring = collect($events)->contains('tip', 'kesim');
                        $isToday = $key === $todayStr;
                        $isSelected = $key === $selectedDay;
                        $isWeekend = (($lead + $day - 1) % 7) >= 5;
                    @endphp
                    <a href="{{ $qs($ay, $yil, $key) }}"
                       class="relative flex h-11 items-center justify-center rounded-lg text-sm font-medium transition
                           {{ $isSelected
                               ? 'bg-indigo-600 font-bold text-white shadow-md'
                               : ($isToday
                                   ? 'bg-indigo-100 font-bold text-indigo-700 ring-2 ring-indigo-300'
                                   : ($isWeekend ? 'text-rose-400 hover:bg-rose-50' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800')) }}">
                        {{ $day }}
                        @if (count($events) > 0)
                            <span class="absolute bottom-1 flex gap-0.5">
                                @if ($hasOverdue)<span class="h-1.5 w-1.5 rounded-full {{ $isSelected ? 'bg-rose-200' : 'bg-rose-500' }}"></span>@endif
                                @if ($hasDue)<span class="h-1.5 w-1.5 rounded-full {{ $isSelected ? 'bg-indigo-200' : 'bg-indigo-500' }}"></span>@endif
                                @if ($hasRecurring)<span class="h-1.5 w-1.5 rounded-full {{ $isSelected ? 'bg-amber-200' : 'bg-amber-500' }}"></span>@endif
                            </span>
                        @endif
                    </a>
                @endfor
            </div>

            {{-- Lejant --}}
            <div class="mt-3 flex items-center justify-center gap-5 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-indigo-500"></span> Yaklaşan</span>
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-rose-500"></span> Geciken</span>
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-500"></span> Tekrarlayan</span>
            </div>
        </div>
        </div>{{-- /takvim sol sütun --}}

        {{-- SAĞ: Yaklaşan ödemeler (dar) --}}
        <div class="card">
            @if ($selectedDay)
                @php
                    $selDate = \Carbon\Carbon::parse($selectedDay);
                    $selList = collect($dayEvents[$selectedDay] ?? []);
                @endphp
                <div class="card-header flex items-center justify-between">
                    <span>{{ $selDate->translatedFormat('d F Y l') }}</span>
                    <a href="{{ $qs($ay, $yil) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Kapat</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($selList as $ev)
                        <a href="{{ $ev['url'] }}" class="flex items-center gap-3 px-5 py-3 transition hover:bg-slate-50">
                            <span class="h-2 w-2 shrink-0 rounded-full {{ $ev['tip'] === 'geciken' ? 'bg-rose-500' : ($ev['tip'] === 'kesim' ? 'bg-amber-500' : 'bg-indigo-500') }}"></span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold text-slate-700">{{ $ev['baslik'] }}</div>
                                <div class="text-xs text-slate-400">{{ $ev['tip'] === 'kesim' ? 'Tekrarlayan fatura' : 'Fatura '.$ev['fatura_no'] }}</div>
                            </div>
                            <span class="shrink-0 text-sm font-bold {{ $ev['tip'] === 'geciken' ? 'text-rose-600' : 'text-slate-700' }}">{{ $fmt($ev['tutar']) }}</span>
                        </a>
                    @empty
                        <div class="px-5 py-6 text-center text-sm text-slate-400">Bu günde ödeme yok.</div>
                    @endforelse
                </div>
            @else
                <div class="card-header">Yaklaşan Ödemeler <span class="text-xs font-normal text-slate-400">— gecikenler + 30 gün</span></div>
                <div class="divide-y divide-slate-100">
                    @forelse ($list->take(10) as $item)
                        <a href="{{ $item['url'] }}" class="flex items-center gap-3 px-5 py-3 transition hover:bg-slate-50">
                            <div class="flex h-10 w-10 shrink-0 flex-col items-center justify-center rounded-lg
                                {{ $item['geciken'] ? 'bg-rose-100 text-rose-700' : ($item['bugun'] ? 'bg-amber-100 text-amber-700' : 'bg-indigo-50 text-indigo-700') }}">
                                <span class="text-sm font-bold leading-none">{{ $item['tarih']->day }}</span>
                                <span class="text-[9px] leading-none">{{ mb_substr($MONTHS[$item['tarih']->month - 1], 0, 3) }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold text-slate-700">{{ $item['cari'] }}</div>
                                <div class="text-xs text-slate-400">{{ $item['fatura_no'] }} · kalan {{ $fmt($item['tutar']) }}</div>
                            </div>
                            <span class="shrink-0 text-xs font-bold {{ $item['geciken'] ? 'text-rose-600' : ($item['bugun'] ? 'text-amber-600' : 'text-slate-400') }}">
                                {{ $item['bugun'] ? 'BUGÜN' : $item['gun'] }}
                            </span>
                        </a>
                    @empty
                        <div class="px-5 py-6 text-center text-sm text-slate-400">Yaklaşan ödeme yok. 👍</div>
                    @endforelse
                </div>
            @endif
        </div>

    </div>
@endsection
