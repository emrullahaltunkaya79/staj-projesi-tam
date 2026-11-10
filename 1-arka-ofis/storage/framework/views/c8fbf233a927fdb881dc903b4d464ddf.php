<?php $__env->startSection('title', 'Yeni Gider'); ?>

<?php $__env->startSection('content'); ?>
    <form method="POST" action="<?php echo e(route('giderler.store')); ?>" class="max-w-2xl">
        <?php echo csrf_field(); ?>

        <div class="card p-6">
            <h2 class="mb-1 text-lg font-bold text-slate-800">Gider Kaydı</h2>
            <p class="mb-6 text-sm text-slate-500">Kira, fatura, maaş gibi işletme giderlerini kaydedin; kasa durumu raporlara yansır.</p>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="label" for="category">Kategori <span class="text-red-500">*</span></label>
                    <select id="category" name="category" class="input" required>
                        <?php $__currentLoopData = \App\Models\Expense::CATEGORIES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($category); ?>" <?php if(old('category', $expense->category) === $category): echo 'selected'; endif; ?>><?php echo e($category); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['category'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="label" for="expense_date">Gider Tarihi <span class="text-red-500">*</span></label>
                    <input type="date" id="expense_date" name="expense_date"
                           value="<?php echo e(old('expense_date', $expense->expense_date)); ?>" required class="input">
                    <?php $__errorArgs = ['expense_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="label" for="amount">Tutar (₺) <span class="text-red-500">*</span></label>
                    <input type="number" id="amount" name="amount" min="0.01" step="0.01"
                           value="<?php echo e(old('amount')); ?>" required class="input" placeholder="0,00">
                    <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <span class="label">Ödeme Türü <span class="text-red-500">*</span></span>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:text-indigo-700">
                            <input type="radio" name="payment_type" value="nakit" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                   <?php if(old('payment_type', 'nakit') === 'nakit'): echo 'checked'; endif; ?>>
                            Nakit (Kasa)
                        </label>
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:text-indigo-700">
                            <input type="radio" name="payment_type" value="havale" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                   <?php if(old('payment_type') === 'havale'): echo 'checked'; endif; ?>>
                            Havale (Banka)
                        </label>
                    </div>
                    <?php $__errorArgs = ['payment_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="sm:col-span-2">
                    <label class="label" for="description">Açıklama</label>
                    <textarea id="description" name="description" rows="2" class="input"
                              placeholder="Örn: Ekim ayı dükkan kirası (opsiyonel)"><?php echo e(old('description')); ?></textarea>
                    <?php $__errorArgs = ['description'];
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
                <button type="submit" class="btn-primary">Gideri Kaydet</button>
                <a href="<?php echo e(route('giderler.index')); ?>" class="btn-secondary">İptal</a>
            </div>
        </div>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/expenses/create.blade.php ENDPATH**/ ?>