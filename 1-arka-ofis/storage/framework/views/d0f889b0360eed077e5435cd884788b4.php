<?php $__env->startSection('title', 'Giderler'); ?>

<?php $__env->startSection('actions'); ?>
    <button type="button" class="btn-primary" data-modal-url="<?php echo e(route('giderler.create')); ?>" data-modal-title="Gider Kaydı">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Gider
    </button>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php
        $chips = [];
        if (! empty(request('category'))) $chips[] = ['label' => 'Kategori', 'value' => request('category'), 'url' => request()->fullUrlWithQuery(['category' => null, 'page' => null])];
    ?>
    <?php echo $__env->make('partials.chips', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    ?>

    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <select name="category" class="input sm:w-52">
            <option value="">Tüm kategoriler</option>
            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($category); ?>" <?php if($filters['category'] === $category): echo 'selected'; endif; ?>><?php echo e($category); ?></option>
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
                    <th>Kategori</th>
                    <th>Açıklama</th>
                    <th>Ödeme</th>
                    <th class="!text-right">Tutar</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $expenses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $expense): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap"><?php echo e($expense->expense_date->format('d.m.Y')); ?></td>
                        <td>
                            <span class="badge-slate"><?php echo e($expense->category); ?></span>
                        </td>
                        <td class="max-w-xs truncate text-slate-500" title="<?php echo e($expense->description); ?>"><?php echo e($expense->description ?: '—'); ?></td>
                        <td>
                            <span class="<?php echo e($expense->isCash() ? 'badge-green' : 'badge-indigo'); ?>"><?php echo e($expense->typeLabel()); ?></span>
                        </td>
                        <td class="text-right font-semibold tabular-nums text-red-600">−<?php echo e($fmt($expense->amount)); ?></td>
                        <td>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $expense)): ?>
                                <div class="flex justify-end">
                                    <form method="POST" action="<?php echo e(route('giderler.destroy', $expense)); ?>"
                                          onsubmit="return confirm('Bu gider kaydını silmek istediğinize emin misiniz?');">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Sil</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php echo $__env->make('partials.empty', ['colspan' => 6, 'icon' => 'trend', 'title' => 'Henüz gider yok', 'desc' => 'Kira, fatura, maaş gibi işletme giderlerini kaydedin; kasa ve raporlara yansır.', 'action' => 'Gider Kaydı Aç', 'actionUrl' => 'http://localhost/giderler/create', 'actionTitle' => 'Gider Kaydı'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <?php echo e($expenses->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/expenses/index.blade.php ENDPATH**/ ?>