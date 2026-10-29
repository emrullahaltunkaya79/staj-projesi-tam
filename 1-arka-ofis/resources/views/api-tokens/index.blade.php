@extends('layouts.app')
@section('title', 'API Tokenlar — E-ticaret Entegrasyonu')

@section('content')
    @php $plainToken = session('plain_token'); @endphp

    {{-- Yeni oluşturulan token (bir kez gösterilir) --}}
    @if ($plainToken)
        <div class="card mb-4 border-emerald-300 p-5">
            <div class="mb-2 flex items-center gap-2 text-emerald-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="font-bold">Token oluşturuldu</span>
            </div>
            <p class="mb-2 text-sm text-slate-500">Bu değer <span class="font-semibold">yalnızca bir kez</span> gösteriliyor. Kopyalayıp güvenli yerde saklayın.</p>
            <div class="flex items-center gap-2">
                <code class="flex-1 overflow-x-auto rounded-lg bg-slate-900 px-3 py-2 font-mono text-xs text-emerald-300">{{ $plainToken }}</code>
                <button type="button" onclick="navigator.clipboard.writeText(this.parentElement.querySelector('code').textContent); this.textContent='Kopyalandı'"
                        class="btn-secondary !py-2">Kopyala</button>
            </div>
        </div>
    @endif

    {{-- Yeni token formu --}}
    <div class="card mb-4 p-5">
        <h3 class="mb-1 font-bold text-slate-800 dark:text-slate-100">Yeni API Token</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
            E-ticaret siteniz API'ye bu token ile erişir: <code class="rounded bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5">Authorization: Bearer &lt;token&gt;</code>
        </p>
        <form method="POST" action="{{ route('api-tokenlar.store') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            @csrf
            <div class="flex-1">
                <label class="label" for="name">Token Adı</label>
                <input type="text" id="name" name="name" required class="input" placeholder="Örn: e-ticaret sitesi">
            </div>
            <div class="sm:w-56">
                <label class="label" for="password">Şifreniz <span class="text-red-500">*</span></label>
                <input type="password" id="password" name="password" required class="input">
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn-primary !h-10">Token Oluştur</button>
        </form>
    </div>

    {{-- Token listesi --}}
    <div class="card">
        <div class="card-header">Aktif Tokenlar</div>
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Ad</th>
                    <th>Oluşturma</th>
                    <th>Son Kullanım</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($tokens as $token)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                        <td class="font-medium text-slate-700 dark:text-slate-200">{{ $token->name }}</td>
                        <td>{{ $token->created_at->format('d.m.Y H:i') }}</td>
                        <td>{{ $token->last_used_at?->format('d.m.Y H:i') ?? 'Hiç kullanılmadı' }}</td>
                        <td>
                            <div class="flex justify-end">
                                <form method="POST" action="{{ route('api-tokenlar.destroy', $token->id) }}"
                                      onsubmit="return confirm('Bu token iptal edilsin mi? E-ticaret bağlantısı kopar.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">İptal</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-10 text-center text-slate-400">Aktif token yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- API referansı --}}
    @php
        $base = request()->getSchemeAndHttpHost();
        $q = fn (string $s) => str_replace("'", "'\''", $s);
        $curl = function (string $method, string $path, ?string $body = null) use ($base, $q) {
            $cmd = "curl -X {$method} \\\n  -H \"Authorization: Bearer TOKEN\" \\\n  -H \"Accept: application/json\" \\\n  -H \"Content-Type: application/json\"";
            if ($body !== null) {
                $cmd .= " \\\n  -d '{$q($body)}'";
            }

            return $cmd . " \\\n  \"{$base}{$path}\"";
        };
        $endpoints = [
            ['Katalog', 'GET', '/api/v1/products', 'Ürün kataloğu: fiyat, stok, rezerve, müsait, görsel URL. Filtre: ?category_id=', $curl('GET', '/api/v1/products?category_id=1')],
            ['Katalog', 'GET', '/api/v1/products/1', 'Tek ürün detayı', $curl('GET', '/api/v1/products/1')],
            ['Katalog', 'GET', '/api/v1/stock?product_ids[]=1', 'Canlı stok sorgusu (array filtre)', $curl('GET', '/api/v1/stock?product_ids%5B%5D=1&product_ids%5B%5D=2')],
            ['Müşteri', 'POST', '/api/v1/customers', 'Müşteri oluştur/bul — aynı e-posta tekrarında aynı kayıt (idempotent)', $curl('POST', '/api/v1/customers', '{"name":"Örnek Müşteri","email":"musteri@ornek.com","type":"musteri"}')],
            ['Sipariş', 'POST', '/api/v1/orders', 'Sipariş oluştur — stok REZERVE edilir (30 dk), ödemede faturalaşır. tax_rate: 0/1/10/20', $curl('POST', '/api/v1/orders', '{"contact_id":1,"tax_rate":20,"items":[{"product_id":1,"quantity":2}],"shipping_address":"Örnek Mah. No:1 İstanbul","external_ref":"SIP-2026-001"}')],
            ['Sipariş', 'GET', '/api/v1/orders/ORD-000001', 'Sipariş durumu: status, fatura no, takip URL', $curl('GET', '/api/v1/orders/ORD-000001')],
            ['Sipariş', 'POST', '/api/v1/orders/ORD-000001/pay', 'Ödemeyi işle (CSPR) → fatura kesilir, stok düşer, order.paid webhook\'u gider', $curl('POST', '/api/v1/orders/ORD-000001/pay', '{"payment_method":"cspr"}')],
            ['Sipariş', 'POST', '/api/v1/orders/ORD-000001/shipping', 'Kargo bilgisi → order.shipped webhook\'u gider (5 firma takip URL)', $curl('POST', '/api/v1/orders/ORD-000001/shipping', '{"carrier":"aras","tracking_no":"ARAS123456789"}')],
            ['Sipariş', 'POST', '/api/v1/orders/ORD-000001/cancel', 'İptal → rezerv/fatura geri alınır, order.cancelled webhook\'u gider', $curl('POST', '/api/v1/orders/ORD-000001/cancel', '{"reason":"müşteri talebi"}')],
            ['Ödeme', 'GET', '/api/v1/cspr-payments/ORD-000001-abc123', 'CSPR ödeme durumu (memo ile sorgulama)', $curl('GET', '/api/v1/cspr-payments/ORD-000001-abc123')],
            ['Güvenlik', 'GET', '/api/v1/tokens', 'Aktif token listesi', $curl('GET', '/api/v1/tokens')],
        ];
        $renk = fn (string $m) => $m === 'GET' ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700';
    @endphp

    <div class="card mt-4">
        <div class="card-header flex items-center justify-between">
            <span class="flex items-center gap-2">
                <svg class="h-5 w-5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                API Referansı — v1
            </span>
            <a href="{{ route('api-tokenlar.postman') }}" class="btn-secondary !py-1.5 !px-3 text-xs">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                Postman Koleksiyonu
            </a>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-700">
            @foreach ($endpoints as [$grup, $method, $path, $desc, $ornek])
                <details class="group">
                    <summary class="flex cursor-pointer list-none items-center gap-3 px-5 py-3 transition hover:bg-slate-50 dark:hover:bg-slate-700/40">
                        <span class="w-20 shrink-0 text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ $grup }}</span>
                        <span class="w-14 shrink-0 rounded-md px-2 py-0.5 text-center text-[11px] font-black {{ $renk($method) }}">{{ $method }}</span>
                        <code class="truncate font-mono text-xs text-slate-700 dark:text-slate-300">{{ $path }}</code>
                        <span class="ml-auto hidden truncate text-xs text-slate-400 md:block md:max-w-xs">{{ $desc }}</span>
                        <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="border-t border-slate-100 bg-slate-50/60 px-5 py-4 dark:border-slate-700 dark:bg-slate-900/40">
                        <p class="mb-3 text-xs text-slate-500 dark:text-slate-400 md:hidden">{{ $desc }}</p>
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400">curl örneği</span>
                            <button type="button"
                                    onclick="navigator.clipboard.writeText(this.closest('div').parentElement.querySelector('pre').textContent); this.textContent='Kopyalandı ✓'; setTimeout(() => this.textContent='Kopyala', 1500)"
                                    class="btn-secondary !py-1 !px-2.5 text-[11px]">Kopyala</button>
                        </div>
                        <pre class="overflow-x-auto rounded-xl bg-slate-900 p-4 text-[11.5px] leading-relaxed text-emerald-300">{{ $ornek }}</pre>
                    </div>
                </details>
            @endforeach
        </div>

        <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-400 dark:border-slate-700">
            Tüm isteklerde <code class="rounded bg-slate-100 dark:bg-slate-700 px-1">Authorization: Bearer &lt;token&gt;</code> gönderilir.
            Hız limiti: <strong>60 istek/dk</strong>. Webhook olayları: order.paid · order.cancelled · order.shipped · stock.changed (HMAC-SHA256 imzalı).
        </div>
    </div>
@endsection
