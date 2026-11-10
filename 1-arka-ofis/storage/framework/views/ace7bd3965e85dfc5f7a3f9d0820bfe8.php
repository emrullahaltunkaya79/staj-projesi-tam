<?php $__env->startSection('title', 'Tahsilatlar'); ?>

<?php $__env->startSection('actions'); ?>
    <button type="button" class="btn-primary" data-modal-url="<?php echo e(route('payments.create')); ?>" data-modal-title="Yeni Tahsilat">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Tahsilat
    </button>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    ?>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Cari</th>
                    <th>Tür</th>
                    <th>Yön</th>
                    <th class="!text-right">Tutar</th>
                    <th>Açıklama</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap"><?php echo e($payment->created_at->format('d.m.Y H:i')); ?></td>
                        <td>
                            <a href="<?php echo e(route('cariler.show', $payment->contact)); ?>" class="font-medium text-indigo-600 hover:text-indigo-700">
                                <?php echo e($payment->contact->name); ?>

                            </a>
                        </td>
                        <td>
                            <span class="<?php echo e($payment->isCash() ? 'badge-green' : 'badge-indigo'); ?>"><?php echo e($payment->typeLabel()); ?></span>
                        </td>
                        <td>
                            <span class="<?php echo e($payment->isCollection() ? 'text-xs font-semibold text-emerald-600' : 'text-xs font-semibold text-amber-600'); ?>">
                                <?php echo e($payment->directionLabel()); ?>

                            </span>
                        </td>
                        <td class="text-right font-semibold tabular-nums <?php echo e($payment->isCollection() ? 'text-emerald-600' : 'text-amber-600'); ?>">
                            <?php echo e($payment->isCollection() ? '+' : '−'); ?><?php echo e($fmt($payment->amount)); ?>

                        </td>
                        <td class="max-w-xs truncate text-slate-500" title="<?php echo e($payment->description); ?>"><?php echo e($payment->description ?: '—'); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php echo $__env->make('partials.empty', ['colspan' => 6, 'icon' => 'cash', 'title' => 'Henüz tahsilat yok', 'desc' => 'Cari bakiyelerini kapatmak için ilk tahsilatı kaydedin — nakit veya banka.', 'action' => 'Yeni Tahsilat Gir', 'actionUrl' => 'http://localhost/tahsilatlar/create', 'actionTitle' => 'Yeni Tahsilat'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <?php echo e($payments->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/payments/index.blade.php ENDPATH**/ ?>