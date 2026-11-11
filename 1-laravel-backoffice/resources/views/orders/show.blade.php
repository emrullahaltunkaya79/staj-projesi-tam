@extends('layouts.app')
@section('title', 'Sipariş '.$order->order_no)

@section('actions')
    <a href="{{ route('siparisler.index') }}" class="btn-secondary">← Siparişler</a>

    @php $odemeKisa = ['cspr' => 'CSPR', 'kapida' => 'Kapıda Ödeme', 'havale' => 'Havale / EFT']; @endphp

    @if (in_array($order->status, ['paid', 'preparing', 'shipped', 'delivered']))
        <a href="{{ route('siparisler.etiket', $order) }}" data-turbo="false" class="btn-secondary" title="Kargo kutu etiketini PDF olarak indir">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            Kargo Etiketi
        </a>
    @endif

    @if ($order->status === 'pending' && $order->payment_method === 'cspr')
        <form method="POST" action="{{ route('siparisler.cspr.start', $order) }}">
            @csrf
            <button type="submit" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                CSPR ile Öde
            </button>
        </form>
    @endif

    @if ($order->status === 'pending')
        <form method="POST" action="{{ route('siparisler.odendi', $order) }}">
            @csrf
            <button type="submit" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $order->payment_method === 'havale' ? 'Havaleyi Onayla — Faturalaştır' : ($order->payment_method === 'kapida' ? 'Kapıda Ödendi — Faturalaştır' : 'Ödendi — Faturalaştır') }}
            </button>
        </form>
        <form method="POST" action="{{ route('siparisler.iptal', $order) }}"
              onsubmit="return confirm('Siparişi iptal etmek istediğinize emin misiniz? Rezerve stok serbest bırakılacak.');">
            @csrf
            <button type="submit" class="btn-danger">İptal</button>
        </form>
    @endif

    {{-- Kapıda ödemede tahsilat kapıda yapılır: kargola eylemi otomatik faturalandırır (sevk ile fatura) --}}

    @if ($order->status === 'paid')
        <form method="POST" action="{{ route('siparisler.hazirla', $order) }}">
            @csrf
            <button type="submit" class="btn-secondary">Hazırlığa Al</button>
        </form>
    @endif

    @if (in_array($order->status, ['paid', 'preparing']) || ($order->status === 'pending' && $order->payment_method === 'kapida'))
        <form method="POST" action="{{ route('siparisler.kargo', $order) }}" class="flex flex-col items-end gap-2 sm:flex-row sm:items-end">
            @csrf
            <div>
                <label class="label !mb-1 text-xs">Kargo Firması</label>
                <select name="carrier" required class="input !h-10 w-36">
                    @foreach (['Yurtiçi Kargo', 'Aras Kargo', 'MNG Kargo', 'PTT Kargo', 'Sürat Kargo', 'Diğer'] as $firm)
                        <option value="{{ $firm }}">{{ $firm }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label !mb-1 text-xs">Takip No</label>
                <input type="text" name="tracking_no" required class="input !h-10 w-36" placeholder="Takip No">
            </div>
            <button type="submit" class="btn-secondary !h-10">Kargola</button>
        </form>
    @endif
@endsection

@section('content')
    {{-- Durum akışı: Bekliyor → Ödendi → Hazırlanıyor → Kargoda → Teslim --}}
    @php
        $adimlar = ['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'preparing' => 'Hazırlanıyor', 'shipped' => 'Kargoda', 'delivered' => 'Teslim'];
        $adimKeys = array_keys($adimlar);
        $aktifIndex = array_search($order->status, $adimKeys);
    @endphp
    <div class="card mb-6 p-5">
        <div class="mb-4 flex items-center justify-between">
            <span class="text-sm font-bold text-slate-800 dark:text-slate-100">Sipariş Akışı</span>
            <span class="{{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
        </div>
        @if ($order->status === 'cancelled')
            <div class="flex items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-5 text-sm font-bold text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                Bu sipariş iptal edildi{{ $order->tracking_no ? '' : ' — rezerve stok serbest bırakıldı' }}.
            </div>
        @else
            <div class="flex items-start">
                @foreach ($adimlar as $key => $label)
                    @php
                        $i = $loop->index;
                        $tamamlanan = $i < $aktifIndex;
                        $aktif = $i === $aktifIndex;
                    @endphp
                    @if (! $loop->first)
                        <div class="mt-[18px] h-0.5 flex-1 {{ $tamamlanan || $aktif ? 'bg-indigo-500' : 'bg-slate-200 dark:bg-slate-700' }}"></div>
                    @endif
                    <div class="flex w-20 shrink-0 flex-col items-center gap-2 sm:w-24">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full border-2 transition
                            {{ $aktif ? 'border-indigo-600 bg-indigo-600 text-white shadow-lg shadow-indigo-500/30'
                                : ($tamamlanan ? 'border-indigo-500 bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-300'
                                : 'border-slate-300 bg-white text-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-600') }}">
                            @if ($tamamlanan)
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @elseif ($aktif && $key === 'pending')
                                <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-white"></span>
                            @else
                                <span class="text-xs font-black">{{ $i + 1 }}</span>
                            @endif
                        </div>
                        <span class="text-center text-[11px] font-semibold leading-tight {{ $aktif ? 'text-indigo-600 dark:text-indigo-300' : ($tamamlanan ? 'text-slate-600 dark:text-slate-300' : 'text-slate-400') }}">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="card-header">Sipariş Kalemleri</div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>Ürün</th>
                        <th class="!text-right">Miktar</th>
                        <th class="!text-right">Birim Fiyat</th>
                        <th class="!text-right">Tutar</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="font-medium text-slate-700 dark:text-slate-200">{{ $item['product_name'] }}</td>
                            <td class="text-right">{{ $item['quantity'] }}</td>
                            <td class="text-right">{{ number_format($item['unit_price'], 2, ',', '.') }} ₺</td>
                            <td class="text-right font-bold">{{ number_format($item['line_total'], 2, ',', '.') }} ₺</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-700 p-5">
                <div class="flex justify-end">
                    <div class="w-full max-w-xs space-y-1.5">
                        <div class="flex justify-between text-sm"><span class="text-slate-500">Ara Toplam</span><span>{{ number_format((float) $order->subtotal, 2, ',', '.') }} ₺</span></div>
                        <div class="flex justify-between text-sm"><span class="text-slate-500">KDV (%{{ $order->tax_rate }})</span><span>{{ number_format((float) $order->tax_amount, 2, ',', '.') }} ₺</span></div>
                        <div class="flex justify-between rounded-lg bg-slate-900 px-3 py-2 text-base font-bold text-white dark:bg-indigo-600"><span>Genel Toplam</span><span>{{ number_format((float) $order->total, 2, ',', '.') }} ₺</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Durum</span>
                <span class="{{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Sipariş No</dt><dd class="font-semibold">{{ $order->order_no }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Müşteri</dt><dd>{{ $order->customer_name ?: ($order->contact->name ?? '—') }}</dd></div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500">Ödeme</dt>
                    <dd>
                        <span class="inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $order->payment_method === 'kapida' ? 'bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300' : ($order->payment_method === 'havale' ? 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300' : 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300') }}">
                            {{ $order->odemeYontemiEtiketi() }}
                        </span>
                        @if ($order->payment_method === 'kapida' && in_array($order->status, ['pending', 'paid', 'preparing']))
                            <span class="ml-1 text-[11px] text-orange-600 dark:text-orange-300">tahsilat kapıda</span>
                        @endif
                    </dd>
                </div>
                @if ($order->customer_email)<div class="flex justify-between"><dt class="text-slate-500">E-posta</dt><dd class="text-xs">{{ $order->customer_email }}</dd></div>@endif
                @if ($order->customer_phone)<div class="flex justify-between"><dt class="text-slate-500">Telefon</dt><dd class="text-xs">{{ $order->customer_phone }}</dd></div>@endif
                @if ($order->invoice)
                    <div class="flex justify-between"><dt class="text-slate-500">Fatura</dt><dd><a href="{{ route('faturalar.show', $order->invoice_id) }}" class="font-semibold text-indigo-600">{{ $order->invoice->invoice_no }}</a></dd></div>
                @endif
                @if ($order->tracking_no)
                    <div class="flex justify-between"><dt class="text-slate-500">Takip No</dt><dd class="font-mono text-xs">{{ $order->tracking_no }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Kargo</dt><dd>{{ $order->carrier }}</dd></div>
                    @if ($order->trackingUrl())
                        <div class="pt-1">
                            <a href="{{ $order->trackingUrl() }}" target="_blank" rel="noopener"
                               class="flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                Kargoyu Takip Et →
                            </a>
                        </div>
                    @endif
                @endif
            </dl>
            @if ($order->shipping_address)
                <div class="mt-3 rounded-lg bg-slate-50 dark:bg-slate-700 p-3 text-xs text-slate-600 dark:text-slate-300">
                    <span class="font-semibold">Teslim Adresi:</span><br>{{ $order->shipping_address }}
                </div>
            @endif
            @if ($order->fatura_tipi === 'kurumsal' || $order->fatura_adresi)
                <div class="mt-3 rounded-lg border border-indigo-100 bg-indigo-50/60 p-3 text-xs text-slate-700 dark:border-indigo-900 dark:bg-indigo-950/30 dark:text-slate-300">
                    <span class="font-semibold">{{ $order->fatura_tipi === 'kurumsal' ? 'Kurumsal Fatura' : 'Fatura Adresi' }}:</span><br>
                    @if ($order->fatura_tipi === 'kurumsal')
                        {{ $order->fatura_unvan }}<br>
                        VD: {{ $order->vergi_dairesi }} · VKN/TCKN: {{ $order->vergi_no }}<br>
                    @endif
                    {{ $order->fatura_adresi ?: $order->shipping_address }}
                </div>
            @endif
            @if ($order->hediye_paket)
                <div class="mt-3 rounded-lg border border-pink-200 bg-pink-50 p-3 text-xs text-pink-800 dark:border-pink-900 dark:bg-pink-950/30 dark:text-pink-200">
                    <span class="font-semibold">🎁 Hediye paketi istendi</span>
                    @if ($order->hediye_notu)<br>Not: “{{ $order->hediye_notu }}”@endif
                </div>
            @endif
            @if ($order->iade_talebi)
                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                    <span class="font-semibold">{{ ($order->iade_talebi['reddedildi'] ?? false) ? 'Reddedilen İade Talebi' : ($order->iade_talebi['kismi'] ? 'Kısmi İade Talebi' : 'İade Talebi') }}</span>
                    — {{ $order->iade_talebi['neden_etiketi'] }}
                    @if (! empty($order->iade_talebi['aciklama']))<br><em>“{{ $order->iade_talebi['aciklama'] }}”</em>@endif
                    <ul class="mt-1.5 list-disc pl-4">
                        @foreach ($order->iade_talebi['kalemler'] as $kalem)
                            <li>{{ $kalem['product_name'] }} × {{ (float) $kalem['quantity'] }}</li>
                        @endforeach
                    </ul>
                    <div class="mt-1.5 font-semibold">İade tutarı: {{ number_format((float) $order->iade_tutari, 2, ',', '.') }} ₺</div>
                    @if ($order->iade_talebi['reddedildi'] ?? false)
                        <div class="mt-1.5 font-semibold text-red-700 dark:text-red-300">Talep reddedildi{{ ! empty($order->iade_talebi['red_notu']) ? ': '.$order->iade_talebi['red_notu'] : '' }}</div>
                    @elseif ($order->status === 'return_requested')
                        <div class="mt-2.5 flex gap-2">
                            <form method="POST" action="{{ route('siparisler.iade.onay', $order) }}">
                                @csrf
                                <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 font-semibold text-white transition hover:bg-emerald-700"
                                        onclick="return confirm('İadeyi onaylıyor musunuz? İade edilen ürünlerin stoğu geri alınır ve tutar cari hesaba iade edilir.')">
                                    İadeyi Onayla
                                </button>
                            </form>
                            <form method="POST" action="{{ route('siparisler.iade.reddet', $order) }}"
                                  onsubmit="return confirm('İade talebini reddetmek istediğinize emin misiniz? Sipariş teslim durumuna döner.')">
                                @csrf
                                <button type="submit" class="rounded-lg border border-amber-300 px-3 py-1.5 font-semibold text-amber-800 transition hover:bg-amber-100 dark:border-amber-800 dark:text-amber-200">Reddet</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
