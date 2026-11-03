@extends('layouts.app')
@section('title', 'Raporlar')

@section('actions')
    <a href="{{ route('raporlar.excel') }}" class="btn-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
        </svg>
        Excel İndir
    </a>
@endsection

@section('content')
    @php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    @endphp

    {{-- Özet kartları --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Toplam Satış</div>
            <div class="mt-1 text-2xl font-bold text-emerald-600">{{ $fmt($stats['sales_total']) }}</div>
            <div class="mt-1 text-xs text-slate-400">Satış faturaları (KDV dahil)</div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Toplam Alış</div>
            <div class="mt-1 text-2xl font-bold text-amber-600">{{ $fmt($stats['purchase_total']) }}</div>
            <div class="mt-1 text-xs text-slate-400">Alış faturaları (KDV dahil)</div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Brüt Kâr</div>
            <div class="mt-1 text-2xl font-bold {{ $stats['gross_profit'] >= 0 ? 'text-slate-800' : 'text-red-600' }}">{{ $fmt($stats['gross_profit']) }}</div>
            <div class="mt-1 text-xs text-slate-400">Satış − Alış (KDV hariç)</div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Toplam Tahsilat</div>
            <div class="mt-1 text-2xl font-bold text-indigo-600">{{ $fmt($stats['collection_total']) }}</div>
            <div class="mt-1 text-xs text-slate-400">Nakit + havale toplamı</div>
        </div>
    </div>

    {{-- Kasa durumu --}}
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="card border-l-4 border-l-red-400 p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Toplam Gider</div>
            <div class="mt-1 text-xl font-bold text-red-600">−{{ $fmt($stats['expense_total']) }}</div>
        </div>
        <div class="card border-l-4 border-l-emerald-400 p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Kasada (Nakit)</div>
            <div class="mt-1 text-xl font-bold {{ $stats['cash_nakit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $fmt($stats['cash_nakit']) }}</div>
            <div class="mt-0.5 text-[11px] text-slate-400">Nakit tahsilat − ödeme − gider</div>
        </div>
        <div class="card border-l-4 border-l-indigo-400 p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Bankada (Havale)</div>
            <div class="mt-1 text-xl font-bold {{ $stats['cash_banka'] >= 0 ? 'text-indigo-600' : 'text-red-600' }}">{{ $fmt($stats['cash_banka']) }}</div>
            <div class="mt-0.5 text-[11px] text-slate-400">Havale tahsilat − ödeme − gider</div>
        </div>
    </div>

    {{-- Grafikler --}}
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header">Son 12 Ay — Satış ve Alış Cirosu</div>
            <div class="p-5">
                <canvas id="monthly-chart" height="130"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header">En Çok Satılan Ürünler (adet)</div>
            <div class="p-5">
                <canvas id="top-products-chart" height="200"></canvas>
            </div>
        </div>
    </div>

    {{-- Alacak yaşlandırma --}}
    <div class="card mt-6">
        <div class="card-header flex items-center justify-between">
            <span class="flex items-center gap-2">
                <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Alacak Yaşlandırma (kalan tutarlar)
            </span>
            <span class="text-xs font-normal text-slate-400">Vadesi geçen faturalar gecikme gününe göre gruplanır</span>
        </div>
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Vade Durumu</th>
                    <th class="!text-right">Fatura Sayısı</th>
                    <th class="!text-right">Kalan Tutar</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($aging as $i => $bucket)
                    <tr class="{{ $i >= 1 && $bucket['amount'] > 0 ? 'bg-red-50/50' : '' }}">
                        <td class="font-medium {{ $i >= 1 && $bucket['amount'] > 0 ? 'text-red-700' : 'text-slate-700' }}">{{ $bucket['label'] }}</td>
                        <td class="text-right">{{ $bucket['count'] }}</td>
                        <td class="text-right font-semibold {{ $i >= 1 && $bucket['amount'] > 0 ? 'text-red-700' : '' }}">{{ $fmt($bucket['amount']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-3 text-xs text-slate-400">
        Not: Brüt kâr, satışların ve alışların KDV hariç (net) toplamları arasındaki farktır; stok maliyeti ağırlığı (FIFO) hesaba katılmaz.
    </p>

    {{-- Ürün kâr marjları --}}
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="card">
            <div class="card-header">Ürün Bazında Kâr Marjı</div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>Ürün</th>
                        <th class="!text-right">Alış</th>
                        <th class="!text-right">Satış</th>
                        <th class="!text-right">Birim Kâr</th>
                        <th class="!text-right">Marj %</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($profitMargins as $row)
                        <tr>
                            <td class="font-medium text-slate-700">{{ $row->name }}</td>
                            <td class="text-right text-slate-500">{{ $fmt($row->purchase_price ?? 0) }}</td>
                            <td class="text-right text-slate-500">{{ $fmt($row->sale_price) }}</td>
                            <td class="text-right font-bold {{ $row->margin > 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $fmt($row->margin) }}</td>
                            <td class="text-right {{ ($row->pct ?? 0) > 0 ? 'text-emerald-500' : 'text-rose-500' }}">{{ $row->pct ?? '—' }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-slate-400">Ürün yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">En Çok Kâr Getiren Ürünler (satılan)</div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>Ürün</th>
                        <th class="!text-right">Satılan</th>
                        <th class="!text-right">Toplam Kâr</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($profitByProduct as $row)
                        <tr>
                            <td class="font-medium text-slate-700">{{ $row->product_name }}</td>
                            <td class="text-right">{{ rtrim(rtrim(number_format($row->qty, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="text-right font-bold text-emerald-600">{{ $fmt($row->total_profit) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-8 text-center text-slate-400">Henüz satış yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Grafik verisi ve Chart.js --}}
    <script>
        window.reportData = {
            chart: @json($chart),
            topProducts: @json($topProducts),
        };
    </script>
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                if (typeof Chart === 'undefined' || !window.reportData) return;

                const { chart, topProducts } = window.reportData;
                const money = (v) => new Intl.NumberFormat('tr-TR', { maximumFractionDigits: 0 }).format(v) + ' ₺';

                new Chart(document.getElementById('monthly-chart'), {
                    type: 'bar',
                    data: {
                        labels: chart.labels,
                        datasets: [
                            {
                                label: 'Satış',
                                data: chart.sales,
                                backgroundColor: '#4f46e5',
                                borderRadius: 6,
                            },
                            {
                                label: 'Alış',
                                data: chart.purchases,
                                backgroundColor: '#f59e0b',
                                borderRadius: 6,
                            },
                            {
                                label: 'Gider',
                                data: chart.expenses,
                                backgroundColor: '#ef4444',
                                borderRadius: 6,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { position: 'bottom' } },
                        scales: { y: { beginAtZero: true, ticks: { callback: money } } },
                    },
                });

                if (topProducts.labels.length > 0) {
                    new Chart(document.getElementById('top-products-chart'), {
                        type: 'doughnut',
                        data: {
                            labels: topProducts.labels,
                            datasets: [{
                                data: topProducts.quantities,
                                backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#64748b'],
                            }],
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { position: 'bottom' } },
                        },
                    });
                }
            })();
        </script>
    @endpush
@endsection
