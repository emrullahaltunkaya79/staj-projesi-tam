<?php $__env->startSection('title', 'Cari Düzenle: '.$contact->name); ?>

<?php $__env->startSection('content'); ?>
    <div class="mb-4 flex items-center justify-between rounded-lg border border-slate-200 bg-white px-5 py-3">
        <div class="text-sm text-slate-500">
            Güncel bakiye:
            <span class="font-bold <?php echo e($contact->balance > 0 ? 'text-emerald-600' : ($contact->balance < 0 ? 'text-red-600' : 'text-slate-600')); ?>">
                <?php echo e(number_format((float) $contact->balance, 2, ',', '.')); ?> ₺
            </span>
        </div>
        <span class="text-xs text-slate-400">Bakiye faturalardan otomatik güncellenir, bu ekrandan değiştirilemez.</span>
    </div>

    <form method="POST" action="<?php echo e(route('cariler.update', $contact)); ?>">
        <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
        <?php echo $__env->make('contacts._form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/contacts/edit.blade.php ENDPATH**/ ?>