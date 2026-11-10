<?php $__env->startSection('title', 'Geri Dönüşüm Kutusu'); ?>

<?php $__env->startSection('content'); ?>
    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <select name="type" class="input sm:w-44" onchange="this.form.submit()">
            <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($key); ?>" <?php if($type === $key): echo 'selected'; endif; ?>><?php echo e($meta['label']); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <span class="text-xs text-slate-400">Soft delete ile silinen kayıtlar buradan geri yüklenebilir.</span>
    </form>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Kayıt</th>
                    <th>Silme Tarihi</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="font-medium text-slate-700"><?php echo e($item->getAuditName()); ?></td>
                        <td><?php echo e($item->deleted_at->format('d.m.Y H:i')); ?></td>
                        <td>
                            <div class="flex justify-end">
                                <form method="POST" action="<?php echo e(route('geri-donusum.restore', ['type' => $type, 'id' => $item->id])); ?>">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn-secondary !px-3 !py-1.5 text-xs">Geri Yükle</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="3" class="py-10 text-center text-slate-400">Bu türde silinmiş kayıt yok. 👍</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <?php echo e($items->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/recycle/index.blade.php ENDPATH**/ ?>