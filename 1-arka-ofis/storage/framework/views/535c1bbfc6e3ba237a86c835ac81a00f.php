<?php $__env->startSection('title', 'Cariler'); ?>

<?php $__env->startSection('actions'); ?>
    <button type="button" class="btn-secondary" data-modal-url="<?php echo e(route('cariler.import.form')); ?>" data-modal-title="Cariler — İçe Aktar">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 14l5-5 5 5M12 9V3"/>
        </svg>
        İçe Aktar
    </button>
    <a href="<?php echo e(route('cariler.export')); ?>" class="btn-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
        </svg>
        Excel
    </a>
    <button type="button" class="btn-primary" data-modal-url="<?php echo e(route('cariler.create')); ?>" data-modal-title="Yeni Cari">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Cari
    </button>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    ?>

    
    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <input type="text" name="search" value="<?php echo e($filters['search']); ?>" class="input sm:max-w-xs"
               placeholder="Cari adı, telefon veya e-posta ara...">
        <select name="type" class="input sm:w-44">
            <option value="">Tümü (Müşteri + Tedarikçi)</option>
            <option value="musteri" <?php if($filters['type'] === 'musteri'): echo 'selected'; endif; ?>>Müşteriler</option>
            <option value="tedarikci" <?php if($filters['type'] === 'tedarikci'): echo 'selected'; endif; ?>>Tedarikçiler</option>
        </select>
        <button type="submit" class="btn-secondary !h-10 sm:w-28">Filtrele</button>
    </form>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Cari Adı</th>
                    <th>Tip</th>
                    <th>Telefon</th>
                    <th>E-posta</th>
                    <th class="!text-right">Bakiye</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $contacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contact): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50">
                        <td>
                            <a href="<?php echo e(route('cariler.show', $contact)); ?>" data-modal-url="<?php echo e(route('cariler.show', $contact)); ?>" data-modal-title="<?php echo e($contact->name); ?>" data-modal-view="1" class="font-medium text-slate-700 hover:text-indigo-600">
                                <?php echo e($contact->name); ?>

                            </a>
                            <?php if($contact->tax_number): ?>
                                <div class="text-xs text-slate-400">VKN: <?php echo e($contact->tax_number); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="<?php echo e($contact->isCustomer() ? 'badge-indigo' : 'badge-slate'); ?>"><?php echo e($contact->typeLabel()); ?></span>
                        </td>
                        <td><?php echo e($contact->phone ?: '—'); ?></td>
                        <td><?php echo e($contact->email ?: '—'); ?></td>
                        <td class="text-right font-semibold tabular-nums <?php echo e($contact->balance > 0 ? 'text-emerald-600' : ($contact->balance < 0 ? 'text-red-600' : 'text-slate-500')); ?>">
                            <?php echo e($contact->balance > 0 ? '+' : ''); ?><?php echo e($fmt($contact->balance)); ?>

                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <a href="<?php echo e(route('cariler.show', $contact)); ?>"
                                   data-modal-url="<?php echo e(route('cariler.show', $contact)); ?>" data-modal-title="<?php echo e($contact->name); ?>" data-modal-view="1"
                                   class="btn-secondary !px-2.5 !py-1.5 text-xs"
                                   title="Cari detay ve ekstre kartını aç">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                    Detay / Ekstre
                                </a>
                                <a href="<?php echo e(route('cariler.edit', $contact)); ?>" data-modal-url="<?php echo e(route('cariler.edit', $contact)); ?>" data-modal-title="Cariyi Düzenle — <?php echo e($contact->name); ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Düzenle</a>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $contact)): ?>
                                    <form method="POST" action="<?php echo e(route('cariler.destroy', $contact)); ?>"
                                          onsubmit="return confirm('Bu cariyi silmek istediğinize emin misiniz?');">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Sil</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php echo $__env->make('partials.empty', ['colspan' => 6, 'icon' => 'users', 'title' => 'Kayıtlı cari yok', 'desc' => 'Müşteri ve tedarikçilerinizi ekleyerek başlayın; ekstre ve faturalar cari üzerinden işler.', 'action' => 'Yeni Cari Ekle', 'actionUrl' => 'http://localhost/cariler/create', 'actionTitle' => 'Yeni Cari'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <?php echo e($contacts->links()); ?>

        </div>
    </div>

    <p class="mt-3 text-xs text-slate-400">
        Bakiye pozitifse cari size borçludur (alacak), negatifse siz cariye borçlusunuz. Bakiye, kesilen faturalarla otomatik güncellenir.
    </p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/contacts/index.blade.php ENDPATH**/ ?>