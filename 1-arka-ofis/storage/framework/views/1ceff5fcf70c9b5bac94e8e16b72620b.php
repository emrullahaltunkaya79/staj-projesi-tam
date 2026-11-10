
<?php if(! empty($chips)): ?>
    <div class="mb-3 flex flex-wrap items-center gap-2">
        <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Filtreler:</span>
        <?php $__currentLoopData = $chips; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e($chip['url']); ?>"
               title="Bu filtreyi kaldır"
               class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 transition hover:border-indigo-400 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-950/70">
                <?php echo e($chip['label']); ?>: <?php echo e($chip['value']); ?>

                <span class="font-black text-indigo-400">×</span>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(request()->url()); ?>" class="text-xs font-semibold text-slate-400 underline-offset-2 transition hover:text-red-500 hover:underline">Temizle</a>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/partials/chips.blade.php ENDPATH**/ ?>