<?php $__env->startSection('title', 'Şifre Değiştir'); ?>

<?php $__env->startSection('content'); ?>
    <form method="POST" action="<?php echo e(route('sifre-degistir.update')); ?>" class="max-w-xl">
        <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

        <div class="card p-6">
            <h2 class="mb-1 text-lg font-bold text-slate-800">Şifre Değiştir</h2>
            <p class="mb-6 text-sm text-slate-500">Şifreniz değiştiğinde diğer cihazlardaki oturumlarınız kapatılır.</p>

            <div class="space-y-5">
                <div>
                    <label class="label" for="current_password">Mevcut Şifre <span class="text-red-500">*</span></label>
                    <input type="password" id="current_password" name="current_password" required class="input">
                    <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="label" for="password">Yeni Şifre <span class="text-red-500">*</span></label>
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
                    <label class="label" for="password_confirmation">Yeni Şifre Tekrar <span class="text-red-500">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required class="input">
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn-primary">Şifreyi Güncelle</button>
            </div>
        </div>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/profile/password.blade.php ENDPATH**/ ?>