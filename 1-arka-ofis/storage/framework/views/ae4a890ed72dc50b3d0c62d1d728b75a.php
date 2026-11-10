<?php $__env->startSection('title', 'Stok Dağıtım Aracı'); ?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(route('depolar.index')); ?>" class="btn-secondary">← Depolar</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $fmt = fn ($v) => number_format((float) $v, 2, ',', '.').' ₺'; ?>

    <div class="card mb-4 p-5">
        <h3 class="mb-1 font-bold text-slate-800 dark:text-slate-100">Toplu Stok Dağıtımı</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Aşağıdaki ürünlerin stokları depolara tam olarak dağıtılmamış. Her ürün için depolara adet girin; toplam sistem stokunu aşamaz.
        </p>
    </div>

    <?php if($unassigned->isEmpty()): ?>
        <div class="card p-10 text-center text-slate-400">Tüm stoklar depolara dağıtılmış. 👍</div>
    <?php else: ?>
        <form method="POST" action="<?php echo e(route('depolar.distribute.store')); ?>">
            <?php echo csrf_field(); ?>
            <div class="card overflow-x-auto">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>Ürün</th>
                        <th class="!text-right">Sistem Stoğu</th>
                        <th class="!text-right">Depolarda</th>
                        <th class="!text-right">Atanmamış</th>
                        <?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <th class="!text-center"><?php echo e($wh->name); ?></th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__currentLoopData = $unassigned; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $inWarehouse = $product->totalWarehouseStock(); ?>
                        <tr>
                            <td>
                                <div class="font-medium text-slate-700 dark:text-slate-200"><?php echo e($product->name); ?></div>
                                <div class="text-xs text-slate-400"><?php echo e($product->code ?: '—'); ?></div>
                            </td>
                            <td class="text-right font-bold"><?php echo e($product->stock_quantity); ?></td>
                            <td class="text-right text-slate-500"><?php echo e($inWarehouse); ?></td>
                            <td class="text-right font-bold text-amber-600"><?php echo e($product->stock_quantity - $inWarehouse); ?></td>
                            <?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <td class="text-center">
                                    <input type="number" name="assignments[<?php echo e($product->id); ?>][<?php echo e($wh->id); ?>]"
                                           value="0" min="0" max="<?php echo e($product->stock_quantity); ?>"
                                           class="w-20 rounded-lg border-slate-300 text-center text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                                           style="min-height: 0; padding: 4px 8px;">
                                </td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex items-center gap-3">
                <button type="submit" class="btn-primary">Dağıtımı Uygula</button>
                <span class="text-xs text-slate-400">Girilen adetler depo stoklarına eklenir; sistem stoğu değişmez.</span>
            </div>
        </form>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/warehouses/distribute.blade.php ENDPATH**/ ?>