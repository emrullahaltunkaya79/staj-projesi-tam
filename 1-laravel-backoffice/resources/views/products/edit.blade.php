@extends('layouts.app')
@section('title', 'Ürün Düzenle: '.$product->name)

@section('content')
    <form method="POST" action="{{ route('urunler.update', $product) }}">
        @csrf @method('PUT')
        @include('products._form')
    </form>

    {{-- Ürün Görselleri --}}
    <div class="card mt-4 p-5">
        <h3 class="mb-1 font-bold text-slate-800 dark:text-slate-100">Ürün Görselleri</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
            Ana görsel mağazada kart ve listelerde görünür. Büyük görseller yükleme anında
            otomatik 1600 px'e küçültülür — JPG/PNG/WebP, dosya başına en fazla 4 MB.
        </p>

        @if ($product->images->isNotEmpty())
            <div class="mb-4 grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6">
                @foreach ($product->images->sortByDesc('is_primary')->sortBy('sort') as $gorsel)
                    <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700">
                        <img src="{{ route('urunler.gorsel.goster', [$product, $gorsel]) }}" alt=""
                             class="aspect-square w-full bg-white object-contain" loading="lazy">
                        @if ($gorsel->is_primary)
                            <span class="absolute left-2 top-2 rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white">Ana görsel</span>
                        @endif
                        <div class="absolute inset-x-0 bottom-0 flex justify-center gap-1 bg-white/90 p-1.5 opacity-0 transition group-hover:opacity-100 dark:bg-slate-900/90">
                            @unless ($gorsel->is_primary)
                                <form method="POST" action="{{ route('urunler.gorsel.ana', [$product, $gorsel]) }}">
                                    @csrf
                                    <button type="submit" title="Ana görsel yap"
                                            class="rounded bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700 transition hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300">
                                        Ana yap
                                    </button>
                                </form>
                            @endunless
                            <form method="POST" action="{{ route('urunler.gorsel.destroy', [$product, $gorsel]) }}"
                                  onsubmit="return confirm('Bu görseli silmek istediğinize emin misiniz?');">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="rounded bg-red-50 px-2 py-1 text-[10px] font-bold text-red-600 transition hover:bg-red-100 dark:bg-red-950/40 dark:text-red-300">
                                    Sil
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="mb-4 rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-400">
                Henüz görsel yok — mağazada ürün baş harfiyle temsil edilir. Aşağıdan yükleyin.
            </p>
        @endif

        <form method="POST" action="{{ route('urunler.gorsel.upload', $product) }}" enctype="multipart/form-data"
              class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center dark:border-slate-700">
            @csrf
            <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" required
                   class="input !h-10 flex-1 file:mr-3 file:rounded-full file:border-0 file:bg-slate-100 file:px-3 file:py-1 file:text-xs file:font-bold file:text-slate-600 dark:file:bg-slate-700 dark:file:text-slate-300">
            <button type="submit" class="btn-secondary !h-10">Görselleri Yükle</button>
        </form>
    </div>
@endsection
