@extends('layouts.app')
@section('title', 'Geri Dönüşüm Kutusu')

@section('content')
    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <select name="type" class="input sm:w-44" onchange="this.form.submit()">
            @foreach ($types as $key => $meta)
                <option value="{{ $key }}" @selected($type === $key)>{{ $meta['label'] }}</option>
            @endforeach
        </select>
        <span class="text-xs text-slate-400">Soft delete ile silinen kayıtlar buradan geri yüklenebilir.</span>
    </form>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Kayıt</th>
                    <th>Silme Tarihi</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($items as $item)
                    <tr class="hover:bg-slate-50">
                        <td class="font-medium text-slate-700">{{ $item->getAuditName() }}</td>
                        <td>{{ $item->deleted_at->format('d.m.Y H:i') }}</td>
                        <td>
                            <div class="flex justify-end">
                                <form method="POST" action="{{ route('geri-donusum.restore', ['type' => $type, 'id' => $item->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn-secondary !px-3 !py-1.5 text-xs">Geri Yükle</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-10 text-center text-slate-400">Bu türde silinmiş kayıt yok. 👍</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $items->links() }}
        </div>
    </div>
@endsection
