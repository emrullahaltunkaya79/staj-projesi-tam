<?php $__env->startSection('title', 'Sistem Sağlığı'); ?>

<?php $__env->startSection('content'); ?>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Kuyrukta Bekleyen İş</div>
            <div class="mt-1 text-2xl font-bold <?php echo e($queuePending > 100 ? 'text-amber-600' : 'text-emerald-600'); ?>"><?php echo e($queuePending); ?></div>
            <div class="mt-1 text-xs text-slate-400">queue:work çalışıyorsa 0'a yakın olmalı</div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Veritabanı Boyutu</div>
            <div class="mt-1 text-2xl font-bold text-slate-800 dark:text-slate-100"><?php echo e($dbSize); ?></div>
            <div class="mt-1 text-xs text-slate-400">Sürücü: <?php echo e($dbDriver); ?></div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Son CSPR Kontrolü</div>
            <div class="mt-1 text-sm font-bold text-slate-800 dark:text-slate-100"><?php echo e($lastCasperCheck?->format('d.m.Y H:i') ?? '—'); ?></div>
            <div class="mt-1 text-xs text-slate-400">Scheduler: casper:odemeler-kontrol (2 dk)</div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Son Yedek</div>
            <div class="mt-1 text-sm font-bold text-slate-800 dark:text-slate-100"><?php echo e($lastBackup ?? 'Henüz yok'); ?></div>
            <div class="mt-1 text-xs text-slate-400">Şirket Ayarları → Şimdi Yedekle</div>
        </div>
    </div>

    <div class="card mt-6 p-5">
        <div class="card-header border-b-0 p-0 pb-3">Ortam Bilgisi</div>
        <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
            <div>
                <div class="text-xs text-slate-400">PHP</div>
                <div class="font-semibold text-slate-700 dark:text-slate-200"><?php echo e($phpVersion); ?></div>
            </div>
            <div>
                <div class="text-xs text-slate-400">Laravel</div>
                <div class="font-semibold text-slate-700 dark:text-slate-200"><?php echo e($laravelVersion); ?></div>
            </div>
            <div>
                <div class="text-xs text-slate-400">Ortam</div>
                <div class="font-semibold text-slate-700 dark:text-slate-200"><?php echo e(app()->environment()); ?></div>
            </div>
            <div>
                <div class="text-xs text-slate-400">Debug</div>
                <div class="font-semibold <?php echo e(config('app.debug') ? 'text-amber-600' : 'text-emerald-600'); ?>"><?php echo e(config('app.debug') ? 'Açık' : 'Kapalı'); ?></div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/system/health.blade.php ENDPATH**/ ?>