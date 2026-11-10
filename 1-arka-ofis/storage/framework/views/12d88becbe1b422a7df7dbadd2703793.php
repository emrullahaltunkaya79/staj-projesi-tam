<?php $__env->startSection('title', 'Ürün Düzenle: '.$product->name); ?>

<?php $__env->startSection('content'); ?>
    <form method="POST" action="<?php echo e(route('urunler.update', $product)); ?>">
        <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
        <?php echo $__env->make('products._form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/products/edit.blade.php ENDPATH**/ ?>