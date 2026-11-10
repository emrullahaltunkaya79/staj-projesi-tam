<?php $__env->startSection('title', 'Etkinlik Günlüğü'); ?>

<?php $__env->startSection('content'); ?>
    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <select name="action" class="input sm:w-44">
            <option value="">Tüm işlemler</option>
            <?php $__currentLoopData = ['created' => 'Oluşturma', 'updated' => 'Güncelleme', 'deleted' => 'Silme', 'restored' => 'Geri Yükleme']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($value); ?>" <?php if($filters['action'] === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select name="model" class="input sm:w-44">
            <option value="">Tüm kayıt türleri</option>
            <?php $__currentLoopData = ['Contact' => 'Cariler', 'Product' => 'Ürünler', 'Invoice' => 'Faturalar', 'Payment' => 'Tahsilat/Ödeme', 'Expense' => 'Giderler', 'Quote' => 'Teklifler', 'RecurringInvoice' => 'Tekrarlayan', 'User' => 'Kullanıcılar', 'Company' => 'Şirket']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($value); ?>" <?php if($filters['model'] === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button type="submit" class="btn-secondary !h-10 sm:w-28">Filtrele</button>
    </form>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Kullanıcı</th>
                    <th>İşlem</th>
                    <th>Kayıt</th>
                    <th>Değişiklik</th>
                    <th>IP</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap"><?php echo e($log->created_at->format('d.m.Y H:i:s')); ?></td>
                        <td>
                            <span class="font-medium text-slate-700"><?php echo e($log->user?->name ?? 'Sistem'); ?></span>
                        </td>
                        <td>
                            <span class="<?php echo e(match($log->action) {
                                'created' => 'badge-green',
                                'updated' => 'badge-indigo',
                                'deleted' => 'badge-red',
                                'restored' => 'badge-amber',
                                default => 'badge-slate',
                            }); ?>"><?php echo e($log->actionLabel()); ?></span>
                        </td>
                        <td class="text-slate-600"><?php echo e($log->description); ?></td>
                        <td class="max-w-xs">
                            <?php if($log->changes): ?>
                                <span class="text-[11px] text-slate-500" title="<?php echo e(json_encode($log->changes, JSON_UNESCAPED_UNICODE)); ?>">
                                    <?php echo e(collect($log->changes)->keys()->implode(', ')); ?>

                                </span>
                            <?php else: ?>
                                <span class="text-slate-300">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="font-mono text-xs text-slate-400"><?php echo e($log->ip); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="py-10 text-center text-slate-400">Henüz denetim kaydı yok.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <?php echo e($logs->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/activity/index.blade.php ENDPATH**/ ?>