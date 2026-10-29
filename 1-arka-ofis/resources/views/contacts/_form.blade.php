<div class="card p-6">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <label class="label" for="name">Cari Adı / Ünvanı <span class="text-red-500">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $contact->name) }}" required
                   class="input" placeholder="Yılmaz Market veya Demir Tedarik A.Ş.">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="type">Cari Tipi <span class="text-red-500">*</span></label>
            <select id="type" name="type" class="input" required>
                <option value="musteri" @selected(old('type', $contact->type) === 'musteri')>Müşteri</option>
                <option value="tedarikci" @selected(old('type', $contact->type) === 'tedarikci')>Tedarikçi</option>
            </select>
            @error('type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="phone">Telefon</label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone', $contact->phone) }}"
                   class="input" placeholder="0532 000 00 00">
            @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="email">E-posta</label>
            <input type="email" id="email" name="email" value="{{ old('email', $contact->email) }}"
                   class="input" placeholder="cari@ornek.com">
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="tax_number">Vergi / TC No</label>
            <input type="text" id="tax_number" name="tax_number" value="{{ old('tax_number', $contact->tax_number) }}"
                   class="input" placeholder="1234567890">
            @error('tax_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label class="label" for="address">Adres</label>
            <textarea id="address" name="address" rows="3" class="input"
                      placeholder="Mahalle, cadde, no, ilçe / il">{{ old('address', $contact->address) }}</textarea>
            @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
        <button type="submit" class="btn-primary">Kaydet</button>
        <a href="{{ route('cariler.index') }}" class="btn-secondary">İptal</a>
    </div>
</div>
