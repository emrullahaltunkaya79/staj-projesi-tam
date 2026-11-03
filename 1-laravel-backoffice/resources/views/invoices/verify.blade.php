<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fatura Doğrulama{{ $invoice ? ' — '.$invoice->invoice_no : '' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 dark:bg-slate-900">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-md">
            @if ($invoice)
                {{-- DOĞRULANDI --}}
                <div class="card p-6 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                        <svg class="h-8 w-8 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">Fatura Doğrulandı</h1>
                    <p class="mt-2 text-sm text-slate-500">Bu fatura {{ config('app.name') }} sistemi tarafından kesilmiştir.</p>

                    <dl class="mt-6 space-y-3 rounded-xl bg-slate-50 dark:bg-slate-800 p-4 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Fatura No</dt>
                            <dd class="font-bold text-slate-800 dark:text-slate-100">{{ $invoice->invoice_no }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Tip</dt>
                            <dd class="font-medium text-slate-700 dark:text-slate-300">{{ $invoice->typeLabel() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Kesen Şirket</dt>
                            <dd class="font-medium text-slate-700 dark:text-slate-300">{{ $invoice->company->name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Cari</dt>
                            <dd class="font-medium text-slate-700 dark:text-slate-300">{{ $invoice->contact->name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Tarih</dt>
                            <dd class="font-medium text-slate-700 dark:text-slate-300">{{ $invoice->issue_date->format('d.m.Y') }}</dd>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 dark:border-slate-700 pt-3">
                            <dt class="font-semibold text-slate-600">Genel Toplam</dt>
                            <dd class="font-bold text-indigo-600">{{ number_format((float) $invoice->total, 2, ',', '.') }} ₺</dd>
                        </div>
                    </dl>

                    <p class="mt-4 text-xs text-slate-400">Doğrulama kodu: <code class="rounded bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5">{{ $code }}</code></p>
                </div>
            @else
                {{-- BULUNAMADI --}}
                <div class="card p-6 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">Fatura Bulunamadı</h1>
                    <p class="mt-2 text-sm text-slate-500">
                        <code class="rounded bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5">{{ $code }}</code>
                        koduna ait geçerli bir fatura kaydı yoktur.
                    </p>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
