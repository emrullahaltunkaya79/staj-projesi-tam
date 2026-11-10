<?php $__env->startSection('title', 'Ürün: '.$product->name); ?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(route('urunler.index')); ?>" class="btn-secondary">← Ürünler</a>
    <a href="<?php echo e(route('urunler.edit', $product)); ?>" class="btn-primary">Düzenle</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $fmt = fn ($v) => number_format((float) $v, 2, ',', '.').' ₺'; ?>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        
        <div class="card p-5 lg:col-span-2">
            <div class="card-header border-b-0 pb-0">Görseller</div>

            <?php if($product->images->count()): ?>
                <div class="grid grid-cols-2 gap-3 px-5 pt-4 sm:grid-cols-4">
                    <?php $__currentLoopData = $product->images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="group relative">
                            <img src="<?php echo e($image->dataUri()); ?>" alt="<?php echo e($product->name); ?>"
                                 class="aspect-square w-full rounded-xl border border-slate-200 object-cover dark:border-slate-700">
                            <?php if($image->is_primary): ?>
                                <span class="absolute left-1.5 top-1.5 rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-bold text-white">Ana</span>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $product)): ?>
                                <form method="POST" action="<?php echo e(route('urunler.gorsel.destroy', [$product, $image])); ?>" class="absolute right-1.5 top-1.5">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="flex h-6 w-6 items-center justify-center rounded-full bg-red-600/90 text-xs text-white opacity-0 transition group-hover:opacity-100" title="Sil">×</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php else: ?>
                <div class="px-5 py-6 text-center text-sm text-slate-400">Henüz görsel yüklenmemiş.</div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('urunler.gorsel.upload', $product)); ?>" enctype="multipart/form-data" class="border-t border-slate-100 dark:border-slate-700 mt-4 p-5">
                <?php echo csrf_field(); ?>
                <label class="label" for="images">Görsel Yükle (JPG/PNG/WebP, en fazla 4 MB — çoklu seçilebilir)</label>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <input type="file" id="images" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp"
                           class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                    <button type="submit" class="btn-primary shrink-0">Yükle</button>
                </div>
                <?php $__errorArgs = ['images.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </form>
        </div>

        
        <div class="card p-5">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Ürün Bilgisi</span>
                <?php if($product->category): ?>
                    <span class="badge-slate"><?php echo e($product->category->name); ?></span>
                <?php endif; ?>
            </div>
            <div class="text-xl font-bold text-slate-800 dark:text-slate-100"><?php echo e($product->name); ?></div>
            <div class="mt-1 font-mono text-xs text-slate-400">
                <?php echo e($product->code ?: '—'); ?>

                <?php if($product->barcode): ?>
                    <span class="ml-2 text-slate-500">▐|▍ <?php echo e($product->barcode); ?></span>
                <?php endif; ?>
            </div>

            <dl class="mt-4 space-y-2.5 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Alış</dt><dd class="font-semibold"><?php echo e($fmt($product->purchase_price)); ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Satış</dt><dd class="font-semibold"><?php echo e($fmt($product->sale_price)); ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Birim kâr</dt><dd class="font-bold text-emerald-600"><?php echo e($fmt($product->sale_price - $product->purchase_price)); ?></dd></div>
                <div class="flex justify-between border-t border-slate-100 dark:border-slate-700 pt-2.5">
                    <dt class="text-slate-500">Fiziksel stok</dt>
                    <dd class="font-bold"><?php echo e($product->stock_quantity); ?> <?php echo e($product->unit); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Rezerve</dt>
                    <dd class="<?php echo e($product->reserved_quantity > 0 ? 'font-bold text-amber-600' : 'text-slate-400'); ?>"><?php echo e($product->reserved_quantity); ?></dd>
                </div>
                <div class="flex justify-between border-t border-slate-100 dark:border-slate-700 pt-2.5">
                    <dt class="font-semibold text-slate-600">Müsait</dt>
                    <dd class="font-bold text-indigo-600"><?php echo e($product->availableQuantity()); ?> <?php echo e($product->unit); ?></dd>
                </div>
            </dl>
        </div>
    </div>

    
    <div class="card mt-6 p-5">
        <div class="card-header border-b-0 p-0 pb-3">Depo Dağılımı</div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <?php $__empty_1 = true; $__currentLoopData = $product->warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="rounded-xl border border-slate-200 p-3 text-center dark:border-slate-700">
                    <div class="truncate text-xs font-semibold text-slate-600 dark:text-slate-300"><?php echo e($wh->name); ?></div>
                    <div class="mt-1 text-xl font-black text-slate-800 dark:text-slate-100">
                        <?php echo e($wh->pivot->stock_quantity); ?>

                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-sm text-slate-400">Ürün henüz bir depoya atanmamış.</div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/products/show.blade.php ENDPATH**/ ?>