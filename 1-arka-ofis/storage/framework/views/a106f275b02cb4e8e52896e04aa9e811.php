<?php $__env->startSection('title', 'Yeni Tekrarlayan Fatura'); ?>

<?php $__env->startSection('content'); ?>
    <form method="POST" action="<?php echo e(route('tekrarlayan.store')); ?>" id="invoice-form" class="max-w-5xl">
        <?php echo csrf_field(); ?>

        
        <div class="card mb-4 grid grid-cols-1 gap-4 p-6 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <label class="label" for="title">Kural Adı <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" value="<?php echo e(old('title')); ?>" required class="input" placeholder="Örn: Aylık Bakım Faturası">
                <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div>
                <label class="label" for="contact_id">Cari (Müşteri) <span class="text-red-500">*</span></label>
                <select id="contact_id" name="contact_id" class="input" required>
                    <option value="">— Cari seçin —</option>
                    <?php $__currentLoopData = $contacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contact): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($contact->id); ?>" <?php if(old('contact_id') == $contact->id): echo 'selected'; endif; ?>><?php echo e($contact->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['contact_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div>
                <label class="label" for="day_of_month">Kesim Günü <span class="text-red-500">*</span></label>
                <select id="day_of_month" name="day_of_month" class="input" required>
                    <?php for($d = 1; $d <= 28; $d++): ?>
                        <option value="<?php echo e($d); ?>" <?php if(old('day_of_month', 1) == $d): echo 'selected'; endif; ?>>Ayın <?php echo e($d); ?>. günü</option>
                    <?php endfor; ?>
                </select>
                <?php $__errorArgs = ['day_of_month'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div>
                <label class="label" for="tax_rate">KDV Oranı <span class="text-red-500">*</span></label>
                <select id="tax_rate" name="tax_rate" class="input" required>
                    <?php $__currentLoopData = [20 => '%20 (Standart)', 10 => '%10 (Orta)', 1 => '%1 (Düşük)', 0 => 'KDV Yok']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rate => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($rate); ?>" <?php if(old('tax_rate', 20) == $rate): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['tax_rate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        
        <div class="card mb-4">
            <div class="card-header flex items-center justify-between">
                <span>Kalem Şablonu</span>
                <button type="button" id="add-row" class="btn-secondary !py-1.5 !px-3 text-xs">+ Kalem Ekle</button>
            </div>

            <div class="overflow-x-auto p-4">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                    <tr class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-2 pb-2 w-[38%]">Ürün</th>
                        <th class="px-2 pb-2 w-[16%]">Miktar</th>
                        <th class="px-2 pb-2 w-[18%]">Birim Fiyat (₺)</th>
                        <th class="px-2 pb-2 w-[18%] text-right">Tutar</th>
                        <th class="px-2 pb-2 w-[10%]"></th>
                    </tr>
                    </thead>
                    <tbody id="invoice-items"></tbody>
                </table>
                <?php $__errorArgs = ['items'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-xs font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="flex justify-end border-t border-slate-200 p-5">
                <div class="w-full max-w-xs space-y-2">
                    <div class="flex justify-between text-sm text-slate-600">
                        <span>Ara Toplam</span>
                        <span id="subtotal">0,00 ₺</span>
                    </div>
                    <div class="flex justify-between text-sm text-slate-600">
                        <span>KDV</span>
                        <span id="tax-amount">0,00 ₺</span>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-lg font-bold text-slate-800">
                        <span>Tahmini Tutar</span>
                        <span id="grand-total">0,00 ₺</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <label class="label" for="notes">Not</label>
            <textarea id="notes" name="notes" rows="2" class="input"
                      placeholder="Fatura notu (opsiyonel)"><?php echo e(old('notes')); ?></textarea>

            <div class="mt-5 flex items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn-primary">Kuralı Oluştur</button>
                <a href="<?php echo e(route('tekrarlayan.index')); ?>" class="btn-secondary">İptal</a>
                <span class="ml-auto text-xs text-slate-400">Kural yalnızca şablondur; fatura kesim anında stok kontrolü yapılır.</span>
            </div>
        </div>
    </form>

    <script>
        window.invoiceProducts = <?php echo json_encode($products, 15, 512) ?>;
        window.invoiceFormConfig = { priceKey: 'sale_price', stockCheck: false };
    </script>

    <?php $__env->startPush('scripts'); ?>
        <script src="<?php echo e(asset('js/invoice-form.js')); ?>"></script>
    <?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/recurring/create.blade.php ENDPATH**/ ?>