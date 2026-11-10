<div class="card p-6">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <label class="label" for="name">Cari Adı / Ünvanı <span class="text-red-500">*</span></label>
            <input type="text" id="name" name="name" value="<?php echo e(old('name', $contact->name)); ?>" required
                   class="input" placeholder="Yılmaz Market veya Demir Tedarik A.Ş.">
            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
            <label class="label" for="type">Cari Tipi <span class="text-red-500">*</span></label>
            <select id="type" name="type" class="input" required>
                <option value="musteri" <?php if(old('type', $contact->type) === 'musteri'): echo 'selected'; endif; ?>>Müşteri</option>
                <option value="tedarikci" <?php if(old('type', $contact->type) === 'tedarikci'): echo 'selected'; endif; ?>>Tedarikçi</option>
            </select>
            <?php $__errorArgs = ['type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
            <label class="label" for="phone">Telefon</label>
            <input type="tel" id="phone" name="phone" value="<?php echo e(old('phone', $contact->phone)); ?>"
                   class="input" placeholder="0532 000 00 00">
            <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
            <label class="label" for="email">E-posta</label>
            <input type="email" id="email" name="email" value="<?php echo e(old('email', $contact->email)); ?>"
                   class="input" placeholder="cari@ornek.com">
            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
            <label class="label" for="tax_number">Vergi / TC No</label>
            <input type="text" id="tax_number" name="tax_number" value="<?php echo e(old('tax_number', $contact->tax_number)); ?>"
                   class="input" placeholder="1234567890">
            <?php $__errorArgs = ['tax_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="sm:col-span-2">
            <label class="label" for="address">Adres</label>
            <textarea id="address" name="address" rows="3" class="input"
                      placeholder="Mahalle, cadde, no, ilçe / il"><?php echo e(old('address', $contact->address)); ?></textarea>
            <?php $__errorArgs = ['address'];
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
        <a href="<?php echo e(route('cariler.index')); ?>" class="btn-secondary">İptal</a>
    </div>
</div>
<?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/contacts/_form.blade.php ENDPATH**/ ?>