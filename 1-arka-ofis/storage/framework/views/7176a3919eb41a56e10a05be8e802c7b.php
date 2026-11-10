<?php $__env->startSection('title', 'Cariler İçe Aktar'); ?>

<?php $__env->startSection('content'); ?>
    <div class="max-w-2xl">
        <div class="card p-6">
            <form method="POST" action="<?php echo e(route('cariler.import')); ?>" enctype="multipart/form-data" class="space-y-4">
                <?php echo csrf_field(); ?>
                <div>
                    <h2 class="mb-1 text-lg font-bold text-slate-800">Cariler — Excel'den İçe Aktar</h2>
                    <p class="text-sm text-slate-500">Aynı ünvana sahip cariler güncellenir, olmayanlar yeni kayıt olarak açılır.</p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                    <div class="mb-1 font-semibold text-slate-700">Beklenen sütunlar (ilk satır başlık olarak atlanır):</div>
                    Tip (Müşteri/Tedarikçi) · Ünvan · Telefon · E-posta · Vergi No · Adres
                </div>

                <div>
                    <label class="label" for="file">Excel Dosyası (.xlsx / .csv) <span class="text-red-500">*</span></label>
                    <input type="file" id="file" name="file" required accept=".xlsx,.xls,.csv"
                           class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                    <?php $__errorArgs = ['file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="btn-primary">İçe Aktar</button>
                    <a href="<?php echo e(route('cariler.import.template')); ?>" class="btn-secondary">Şablonu İndir</a>
                </div>
            </form>
        </div>
        <p class="mt-3 text-xs text-slate-400"><a href="<?php echo e(route('cariler.index')); ?>" class="text-indigo-600">← Carilere dön</a></p>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/imports/contacts.blade.php ENDPATH**/ ?>