<?php $__env->startSection('title', 'Giriş Yap'); ?>

<?php $__env->startSection('content'); ?>
    <h2 class="mb-6 text-xl font-bold text-slate-800">Giriş Yap</h2>

    <form method="POST" action="<?php echo e(route('login.store')); ?>" class="space-y-4">
        <?php echo csrf_field(); ?>

        <div>
            <label class="label" for="email">E-posta</label>
            <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus
                   class="input" placeholder="ornek@sirket.com">
        </div>

        <div>
            <label class="label" for="password">Şifre</label>
            <input id="password" type="password" name="password" required class="input" placeholder="••••••••">
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            Beni hatırla
        </label>

        <div class="text-right">
            <a href="<?php echo e(route('password.request')); ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Şifremi unuttum?</a>
        </div>

        <button type="submit" class="btn-primary w-full">Giriş Yap</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Hesabınız yok mu?
        <a href="<?php echo e(route('register')); ?>" class="font-semibold text-indigo-600 hover:text-indigo-700">Kayıt olun</a>
    </p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/auth/login.blade.php ENDPATH**/ ?>