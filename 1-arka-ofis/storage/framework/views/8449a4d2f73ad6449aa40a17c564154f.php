<?php $__env->startSection('title', 'Depolar'); ?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(route('depolar.distribute')); ?>" class="btn-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
        </svg>
        Stok Dağıt
    </a>
    <a href="<?php echo e(route('depolar.matrix')); ?>" class="btn-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M9 19V9m6 10V9M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>
        </svg>
        Stok Matrisi
    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $fmt = fn ($v) => number_format((float) $v, 2, ',', '.').' ₺'; ?>

    
    <?php if($summaries->count() > 0): ?>
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <?php $__currentLoopData = $summaries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('depolar.stock', $s->id)); ?>" class="card p-5 transition hover:border-indigo-400 hover:shadow-md">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-bold text-slate-800 dark:text-slate-100"><?php echo e($s->name); ?></div>
                            <div class="mt-0.5 text-xs text-slate-400"><?php echo e($s->code ?: '—'); ?> · <?php echo e($s->is_active ? 'Aktif' : 'Pasif'); ?></div>
                        </div>
                        <?php if($s->critical_count > 0): ?>
                            <span class="badge-red shrink-0"><?php echo e($s->critical_count); ?> kritik</span>
                        <?php else: ?>
                            <span class="badge-green shrink-0">✓</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                        <div>
                            <div class="text-lg font-bold text-slate-800 dark:text-slate-100"><?php echo e($s->product_count); ?></div>
                            <div class="text-[10px] text-slate-400">Ürün</div>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-slate-800 dark:text-slate-100"><?php echo e($s->total_items); ?></div>
                            <div class="text-[10px] text-slate-400">Adet</div>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-indigo-600 dark:text-indigo-400"><?php echo e($fmt($s->stock_value)); ?></div>
                            <div class="text-[10px] text-slate-400">Değer</div>
                        </div>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    
    <div class="card mb-4 p-5">
        <h3 class="mb-3 font-bold text-slate-800 dark:text-slate-100">Yeni Depo</h3>
        <form method="POST" action="<?php echo e(route('depolar.store')); ?>" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <?php echo csrf_field(); ?>
            <div class="flex-1">
                <label class="label" for="name">Depo Adı</label>
                <input type="text" id="name" name="name" required class="input" placeholder="Örn: Merkez Depo">
            </div>
            <div class="sm:w-28">
                <label class="label" for="code">Kod</label>
                <input type="text" id="code" name="code" class="input" placeholder="MRK">
            </div>
            <div class="flex-[2]">
                <label class="label" for="address">Adres</label>
                <input type="text" id="address" name="address" class="input" placeholder="Adres (opsiyonel)">
            </div>
            <button type="submit" class="btn-primary">Ekle</button>
        </form>
    </div>

    
    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Depo</th>
                    <th>Kod</th>
                    <th>Adres</th>
                    <th class="!text-right">Ürün Çeşidi</th>
                    <th>Durum</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                        <td>
                            <a href="<?php echo e(route('depolar.stock', $wh)); ?>" class="font-semibold text-indigo-600 hover:text-indigo-700">
                                <?php echo e($wh->name); ?>

                            </a>
                        </td>
                        <td class="font-mono text-xs"><?php echo e($wh->code ?: '—'); ?></td>
                        <td class="text-slate-500 dark:text-slate-400"><?php echo e($wh->address ?: '—'); ?></td>
                        <td class="text-right"><?php echo e($wh->stocks_count); ?></td>
                        <td>
                            <span class="<?php echo e($wh->is_active ? 'badge-green' : 'badge-slate'); ?>">
                                <?php echo e($wh->is_active ? 'Aktif' : 'Pasif'); ?>

                            </span>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <a href="<?php echo e(route('depolar.excel', $wh)); ?>" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700" title="Excel'e aktar">Excel</a>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $wh)): ?>
                                    <form method="POST" action="<?php echo e(route('depolar.destroy', $wh)); ?>"
                                          onsubmit="return confirm('Bu depoyu silmek istediğinize emin misiniz?');">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Sil</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="py-10 text-center text-slate-400">Henüz depo yok.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/warehouses/index.blade.php ENDPATH**/ ?>