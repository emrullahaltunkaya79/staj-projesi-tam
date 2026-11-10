    <div class="card p-6">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <label class="label" for="name">Ürün Adı <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" value="<?php echo e(old('name', $product->name)); ?>" required
                               class="input" placeholder="USB Kablosu 2m">
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="sm:w-52">
                        <label class="label" for="category_id">Kategori</label>
                        <select id="category_id" name="category_id" class="input">
                            <option value="">— Kategorisiz —</option>
                            <?php $__currentLoopData = \App\Models\Category::orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($cat->id); ?>" <?php if(old('category_id', $product->category_id) == $cat->id): echo 'selected'; endif; ?>><?php echo e($cat->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <form method="POST" action="<?php echo e(route('kategoriler.store')); ?>" class="mt-1.5 flex gap-1">
                            <?php echo csrf_field(); ?>
                            <input type="text" name="name" placeholder="Yeni kategori…" class="w-full rounded-lg border-slate-200 bg-slate-50 px-2 py-1.5 text-xs focus:border-indigo-400 focus:bg-white focus:outline-none" style="min-height: 0;">
                            <button type="submit" class="shrink-0 rounded-lg bg-indigo-50 px-2.5 py-1.5 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100">+</button>
                        </form>
                    </div>
                </div>
            </div>

        <div>
            <label class="label" for="code">Ürün Kodu</label>
            <input type="text" id="code" name="code" value="<?php echo e(old('code', $product->code)); ?>"
                   class="input" placeholder="URN-001">
            <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
            <label class="label" for="barcode">Barkod <span class="font-normal text-slate-400">(EAN/POS)</span></label>
            <input type="text" id="barcode" name="barcode" value="<?php echo e(old('barcode', $product->barcode)); ?>"
                   class="input" placeholder="8690000000000">
            <?php $__errorArgs = ['barcode'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
            <label class="label" for="unit">Birim <span class="text-red-500">*</span></label>
            <select id="unit" name="unit" class="input" required>
                <?php $__currentLoopData = ['adet', 'kg', 'lt', 'metre', 'paket', 'koli']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($unit); ?>" <?php if(old('unit', $product->unit) === $unit): echo 'selected'; endif; ?>><?php echo e($unit); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php $__errorArgs = ['unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
            <label class="label" for="stock_quantity">Stok Adedi <span class="text-red-500">*</span></label>
            <input type="number" id="stock_quantity" name="stock_quantity" min="0" step="1"
                   value="<?php echo e(old('stock_quantity', $product->stock_quantity)); ?>" required class="input">
            <?php $__errorArgs = ['stock_quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
            <label class="label" for="purchase_price">Alış Fiyatı (₺) <span class="text-red-500">*</span></label>
            <input type="number" id="purchase_price" name="purchase_price" min="0" step="0.01"
                   value="<?php echo e(old('purchase_price', $product->purchase_price)); ?>" required class="input">
            <?php $__errorArgs = ['purchase_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
            <label class="label" for="sale_price">Satış Fiyatı (₺) <span class="text-red-500">*</span></label>
            <input type="number" id="sale_price" name="sale_price" min="0" step="0.01"
                   value="<?php echo e(old('sale_price', $product->sale_price)); ?>" required class="input">
            <?php $__errorArgs = ['sale_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
    </div>

    <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
        <button type="submit" class="btn-primary">Kaydet</button>
        <a href="<?php echo e(route('urunler.index')); ?>" class="btn-secondary">İptal</a>
    </div>
</div>
<?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/products/_form.blade.php ENDPATH**/ ?>