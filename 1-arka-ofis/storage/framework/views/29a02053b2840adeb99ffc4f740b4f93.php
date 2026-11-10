<?php $__env->startSection('title', 'Teklif Düzenle: '.$quote->quote_no); ?>

<?php $__env->startSection('content'); ?>
    <form method="POST" action="<?php echo e(route('teklifler.update', $quote)); ?>" id="invoice-form" class="max-w-5xl">
        <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

        <div class="card mb-4 grid grid-cols-1 gap-4 p-6 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <label class="label" for="contact_id">Cari (Müşteri) <span class="text-red-500">*</span></label>
                <select id="contact_id" name="contact_id" class="input" required>
                    <option value="">— Cari seçin —</option>
                    <?php $__currentLoopData = $contacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contact): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($contact->id); ?>" <?php if(old('contact_id', $quote->contact_id) == $contact->id): echo 'selected'; endif; ?>>
                            <?php echo e($contact->name); ?>

                        </option>
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
                <label class="label" for="issue_date">Teklif Tarihi <span class="text-red-500">*</span></label>
                <input type="date" id="issue_date" name="issue_date" value="<?php echo e(old('issue_date', $quote->issue_date->toDateString())); ?>"
                       required class="input">
                <?php $__errorArgs = ['issue_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div>
                <label class="label" for="valid_until">Geçerlilik Tarihi</label>
                <input type="date" id="valid_until" name="valid_until" value="<?php echo e(old('valid_until', $quote->valid_until?->toDateString())); ?>" class="input">
                <?php $__errorArgs = ['valid_until'];
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
                        <option value="<?php echo e($rate); ?>" <?php if(old('tax_rate', (int) $quote->tax_rate) == $rate): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
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
                <span>Teklif Kalemleri</span>
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
                        <span>Genel Toplam</span>
                        <span id="grand-total">0,00 ₺</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <label class="label" for="notes">Teklif Notu</label>
            <textarea id="notes" name="notes" rows="2" class="input"><?php echo e(old('notes', $quote->notes)); ?></textarea>

            <div class="mt-5 flex items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn-primary">Değişiklikleri Kaydet</button>
                <a href="<?php echo e(route('teklifler.show', $quote)); ?>" class="btn-secondary">İptal</a>
            </div>
        </div>
    </form>

    <script>
        window.invoiceProducts = <?php echo json_encode($products, 15, 512) ?>;
        window.invoiceFormConfig = { priceKey: 'sale_price', stockCheck: false };
        window.invoicePrefill = <?php echo json_encode($prefill, 15, 512) ?>;
    </script>

    <?php $__env->startPush('scripts'); ?>
        <script src="<?php echo e(asset('js/invoice-form.js')); ?>"></script>
    <?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/quotes/edit.blade.php ENDPATH**/ ?>