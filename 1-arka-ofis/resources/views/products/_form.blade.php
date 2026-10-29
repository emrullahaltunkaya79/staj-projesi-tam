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
                        <form method="POST" action="{{ route('kategoriler.store') }}" class="mt-1.5 flex gap-1">
                            @csrf
                            <input type="text" name="name" placeholder="Yeni kategori…" class="w-full rounded-lg border-slate-200 bg-slate-50 px-2 py-1.5 text-xs focus:border-indigo-400 focus:bg-white focus:outline-none" style="min-height: 0;">
                            <button type="submit" class="shrink-0 rounded-lg bg-indigo-50 px-2.5 py-1.5 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100">+</button>
                        </form>
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
    </div>

    <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
        <button type="submit" class="btn-primary">Kaydet</button>
        <a href="{{ route('urunler.index') }}" class="btn-secondary">İptal</a>
    </div>
</div>
