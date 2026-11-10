<?php $__env->startSection('title', $warehouse->name . ' — Depo Stoğu'); ?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(route('depolar.index')); ?>" class="btn-secondary">← Depolar</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $fmt = fn ($v) => number_format((float) $v, 2, ',', '.').' ₺';
    ?>

    <div class="mb-4 flex items-center gap-3">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 text-lg font-black text-white">
            <?php echo e(mb_substr($warehouse->name, 0, 1)); ?>

        </div>
        <div>
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100"><?php echo e($warehouse->name); ?></h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                <?php echo e($warehouse->code ? $warehouse->code . ' · ' : ''); ?><?php echo e($stocks->count()); ?> ürün · Toplam <?php echo e($stocks->sum('stock_quantity')); ?> adet
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        
        <div class="card xl:col-span-2">
            <div class="card-header">Depo Stoğu</div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>Ürün</th>
                        <th>Kategori</th>
                        <th class="!text-right">Stok</th>
                        <th class="!text-right">Stok Değeri</th>
                        <th>Transfer</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $stocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                            <td>
                                <div class="font-medium text-slate-700 dark:text-slate-200"><?php echo e($row->product->name); ?></div>
                                <div class="text-xs text-slate-400"><?php echo e($row->product->code ?: '—'); ?></div>
                            </td>
                            <td>
                                <?php if($row->product->category): ?>
                                    <span class="badge-slate"><?php echo e($row->product->category->name); ?></span>
                                <?php else: ?>
                                    <span class="text-slate-300">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <span class="<?php echo e($row->stock_quantity < 5 ? 'badge-amber' : 'badge-green'); ?>">
                                    <?php echo e($row->stock_quantity); ?> <?php echo e($row->product->unit); ?>

                                </span>
                            </td>
                            <td class="text-right font-semibold"><?php echo e($fmt($row->stock_quantity * $row->product->purchase_price)); ?></td>
                            <td>
                                <?php if(count($allWarehouses) > 0): ?>
                                    <form method="POST" action="<?php echo e(route('depolar.transfer', $warehouse)); ?>"
                                          class="flex items-center gap-1" style="min-height:0;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="product_id" value="<?php echo e($row->product_id); ?>">
                                        <select name="to_warehouse_id" class="rounded-lg border-slate-300 text-xs dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200" style="min-height:0;padding:4px 8px;">
                                            <?php $__currentLoopData = $allWarehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $target): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($target->id); ?>"><?php echo e($target->name); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                        <input type="number" name="quantity" value="1" min="1" max="<?php echo e($row->stock_quantity); ?>"
                                               class="w-16 rounded-lg border-slate-300 text-xs dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200" style="min-height:0;padding:4px 8px;">
                                        <button type="submit" class="rounded-lg bg-indigo-50 px-2 py-1 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100">→</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="py-10 text-center text-slate-400">Bu depoda stok yok.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        
        <div class="card p-5">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Depo Özeti</div>
            <dl class="mt-3 space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Ürün çeşidi</dt>
                    <dd class="font-bold"><?php echo e($stocks->count()); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Toplam adet</dt>
                    <dd class="font-bold"><?php echo e($stocks->sum('stock_quantity')); ?></dd>
                </div>
                <div class="flex justify-between border-t border-slate-100 dark:border-slate-700 pt-3">
                    <dt class="text-slate-500">Stok değeri (alış)</dt>
                    <dd class="font-bold"><?php echo e($fmt($stocks->sum(fn ($s) => $s->stock_quantity * $s->product->purchase_price))); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Stok değeri (satış)</dt>
                    <dd class="font-bold"><?php echo e($fmt($stocks->sum(fn ($s) => $s->stock_quantity * $s->product->sale_price))); ?></dd>
                </div>
            </dl>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/warehouses/stock.blade.php ENDPATH**/ ?>