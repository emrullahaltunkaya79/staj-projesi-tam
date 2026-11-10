<?php $__env->startSection('title', 'Tekrarlayan Faturalar'); ?>

<?php $__env->startSection('actions'); ?>
    <button type="button" class="btn-primary" data-modal-url="<?php echo e(route('tekrarlayan.create')); ?>" data-modal-title="Yeni Tekrarlayan Fatura Kuralı" data-modal-wide="1">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Kural
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
                    <th>Kural</th>
                    <th>Cari</th>
                    <th>Kesim Günü</th>
                    <th class="!text-right">Kalem</th>
                    <th class="!text-right">Tahmini Tutar</th>
                    <th>Son Kesim</th>
                    <th>Durum</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $rules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $net = collect($rule->items)->sum(fn ($i) => $i['quantity'] * $i['unit_price']);
                        $total = $net * (1 + (float) $rule->tax_rate / 100);
                    ?>
                    <tr class="hover:bg-slate-50">
                        <td class="font-medium text-slate-700"><?php echo e($rule->title); ?></td>
                        <td><?php echo e($rule->contact->name); ?></td>
                        <td>Her ayın <?php echo e($rule->day_of_month); ?>. günü</td>
                        <td class="text-right"><?php echo e(count($rule->items)); ?> ürün</td>
                        <td class="text-right font-semibold"><?php echo e($fmt($total)); ?></td>
                        <td><?php echo e($rule->last_run_at?->format('d.m.Y') ?? '—'); ?></td>
                        <td>
                            <span class="<?php echo e($rule->is_active ? 'badge-green' : 'badge-slate'); ?>">
                                <?php echo e($rule->is_active ? 'Aktif' : 'Duraklatıldı'); ?>

                            </span>
                        </td>
                        <td>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $rule)): ?>
                                <div class="flex justify-end">
                                    <form method="POST" action="<?php echo e(route('tekrarlayan.destroy', $rule)); ?>"
                                          onsubmit="return confirm('Bu kuralı silmek istediğinize emin misiniz?');">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Sil</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="py-10 text-center text-slate-400">Henüz tekrarlayan fatura kuralı yok.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <?php echo e($rules->links()); ?>

        </div>
    </div>

    <p class="mt-3 text-xs text-slate-400">
        Kurallar her gün saat 00:05'te <code>tekrarlayan:faturalar-kes</code> komutuyla işlenir; ayın günü gelen kurallar otomatik satış faturası keser (stok kontrolü dahil).
    </p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/recurring/index.blade.php ENDPATH**/ ?>