<?php $__env->startSection('title', 'Stok Hareketleri'); ?>

<?php $__env->startSection('content'); ?>

    <?php
        $chips = [];
        $q = request()->query();
        if (! empty($q['product_id'])) { $p = $products->firstWhere('id', (int) $q['product_id']); $chips[] = ['label' => 'Ürün', 'value' => $p?->name ?? $q['product_id'], 'url' => request()->fullUrlWithQuery(['product_id' => null, 'page' => null])]; }
        if (! empty($q['type'])) $chips[] = ['label' => 'Tip', 'value' => $q['type'], 'url' => request()->fullUrlWithQuery(['type' => null, 'page' => null])];
        if (! empty($q['warehouse_id'])) { $w = $warehouses->firstWhere('id', (int) $q['warehouse_id']); $chips[] = ['label' => 'Depo', 'value' => $w?->name ?? $q['warehouse_id'], 'url' => request()->fullUrlWithQuery(['warehouse_id' => null, 'page' => null])]; }
        if (! empty($q['direction'])) $chips[] = ['label' => 'Yön', 'value' => $q['direction'], 'url' => request()->fullUrlWithQuery(['direction' => null, 'page' => null])];
    ?>
    <?php echo $__env->make('partials.chips', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <select name="product_id" class="input sm:w-56">
            <option value="">Tüm ürünler</option>
            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($product->id); ?>" <?php if($filters['product_id'] == $product->id): echo 'selected'; endif; ?>><?php echo e($product->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select name="type" class="input sm:w-40">
            <option value="">Tüm hareket türleri</option>
            <?php $__currentLoopData = ['acilis' => 'Açılış', 'alis' => 'Alış', 'satis' => 'Satış', 'iade' => 'İade', 'duzeltme' => 'Düzeltme']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($value); ?>" <?php if($filters['type'] === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select name="warehouse_id" class="input sm:w-40">
            <option value="">Tüm depolar</option>
            <?php $__currentLoopData = $warehouses ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($wh->id); ?>" <?php if(($filters['warehouse_id'] ?? '') == $wh->id): echo 'selected'; endif; ?>><?php echo e($wh->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select name="direction" class="input sm:w-36">
            <option value="">Giriş + Çıkış</option>
            <option value="giris" <?php if($filters['direction'] === 'giris'): echo 'selected'; endif; ?>>Sadece Giriş</option>
            <option value="cikis" <?php if($filters['direction'] === 'cikis'): echo 'selected'; endif; ?>>Sadece Çıkış</option>
        </select>
        <button type="submit" class="btn-secondary !h-10 sm:w-28">Filtrele</button>
    </form>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Ürün</th>
                    <th>Tür</th>
                    <th>Yön</th>
                    <th class="!text-right">Miktar</th>
                    <th class="!text-right">İşlem Sonrası Stok</th>
                    <th>Kaynak</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $movements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $movement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap"><?php echo e($movement->created_at->format('d.m.Y H:i')); ?></td>
                        <td class="font-medium text-slate-700"><?php echo e($movement->product_name); ?></td>
                        <td>
                            <span class="<?php echo e(match($movement->type) {
                                'alis' => 'badge-amber',
                                'satis' => 'badge-indigo',
                                'iade' => 'badge-red',
                                'acilis' => 'badge-green',
                                default => 'badge-slate',
                            }); ?>"><?php echo e($movement->typeLabel()); ?></span>
                        </td>
                        <td>
                            <?php if($movement->isIncoming()): ?>
                                <span class="font-semibold text-emerald-600">↑ Giriş</span>
                            <?php else: ?>
                                <span class="font-semibold text-red-600">↓ Çıkış</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right font-semibold"><?php echo e(rtrim(rtrim(number_format($movement->quantity, 2, ',', '.'), '0'), ',')); ?></td>
                        <td class="text-right tabular-nums"><?php echo e(rtrim(rtrim(number_format($movement->stock_after, 2, ',', '.'), '0'), ',')); ?></td>
                        <td>
                            <?php if($movement->invoice): ?>
                                <a href="<?php echo e(route('faturalar.show', $movement->invoice)); ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                                    <?php echo e($movement->invoice->invoice_no); ?>

                                </a>
                            <?php else: ?>
                                <span class="text-xs text-slate-400"><?php echo e($movement->description); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php echo $__env->make('partials.empty', ['colspan' => 7, 'icon' => 'swap', 'title' => 'Henüz stok hareketi yok', 'desc' => 'Hareketler fatura, sayım ve depo transferleriyle otomatik oluşur — manuel giriş gerekmez.'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <?php echo e($movements->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/movements/index.blade.php ENDPATH**/ ?>