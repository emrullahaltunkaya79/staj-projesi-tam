<?php $__env->startSection('title', 'Yeni Cari'); ?>

<?php $__env->startSection('content'); ?>
    <form method="POST" action="<?php echo e(route('cariler.store')); ?>">
        <?php echo csrf_field(); ?>
        <?php echo $__env->make('contacts._form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/contacts/create.blade.php ENDPATH**/ ?>