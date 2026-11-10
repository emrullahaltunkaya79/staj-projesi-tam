<?php $__env->startSection('title', 'Faturalar'); ?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(route('faturalar.export')); ?>" class="btn-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
        </svg>
        Excel
    </a>
    <button type="button" class="btn-secondary" data-modal-url="<?php echo e(route('alislar.create')); ?>" data-modal-title="Yeni Alış Faturası" data-modal-wide="1">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>
        </svg>
        Alış Faturası
    </button>
    <button type="button" class="btn-primary" data-modal-url="<?php echo e(route('faturalar.create')); ?>" data-modal-title="Yeni Satış Faturası" data-modal-wide="1">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Satış Faturası
    </button>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php
        $chips = [];
        if (! empty(request('search'))) $chips[] = ['label' => 'Arama', 'value' => request('search'), 'url' => request()->fullUrlWithQuery(['search' => null, 'page' => null])];
    ?>
    <?php echo $__env->make('partials.chips', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    ?>

    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <input type="text" name="search" value="<?php echo e($filters['search']); ?>" class="input sm:max-w-xs"
               placeholder="Fatura no veya cari adı ara...">
        <button type="submit" class="btn-secondary !h-10 sm:w-28">Ara</button>
    </form>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Fatura No</th>
                    <th>Tip</th>
                    <th>Cari</th>
                    <th>Tarih</th>
                    <th class="!text-right">Ara Toplam</th>
                    <th class="!text-right">KDV</th>
                    <th class="!text-right">Genel Toplam</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50">
                        <td>
                            <a href="<?php echo e(route('faturalar.show', $invoice)); ?>" data-modal-url="<?php echo e(route('faturalar.show', $invoice)); ?>" data-modal-title="Fatura <?php echo e($invoice->invoice_no); ?>" data-modal-wide="1" class="font-semibold text-indigo-600 hover:text-indigo-700"><?php echo e($invoice->invoice_no); ?></a>
                        </td>
                        <td>
                            <span class="<?php echo e($invoice->typeBadgeClass()); ?>"><?php echo e($invoice->typeShortLabel()); ?></span>
                        </td>
                        <td><?php echo e($invoice->contact->name); ?></td>
                        <td><?php echo e($invoice->issue_date->format('d.m.Y')); ?></td>
                        <td class="text-right tabular-nums"><?php echo e($fmt($invoice->subtotal)); ?></td>
                        <td class="text-right tabular-nums">%<?php echo e(rtrim(rtrim(number_format($invoice->tax_rate, 2, ',', '.'), '0'), ',')); ?> (<?php echo e($fmt($invoice->tax_amount)); ?>)</td>
                        <td class="text-right font-bold"><?php echo e($fmt($invoice->total)); ?></td>
                        <td class="text-right tabular-nums">
                            <a href="<?php echo e(route('faturalar.show', $invoice)); ?>" data-modal-url="<?php echo e(route('faturalar.show', $invoice)); ?>" data-modal-title="Fatura <?php echo e($invoice->invoice_no); ?>" data-modal-wide="1" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Görüntüle</a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php echo $__env->make('partials.empty', ['colspan' => 8, 'icon' => 'invoice', 'title' => 'Henüz fatura yok', 'desc' => 'İlk faturanızı kesin — stoklar düşer, cari bakiyesi otomatik işlenir.', 'action' => 'Yeni Fatura Kes', 'actionUrl' => 'http://localhost/faturalar/create', 'actionTitle' => 'Yeni Satış Faturası', 'wide' => 1], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <?php echo e($invoices->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/invoices/index.blade.php ENDPATH**/ ?>