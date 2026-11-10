<?php $__env->startSection('title', 'Teklifler'); ?>

<?php $__env->startSection('actions'); ?>
    <button type="button" class="btn-primary" data-modal-url="<?php echo e(route('teklifler.create')); ?>" data-modal-title="Yeni Teklif" data-modal-wide="1">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Teklif
    </button>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    ?>

    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <select name="status" class="input sm:w-48">
            <option value="">Tüm durumlar</option>
            <?php $__currentLoopData = ['acik' => 'Açık', 'onaylandi' => 'Onaylandı', 'reddedildi' => 'Reddedildi', 'faturalandi' => 'Faturalandı']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($value); ?>" <?php if($filters['status'] === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button type="submit" class="btn-secondary !h-10 sm:w-28">Filtrele</button>
    </form>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Teklif No</th>
                    <th>Cari</th>
                    <th>Tarih</th>
                    <th>Geçerlilik</th>
                    <th class="!text-right">Tutar</th>
                    <th>Durum</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $quotes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quote): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50">
                        <td>
                            <a href="<?php echo e(route('teklifler.show', $quote)); ?>" data-modal-url="<?php echo e(route('teklifler.show', $quote)); ?>" data-modal-title="Teklif <?php echo e($quote->quote_no); ?>" data-modal-view="1" data-modal-wide="1" class="font-semibold text-indigo-600 hover:text-indigo-700"><?php echo e($quote->quote_no); ?></a>
                        </td>
                        <td><?php echo e($quote->contact->name); ?></td>
                        <td><?php echo e($quote->issue_date->format('d.m.Y')); ?></td>
                        <td><?php echo e($quote->valid_until?->format('d.m.Y') ?? '—'); ?></td>
                        <td class="text-right font-semibold tabular-nums"><?php echo e($fmt($quote->total)); ?></td>
                        <td><span class="<?php echo e($quote->statusBadgeClass()); ?>"><?php echo e($quote->statusLabel()); ?></span></td>
                        <td class="text-right tabular-nums">
                            <div class="flex items-center justify-end gap-2">
                                <a href="<?php echo e(route('teklifler.show', $quote)); ?>" data-modal-url="<?php echo e(route('teklifler.show', $quote)); ?>" data-modal-title="Teklif <?php echo e($quote->quote_no); ?>" data-modal-view="1" data-modal-wide="1" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Görüntüle</a>
                                <?php if($quote->status === 'acik'): ?>
                                    <a href="<?php echo e(route('teklifler.edit', $quote)); ?>" data-modal-url="<?php echo e(route('teklifler.edit', $quote)); ?>" data-modal-title="Teklifi Düzenle — <?php echo e($quote->quote_no); ?>" data-modal-wide="1" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Düzenle</a>
                                <?php endif; ?>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $quote)): ?>
                                    <form method="POST" action="<?php echo e(route('teklifler.destroy', $quote)); ?>"
                                          onsubmit="return confirm('Bu teklifi silmek istediğinize emin misiniz? (Geri dönüşüm kutusuna taşınır)');">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Sil</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php echo $__env->make('partials.empty', ['colspan' => 7, 'icon' => 'quote', 'title' => 'Henüz teklif yok', 'desc' => 'İlk teklifinizi hazırlayın; onaylanınca tek tıkla faturaya dönüşür.', 'action' => 'Yeni Teklif Hazırla', 'actionUrl' => 'http://localhost/teklifler/create', 'actionTitle' => 'Yeni Teklif', 'wide' => 1], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <?php echo e($quotes->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/quotes/index.blade.php ENDPATH**/ ?>