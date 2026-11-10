<?php $__env->startSection('title', 'Ürünler'); ?>

<?php $__env->startSection('actions'); ?>
    <button type="button" class="btn-secondary" data-modal-url="<?php echo e(route('urunler.stok-sayim.import.form')); ?>" data-modal-title="Stok Sayımı Yükle">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 14l5-5 5 5M12 9V3"/>
        </svg>
        Sayım Yükle
    </button>
    <button type="button" class="btn-secondary" data-modal-url="<?php echo e(route('urunler.import.form')); ?>" data-modal-title="Ürünler — İçe Aktar">İçe Aktar</button>
    <a href="<?php echo e(route('urunler.stok-sayim')); ?>" class="btn-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        Stok Sayım
    </a>
    <a href="<?php echo e(route('urunler.export')); ?>" class="btn-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
        </svg>
        Excel
    </a>
    <button type="button" class="btn-primary" data-modal-url="<?php echo e(route('urunler.create')); ?>" data-modal-title="Yeni Ürün">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Ürün
    </button>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php
        $chips = [];
        $q = request()->query();
        if (! empty($q['search'])) $chips[] = ['label' => 'Arama', 'value' => $q['search'], 'url' => request()->fullUrlWithQuery(['search' => null, 'page' => null])];
        if (! empty($q['category'])) { $cat = $categories->firstWhere('id', (int) $q['category']); $chips[] = ['label' => 'Kategori', 'value' => $cat?->name ?? $q['category'], 'url' => request()->fullUrlWithQuery(['category' => null, 'page' => null])]; }
        if (! empty($q['low_stock'])) $chips[] = ['label' => 'Stok', 'value' => 'sadece kritik', 'url' => request()->fullUrlWithQuery(['low_stock' => null, 'page' => null])];
    ?>
    <?php echo $__env->make('partials.chips', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    ?>

    
    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <input type="text" name="search" value="<?php echo e($filters['search']); ?>" class="input sm:max-w-xs"
               placeholder="Ürün adı veya kodu ara...">
        <select name="category" class="input sm:w-44">
            <option value="">Tüm kategoriler</option>
            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($cat->id); ?>" <?php if($filters['category'] == $cat->id): echo 'selected'; endif; ?>><?php echo e($cat->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="low_stock" value="1" <?php if($filters['low_stock']): echo 'checked'; endif; ?> class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            Sadece kritik stok (&lt; 5)
        </label>
        <button type="submit" class="btn-secondary !h-10 sm:w-28">Filtrele</button>
    </form>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th class="w-14"></th>
                    <th>Kod</th>
                    <th>Ürün Adı</th>
                    <th>Kategori</th>
                    <th>Birim</th>
                    <th class="!text-right">Alış Fiyatı</th>
                    <th class="!text-right">Satış Fiyatı</th>
                    <th class="!text-right">Stok</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50">
                        <td>
                            <?php if($product->primaryImageUri()): ?>
                                <img src="<?php echo e($product->primaryImageUri()); ?>" alt="" class="h-10 w-10 rounded-lg border border-slate-200 object-cover dark:border-slate-700">
                            <?php else: ?>
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-300 dark:bg-slate-700 dark:text-slate-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="font-mono text-xs text-slate-500"><?php echo e($product->code ?: '—'); ?></div>
                            <?php if($product->barcode): ?>
                                <div class="mt-0.5 font-mono text-[10px] text-slate-400"><?php echo e($product->barcode); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="font-medium">
                            <a href="<?php echo e(route('urunler.show', $product)); ?>" data-modal-url="<?php echo e(route('urunler.show', $product)); ?>" data-modal-title="Ürün: <?php echo e($product->name); ?>" data-modal-view="1" class="text-slate-700 hover:text-indigo-600 dark:text-slate-200"><?php echo e($product->name); ?></a>
                        </td>
                        <td>
                            <?php if($product->category): ?>
                                <span class="badge-slate"><?php echo e($product->category->name); ?></span>
                            <?php else: ?>
                                <span class="text-slate-300">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($product->unit); ?></td>
                        <td class="text-right tabular-nums"><?php echo e($fmt($product->purchase_price)); ?></td>
                        <td class="text-right tabular-nums"><?php echo e($fmt($product->sale_price)); ?></td>
                        <td class="text-right tabular-nums">
                            <span class="<?php echo e($product->stock_quantity == 0 ? 'badge-red' : ($product->isLowStock() ? 'badge-amber' : 'badge-green')); ?>">
                                <?php echo e($product->stock_quantity); ?> <?php echo e($product->unit); ?>

                            </span>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <a href="<?php echo e(route('urunler.show', $product)); ?>" data-modal-url="<?php echo e(route('urunler.show', $product)); ?>" data-modal-title="Ürün: <?php echo e($product->name); ?>" data-modal-view="1" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Detay</a>
                                <a href="<?php echo e(route('urunler.edit', $product)); ?>" data-modal-url="<?php echo e(route('urunler.edit', $product)); ?>" data-modal-title="Ürünü Düzenle" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Düzenle</a>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $product)): ?>
                                    <form method="POST" action="<?php echo e(route('urunler.destroy', $product)); ?>"
                                          onsubmit="return confirm('Bu ürünü silmek istediğinize emin misiniz?');">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Sil</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php echo $__env->make('partials.empty', ['colspan' => 7, 'icon' => 'box', 'title' => 'Kayıtlı ürün yok', 'desc' => 'İlk ürününüzü ekleyin — stok, kategori ve barkodla birlikte kataloğunuz oluşur.', 'action' => 'Yeni Ürün Ekle', 'actionUrl' => 'http://localhost/urunler/create', 'actionTitle' => 'Yeni Ürün'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <?php echo e($products->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/products/index.blade.php ENDPATH**/ ?>