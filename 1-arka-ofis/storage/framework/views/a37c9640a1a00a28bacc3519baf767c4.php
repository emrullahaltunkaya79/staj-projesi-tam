<?php $__env->startSection('title', 'Yeni Kullanıcı'); ?>

<?php $__env->startSection('content'); ?>
    <form method="POST" action="<?php echo e(route('kullanicilar.store')); ?>" class="max-w-2xl">
        <?php echo csrf_field(); ?>

        <div class="card p-6">
            <h2 class="mb-1 text-lg font-bold text-slate-800">Şirkete Kullanıcı Ekle</h2>
            <p class="mb-6 text-sm text-slate-500">Yeni kullanıcı aynı şirketin verilerine erişir.</p>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="label" for="name">Ad Soyad <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo e(old('name')); ?>" required class="input" placeholder="Ayşe Demir">
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
                    <label class="label" for="email">E-posta <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>" required class="input" placeholder="ayse@sirket.com">
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
                    <label class="label" for="password">Şifre <span class="text-red-500">*</span></label>
                    <input type="password" id="password" name="password" required class="input" placeholder="En az 8 karakter">
                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="label" for="password_confirmation">Şifre Tekrar <span class="text-red-500">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required class="input">
                </div>

                <div class="sm:col-span-2">
                    <span class="label">Rol <span class="text-red-500">*</span></span>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:text-indigo-700">
                            <input type="radio" name="role" value="admin" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500" <?php if(old('role', 'kullanici') === 'admin'): echo 'checked'; endif; ?>>
                            Yönetici
                        </label>
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:text-indigo-700">
                            <input type="radio" name="role" value="kullanici" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500" <?php if(old('role', 'kullanici') === 'kullanici'): echo 'checked'; endif; ?>>
                            Kullanıcı
                        </label>
                    </div>
                    <?php $__errorArgs = ['role'];
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
                <button type="submit" class="btn-primary">Kullanıcıyı Oluştur</button>
                <a href="<?php echo e(route('kullanicilar.index')); ?>" class="btn-secondary">İptal</a>
            </div>
        </div>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/users/create.blade.php ENDPATH**/ ?>