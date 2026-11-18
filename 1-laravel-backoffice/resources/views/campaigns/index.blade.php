@extends('layouts.app')

@section('title', 'Kampanyalar')

@section('content')
<div class="mx-auto max-w-5xl px-6 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Kampanyalar</h1>
            <p class="mt-1 text-sm text-slate-500">Paket kampanyaları mağazada otomatik uygulanır: sepet fiyat motoru indirimleri işler.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('success') }}</div>
    @endif

    {{-- Yeni kampanya --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-900">Yeni kampanya</h2>
        <form method="POST" action="{{ route('kampanyalar.store') }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
            @csrf
            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-semibold text-slate-500">Kampanya adı</label>
                <input name="ad" required maxlength="100" placeholder="Örn: 3 Al 2 Öde — Defter" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Tür</label>
                <select name="tur" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="x_al_y_ode">X Al Y Öde</option>
                    <option value="esik_yarim">Eşik üstü yarı fiyat</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Ürün (boş = tüm ürünler)</label>
                <select name="urun_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">— Tüm ürünler —</option>
                    @foreach (\App\Models\Product::orderBy('name')->get(['id', 'name']) as $urun)
                        <option value="{{ $urun->id }}">{{ $urun->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Eşik (₺ — yarı fiyat türü için)</label>
                <input name="esik" type="number" step="0.01" min="0" placeholder="2000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Al adet</label>
                <input name="al_adet" type="number" min="2" max="10" value="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Öde adet</label>
                <input name="ode_adet" type="number" min="1" max="9" value="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white transition hover:bg-slate-700">Kampanyayı oluştur</button>
            </div>
        </form>
    </div>

    {{-- Liste --}}
    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Kampanya</th>
                    <th class="px-4 py-3">Tür</th>
                    <th class="px-4 py-3">Ürün</th>
                    <th class="px-4 py-3">Kural</th>
                    <th class="px-4 py-3 text-right">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($kampanyalar as $k)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $k->ad }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            @if ($k->tur === 'x_al_y_ode') X Al Y Öde @else Eşik üstü yarı fiyat @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $k->urun?->name ?? 'Tüm ürünler' }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            @if ($k->tur === 'x_al_y_ode')
                                {{ $k->al_adet }} adette {{ $k->al_adet - $k->ode_adet }} adet bedava
                            @else
                                {{ number_format($k->esik, 2) }} ₺ üstünde %50
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="{{ route('kampanyalar.toggle', $k) }}" class="flex items-center">
                                    @csrf
                                    <button type="submit" class="rounded-full border px-3 py-1 text-xs font-bold {{ $k->aktif ? 'border-emerald-300 text-emerald-700' : 'border-slate-300 text-slate-500' }}">
                                        {{ $k->aktif ? 'Aktif' : 'Pasif' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('kampanyalar.destroy', $k) }}"
                                      class="flex items-center"
                                      onsubmit="return confirm('Kampanya silinsin mi?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-icon-trash title="Kampanyayı sil"/>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Henüz kampanya yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
