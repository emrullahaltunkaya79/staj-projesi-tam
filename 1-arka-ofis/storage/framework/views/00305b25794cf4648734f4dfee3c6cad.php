<?php $__env->startSection('title', 'Depo Stok Matrisi'); ?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(route('depolar.index')); ?>" class="btn-secondary">← Depolar</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="card">
        <div class="card-header">Stok Matrisi — Ürün × Depo</div>
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th class="sticky left-0 bg-slate-50 dark:bg-slate-800 z-10">Ürün</th>
                    <?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <th class="!text-center"><?php echo e($wh->name); ?></th>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <th class="!text-center">Toplam</th>
                </tr>
                </thead>
                <tbody>
                <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $total = $product->totalWarehouseStock(); ?>
                    <?php if($total > 0): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                            <td class="sticky left-0 bg-white dark:bg-slate-800 z-10">
                                <div class="font-medium text-slate-700 dark:text-slate-200"><?php echo e($product->name); ?></div>
                                <div class="text-xs text-slate-400"><?php echo e($product->code ?: '—'); ?> · Sistem stoğu: <?php echo e($product->stock_quantity); ?></div>
                            </td>
                            <?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $qty = $wh->stockOf($product->id); ?>
                                <td class="text-center">
                                    <?php if($qty > 0): ?>
                                        <span class="<?php echo e($qty < 5 ? 'badge-amber' : 'badge-green'); ?>"><?php echo e($qty); ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-300">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <td class="text-center font-bold"><?php echo e($total); ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($product->totalWarehouseStock() === 0 && $product->stock_quantity > 0): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                            <td class="sticky left-0 bg-white dark:bg-slate-800 z-10">
                                <div class="font-medium text-slate-700 dark:text-slate-200"><?php echo e($product->name); ?></div>
                                <div class="text-xs text-amber-500">Henüz depoya atanmamış (sistem stoğu: <?php echo e($product->stock_quantity); ?>)</div>
                            </td>
                            <?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <td class="text-center text-slate-300">—</td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <td class="text-center text-slate-300">0</td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <p class="border-t border-slate-100 dark:border-slate-700 px-4 py-3 text-xs text-slate-400">
            Bu matris her ürünün hangi depoda kaç adet olduğunu gösterir. "Henüz depoya atanmamış" satırları ürün açılış stoğunun depolara dağıtılmadığını belirtir.
        </p>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/warehouses/matrix.blade.php ENDPATH**/ ?>