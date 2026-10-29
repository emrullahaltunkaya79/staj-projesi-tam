@extends('layouts.app')
@section('title', 'Yeni Gider')

@section('content')
    <form method="POST" action="{{ route('giderler.store') }}" class="max-w-2xl">
        @csrf

        <div class="card p-6">
            <h2 class="mb-1 text-lg font-bold text-slate-800">Gider Kaydı</h2>
            <p class="mb-6 text-sm text-slate-500">Kira, fatura, maaş gibi işletme giderlerini kaydedin; kasa durumu raporlara yansır.</p>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="label" for="category">Kategori <span class="text-red-500">*</span></label>
                    <select id="category" name="category" class="input" required>
                        @foreach (\App\Models\Expense::CATEGORIES as $category)
                            <option value="{{ $category }}" @selected(old('category', $expense->category) === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                    @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="expense_date">Gider Tarihi <span class="text-red-500">*</span></label>
                    <input type="date" id="expense_date" name="expense_date"
                           value="{{ old('expense_date', $expense->expense_date) }}" required class="input">
                    @error('expense_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="amount">Tutar (₺) <span class="text-red-500">*</span></label>
                    <input type="number" id="amount" name="amount" min="0.01" step="0.01"
                           value="{{ old('amount') }}" required class="input" placeholder="0,00">
                    @error('amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <span class="label">Ödeme Türü <span class="text-red-500">*</span></span>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:text-indigo-700">
                            <input type="radio" name="payment_type" value="nakit" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                   @checked(old('payment_type', 'nakit') === 'nakit')>
                            Nakit (Kasa)
                        </label>
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:text-indigo-700">
                            <input type="radio" name="payment_type" value="havale" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                   @checked(old('payment_type') === 'havale')>
                            Havale (Banka)
                        </label>
                    </div>
                    @error('payment_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="label" for="description">Açıklama</label>
                    <textarea id="description" name="description" rows="2" class="input"
                              placeholder="Örn: Ekim ayı dükkan kirası (opsiyonel)">{{ old('description') }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn-primary">Gideri Kaydet</button>
                <a href="{{ route('giderler.index') }}" class="btn-secondary">İptal</a>
            </div>
        </div>
    </form>
@endsection
