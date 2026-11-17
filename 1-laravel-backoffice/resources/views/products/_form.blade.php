    <div class="card p-6">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <label class="label" for="name">Ürün Adı <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}" required
                               class="input" placeholder="USB Kablosu 2m">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:w-52">
                        <label class="label" for="category_id">Kategori</label>
                        <select id="category_id" name="category_id" class="input">
                            <option value="">— Kategorisiz —</option>
                            @foreach (\App\Models\Category::orderBy('name')->get() as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        {{-- İç içe form YASAK: eski mini form ürün adı alanını (name="name") eziyordu.
                             Yeni kategori artık JS ile ayrı istekle eklenir. --}}
                        <div class="mt-1.5 flex gap-1">
                            <input type="text" id="yeni_kategori_ad" placeholder="Yeni kategori…" class="w-full rounded-lg border-slate-200 bg-slate-50 px-2 py-1.5 text-xs focus:border-indigo-400 focus:bg-white focus:outline-none" style="min-height: 0;">
                            <button type="button" id="yeni_kategori_ekle" class="shrink-0 rounded-lg bg-indigo-50 px-2.5 py-1.5 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100">+</button>
                        </div>
                        <script>
                            document.getElementById('yeni_kategori_ekle')?.addEventListener('click', async function () {
                                const ad = document.getElementById('yeni_kategori_ad')?.value.trim();
                                if (!ad) return;
                                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                                const yanit = await fetch('{{ route('kategoriler.store') }}', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf ?? '' },
                                    body: JSON.stringify({ name: ad }),
                                });
                                if (!yanit.ok) return;
                                const secici = document.getElementById('category_id');
                                const varMi = Array.from(secici.options).some((o) => o.text === ad);
                                if (!varMi) {
                                    const opt = document.createElement('option');
                                    opt.textContent = ad;
                                    secici.appendChild(opt);
                                }
                                secici.value = Array.from(secici.options).find((o) => o.text === ad)?.value ?? '';
                                document.getElementById('yeni_kategori_ad').value = '';
                            });
                        </script>
                    </div>
                </div>
            </div>

        <div>
            <label class="label" for="code">Ürün Kodu</label>
            <input type="text" id="code" name="code" value="{{ old('code', $product->code) }}"
                   class="input" placeholder="URN-001">
            @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="brand">Marka</label>
            <input type="text" id="brand" name="brand" value="{{ old('brand', $product->brand) }}"
                   class="input" placeholder="Logitech, Samsung…">
            @error('brand') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="model">Model</label>
            <input type="text" id="model" name="model" value="{{ old('model', $product->model) }}"
                   class="input" placeholder="MX-2200">
            @error('model') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="alt_kategori">Alt Kategori</label>
            <input type="text" id="alt_kategori" name="alt_kategori" value="{{ old('alt_kategori', $product->alt_kategori) }}"
                   class="input" placeholder="Klavye, Mouse, Monitör…" list="alt-kategori-onerileri">
            <datalist id="alt-kategori-onerileri">
                @foreach (\App\Models\Product::whereNotNull('alt_kategori')->distinct()->orderBy('alt_kategori')->pluck('alt_kategori') as $a)
                    <option value="{{ $a }}">
                @endforeach
            </datalist>
            @error('alt_kategori') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="video_url">Ürün Videosu (YouTube)</label>
            <input type="url" id="video_url" name="video_url" value="{{ old('video_url', $product->video_url) }}"
                   class="input font-mono text-xs" placeholder="https://www.youtube.com/watch?v=...">
            @error('video_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="pre_order">Ön Sipariş</label>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="hidden" name="pre_order" value="0" />
                <input type="checkbox" id="pre_order" name="pre_order" value="1" @checked(old('pre_order', $product?->pre_order)) class="h-4 w-4 rounded border-slate-300" />
                Tükendiğinde ön siparişe aç
            </label>
        </div>

        <div>
            <label class="label" for="katalog_gizli">Katalogda Gizli</label>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="hidden" name="katalog_gizli" value="0" />
                <input type="checkbox" id="katalog_gizli" name="katalog_gizli" value="1" @checked(old('katalog_gizli', $product?->katalog_gizli)) class="h-4 w-4 rounded border-slate-300" />
                Mağaza vitrininde listelenmez (üyelik paketi vb.)
            </label>
        </div>

        <div>
            <label class="label" for="barcode">Barkod <span class="font-normal text-slate-400">(EAN/POS)</span></label>
            <input type="text" id="barcode" name="barcode" value="{{ old('barcode', $product->barcode) }}"
                   class="input" placeholder="8690000000000">
            @error('barcode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="unit">Birim <span class="text-red-500">*</span></label>
            <select id="unit" name="unit" class="input" required>
                @foreach (['adet', 'kg', 'lt', 'metre', 'paket', 'koli'] as $unit)
                    <option value="{{ $unit }}" @selected(old('unit', $product->unit) === $unit)>{{ $unit }}</option>
                @endforeach
            </select>
            @error('unit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="stock_quantity">Stok Adedi <span class="text-red-500">*</span></label>
            <input type="number" id="stock_quantity" name="stock_quantity" min="0" step="1"
                   value="{{ old('stock_quantity', $product->stock_quantity) }}" required class="input">
            @error('stock_quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="min_stock_level">Kritik Stok Eşiği</label>
            <input type="number" id="min_stock_level" name="min_stock_level" min="0" step="1"
                   value="{{ old('min_stock_level', $product->min_stock_level ?? 5) }}" class="input">
            <p class="mt-1 text-xs text-slate-400">Stok bu seviyenin altına düşerse uyarı e-postası gönderilir.</p>
            @error('min_stock_level') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="supplier_id">Tedarikçi</label>
            <select id="supplier_id" name="supplier_id" class="input">
                <option value="">— Tedarikçi seçin —</option>
                @foreach (\App\Models\Contact::where('type', 'tedarikci')->orderBy('name')->get() as $tedarikci)
                    <option value="{{ $tedarikci->id }}" @selected(old('supplier_id', $product->supplier_id) == $tedarikci->id)>{{ $tedarikci->name }}</option>
                @endforeach
            </select>
            @error('supplier_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="purchase_price">Alış Fiyatı (₺) <span class="text-red-500">*</span></label>
            <input type="number" id="purchase_price" name="purchase_price" min="0" step="0.01"
                   value="{{ old('purchase_price', $product->purchase_price) }}" required class="input">
            @error('purchase_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="sale_price">Satış Fiyatı (₺) <span class="text-red-500">*</span></label>
            <input type="number" id="sale_price" name="sale_price" min="0" step="0.01"
                   value="{{ old('sale_price', $product->sale_price) }}" required class="input">
            @error('sale_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- ═══ Pazar Yeri Komisyon ve Net Kâr Simülatörü ═══ --}}
        <div class="sm:col-span-2">
            <div class="rounded-xl border border-indigo-200 bg-indigo-50/40 p-4 dark:border-indigo-900/60 dark:bg-indigo-950/20">
                <div class="mb-3 flex items-center gap-2">
                    <svg class="h-4 w-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h3m0 0v9m0-9c1.657 0 3 .895 3 2s-1.343 2-3 2m0 0c-1.657 0-3 .895-3 2m0 0v3m9-11a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300">Pazar Yeri Komisyon & Net Kâr Simülatörü</span>
                    <span class="ml-auto text-[11px] text-slate-400">fiyatlar yazıldıkça canlı hesaplanır</span>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    {{-- Girdiler --}}
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="label !mb-1 text-xs" for="sim_pazaryeri">Pazar Yeri</label>
                            <select id="sim_pazaryeri" name="pazaryeri" class="input !h-10 text-sm">
                                @foreach ([
                                    'web' => 'Web Sitesi / Mağaza (komisyonsuz)',
                                    'trendyol' => 'Trendyol (%15)',
                                    'hepsiburada' => 'Hepsiburada (%14)',
                                    'amazon' => 'Amazon (%12)',
                                    'ozel' => 'Özel Oran…',
                                ] as $deger => $etiket)
                                    <option value="{{ $deger }}" @selected(old('pazaryeri', $product->pazaryeri) === $deger)>{{ $etiket }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="sim-komisyon-alani">
                            <label class="label !mb-1 text-xs" for="pazaryeri_komisyon_orani">Komisyon Oranı (%)</label>
                            <input type="number" id="pazaryeri_komisyon_orani" name="pazaryeri_komisyon_orani" min="0" max="100" step="0.01"
                                   value="{{ old('pazaryeri_komisyon_orani', $product->pazaryeri_komisyon_orani) }}"
                                   class="input !h-10 text-sm" placeholder="Özel oran girin">
                        </div>
                        <div>
                            <label class="label !mb-1 text-xs" for="kargo_maliyeti">Kargo Kesintisi (₺)</label>
                            <input type="number" id="kargo_maliyeti" name="kargo_maliyeti" min="0" step="0.01"
                                   value="{{ old('kargo_maliyeti', $product->kargo_maliyeti) }}"
                                   class="input !h-10 text-sm" placeholder="0,00">
                        </div>
                        <div>
                            <label class="label !mb-1 text-xs" for="sim_kdv">KDV Oranı (%)</label>
                            <select id="sim_kdv" class="input !h-10 text-sm">
                                @foreach ([20, 10, 1, 0] as $oran)
                                    <option value="{{ $oran }}" @selected((int) old('kdv_orani', 20) === $oran)>%{{ $oran }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300">
                                <input type="checkbox" id="sim_maliyet_kdv_dahil" class="h-4 w-4 rounded border-slate-300 accent-indigo-600" />
                                Alış fiyatı KDV dahil girildi (net maliyet KDV'si düşülerek hesaplanır)
                            </label>
                        </div>
                    </div>

                    {{-- Finansal Özet Kartı — kenar rengi kârlılık durumuna göre JS ile atanır --}}
                    <div id="sim-ozet" class="rounded-xl border-2 border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800/60 transition-colors">
                        <div class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Finansal Özet</div>
                        <dl class="space-y-1.5 text-[13px]">
                            <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">Brüt Ciro (KDV Dahil)</dt><dd class="font-semibold tabular-nums" id="sim-brut">—</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">Pazar Yeri Komisyonu <span id="sim-komisyon-etiket" class="text-slate-400"></span></dt><dd class="tabular-nums text-red-600 dark:text-red-400" id="sim-komisyon-tutar">—</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">Kargo Kesintisi</dt><dd class="tabular-nums text-red-600 dark:text-red-400" id="sim-kargo-tutar">—</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">Ürün Maliyeti (net)</dt><dd class="tabular-nums text-red-600 dark:text-red-400" id="sim-maliyet-tutar">—</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">KDV Yükü</dt><dd class="tabular-nums text-red-600 dark:text-red-400" id="sim-kdv-tutar">—</dd></div>
                        </dl>
                        <div class="mt-3 flex items-center justify-between border-t border-slate-200 pt-3 dark:border-slate-700">
                            <div>
                                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Net Kâr</div>
                                <div id="sim-net-kar" class="text-2xl font-bold tabular-nums">—</div>
                            </div>
                            <div class="text-right">
                                <span id="sim-rozet" class="inline-block rounded-full px-3 py-1 text-xs font-bold">—</span>
                                <div id="sim-marj" class="mt-1 text-xs font-semibold tabular-nums text-slate-500 dark:text-slate-400">Marj: —</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const fmt = (v) => new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v) + ' ₺';
        const sayi = (el) => { const v = parseFloat((el?.value ?? '').replace(',', '.')); return Number.isFinite(v) ? v : 0; };
        const GIRDILER = ['#purchase_price', '#sale_price', '#pazaryeri_komisyon_orani', '#kargo_maliyeti', '#sim_pazaryeri', '#sim_kdv', '#sim_maliyet_kdv_dahil'];

        const varsayilanKomisyonlar = { trendyol: 15, hepsiburada: 14, amazon: 12 };

        function hesapla() {
            const alis = sayi(document.getElementById('purchase_price'));
            const satis = sayi(document.getElementById('sale_price'));
            const kdvOran = sayi(document.getElementById('sim_kdv'));
            const kargo = sayi(document.getElementById('kargo_maliyeti'));
            const komisyonOran = Math.min(100, Math.max(0, sayi(document.getElementById('pazaryeri_komisyon_orani'))));
            const maliyetKdvDahil = document.getElementById('sim_maliyet_kdv_dahil').checked;
            const pazaryeri = document.getElementById('sim_pazaryeri').value;

            const satisKdvHaric = kdvOran > 0 ? satis / (1 + kdvOran / 100) : satis;
            const kdvYuku = satis - satisKdvHaric;
            const komisyon = satis * (komisyonOran / 100);
            const maliyetNet = maliyetKdvDahil && kdvOran > 0 ? alis / (1 + kdvOran / 100) : alis;
            const netKar = satisKdvHaric - komisyon - kargo - maliyetNet;
            const marj = satis > 0 ? (netKar / satis) * 100 : null;

            // Özet kartı doldur
            document.getElementById('sim-brut').textContent = fmt(satis);
            document.getElementById('sim-komisyon-etiket').textContent = komisyonOran > 0 ? `(%${komisyonOran})` : '';
            document.getElementById('sim-komisyon-tutar').textContent = komisyon > 0 ? '−' + fmt(komisyon) : '—';
            document.getElementById('sim-kargo-tutar').textContent = kargo > 0 ? '−' + fmt(kargo) : '—';
            document.getElementById('sim-maliyet-tutar').textContent = alis > 0 ? '−' + fmt(maliyetNet) : '—';
            document.getElementById('sim-kdv-tutar').textContent = kdvYuku > 0 ? '−' + fmt(kdvYuku) : '—';
            document.getElementById('sim-net-kar').textContent = fmt(netKar);
            document.getElementById('sim-marj').textContent = marj === null ? 'Marj: —' : 'Marj: %' + marj.toFixed(1);

            // Dinamik rozet + kart rengi: zarar → kırmızı, %10 altı → turuncu, kârlı → yeşil
            const kart = document.getElementById('sim-ozet');
            const rozet = document.getElementById('sim-rozet');
            const stiller = netKar < 0
                ? { kart: 'border-red-300 dark:border-red-900/60', rozet: 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-300', metin: 'ZARAR', koyu: 'text-red-600 dark:text-red-400' }
                : marj !== null && marj < 10
                    ? { kart: 'border-amber-300 dark:border-amber-900/60', rozet: 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300', metin: 'İNCE MARJ', koyu: 'text-amber-600 dark:text-amber-400' }
                    : { kart: 'border-emerald-300 dark:border-emerald-900/60', rozet: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300', metin: 'KÂRLI', koyu: 'text-emerald-600 dark:text-emerald-400' };

            kart.className = kart.className
                .replace(/border-(red|amber|emerald|slate)-(200|300)/g, '')
                .replace(/dark:border-(red|amber|emerald|slate)-(700|900)\/?\d*/g, '')
                .replace(/\s+/g, ' ');
            kart.classList.add(...stiller.kart.split(' '));
            rozet.className = 'inline-block rounded-full px-3 py-1 text-xs font-bold ' + stiller.rozet;
            rozet.textContent = stiller.metin;
            document.getElementById('sim-net-kar').className = 'text-2xl font-bold tabular-nums ' + stiller.koyu;

            // Komisyon alanı: pazar yerinden seçilince bilinen oran dolar; web/özel'de elle girilir
            const komisyonInput = document.getElementById('pazaryeri_komisyon_orani');
            const alan = document.getElementById('sim-komisyon-alani');
            const webMi = pazaryeri === 'web';
            komisyonInput.disabled = webMi;
            alan.style.opacity = webMi ? '0.5' : '1';
            if (webMi) komisyonInput.value = 0;
            if (varsayilanKomisyonlar[pazaryeri] !== undefined && (komisyonInput.dataset.otomatik === '1' || komisyonInput.value === '')) {
                komisyonInput.value = varsayilanKomisyonlar[pazaryeri];
                komisyonInput.dataset.otomatik = '1';
            }
            if (pazaryeri === 'ozel') delete komisyonInput.dataset.otomatik;
        }

        // Pazar yeri değişiminde bilinen komisyonu otomatik doldur (kullanıcı sonra düzeltebilir)
        document.getElementById('sim_pazaryeri')?.addEventListener('change', function () {
            const input = document.getElementById('pazaryeri_komisyon_orani');
            const bilinen = varsayilanKomisyonlar[this.value];
            if (bilinen !== undefined) {
                input.value = bilinen;
                input.dataset.otomatik = '1';
            } else {
                delete input.dataset.otomatik;
            }
            hesapla();
        });

        GIRDILER.forEach((secici) => document.querySelector(secici)?.addEventListener('input', hesapla));
        hesapla();
    })();
    </script>

    <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
        <button type="submit" class="btn-primary">Kaydet</button>
        <a href="{{ route('urunler.index') }}" class="btn-secondary">İptal</a>
    </div>
</div>
