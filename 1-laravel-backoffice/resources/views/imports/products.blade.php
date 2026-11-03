@extends('layouts.app')
@section('title', 'Ürünler İçe Aktar')

@section('content')
    <div class="max-w-2xl">
        <div class="card p-6">
            <h2 class="mb-1 text-lg font-bold text-slate-800">Ürünler — Excel'den İçe Aktar</h2>
            <p class="mb-5 text-sm text-slate-500">Kodu (yoksa ismi) eşleşen ürünler güncellenir; yeni ürünler "Açılış" stok hareketiyle açılır, stok değişimleri "Düzeltme" hareketi olarak kaydedilir.</p>

            <div class="mb-5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                <div class="mb-1 font-semibold text-slate-700">Beklenen sütunlar (ilk satır başlık olarak atlanır):</div>
                Kod · Ürün · Birim · Alış Fiyatı · Satış Fiyatı · Stok
            </div>

            <form method="POST" action="{{ route('urunler.import') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="label" for="file">Excel Dosyası (.xlsx / .csv) <span class="text-red-500">*</span></label>
                    <input type="file" id="file" name="file" required accept=".xlsx,.xls,.csv"
                           class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                    @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="btn-primary">İçe Aktar</button>
                    <a href="{{ route('urunler.import.template') }}" class="btn-secondary">Şablonu İndir</a>
                </div>
            </form>
        </div>
        <p class="mt-3 text-xs text-slate-400"><a href="{{ route('urunler.index') }}" class="text-indigo-600">← Ürünlere dön</a></p>
    </div>
@endsection
