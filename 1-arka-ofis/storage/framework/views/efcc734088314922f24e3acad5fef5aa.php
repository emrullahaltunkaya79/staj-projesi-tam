<?php $__env->startSection('title', 'Özel Fiyat Listesi'); ?>

<?php $__env->startSection('actions'); ?>
    <a href="#yeni-fiyat" class="btn-primary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Özel Fiyat
    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    ?>

    
    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <select name="contact_id" class="input sm:w-56">
            <option value="">Tüm cariler</option>
            <?php $__currentLoopData = $contacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contact): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($contact->id); ?>" <?php if($filters['contact_id'] == $contact->id): echo 'selected'; endif; ?>><?php echo e($contact->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button type="submit" class="btn-secondary !h-10 sm:w-28">Filtrele</button>
    </form>

    
    <div class="card mb-4 p-5" id="yeni-fiyat">
        <h3 class="mb-3 font-bold text-slate-800">Özel Fiyat Ekle</h3>
        <form method="POST" action="<?php echo e(route('fiyatlar.store')); ?>" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <?php echo csrf_field(); ?>
            <div class="flex-1">
                <label class="label" for="contact_id">Cari</label>
                <select id="contact_id" name="contact_id" class="input" required>
                    <option value="">— Cari seçin —</option>
                    <?php $__currentLoopData = $contacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contact): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($contact->id); ?>"><?php echo e($contact->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="flex-1">
                <label class="label" for="product_id">Ürün</label>
                <select id="product_id" name="product_id" class="input" required>
                    <option value="">— Ürün seçin —</option>
                    <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($product->id); ?>"><?php echo e($product->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="sm:w-32">
                <label class="label" for="price">Fiyat (₺)</label>
                <input type="number" id="price" name="price" min="0.01" step="0.01" required class="input">
            </div>
            <button type="submit" class="btn-primary !h-10">Kaydet</button>
        </form>
        <p class="mt-2 text-xs text-slate-400">Aynı cari-ürün çifti için varolan kayıt güncellenir. Fatura/teklif formunda ürün seçilince bu fiyat otomatik uygulanır.</p>
    </div>

    
    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Cari</th>
                    <th>Ürün</th>
                    <th class="!text-right">Standart Satış</th>
                    <th class="!text-right">Özel Fiyat</th>
                    <th class="!text-right">Fark</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $prices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $price): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $diff = (float) $price->price - (float) $price->product->sale_price;
                    ?>
                    <tr class="hover:bg-slate-50">
                        <td class="font-medium text-slate-700"><?php echo e($price->contact->name); ?></td>
                        <td><?php echo e($price->product->name); ?></td>
                        <td class="text-right text-slate-400"><?php echo e($fmt($price->product->sale_price)); ?></td>
                        <td class="text-right font-bold <?php echo e($diff < 0 ? 'text-rose-600' : 'text-emerald-600'); ?>"><?php echo e($fmt($price->price)); ?></td>
                        <td class="text-right text-xs <?php echo e($diff < 0 ? 'text-rose-500' : 'text-emerald-500'); ?>">
                            <?php echo e($diff > 0 ? '+' : ''); ?><?php echo e($fmt($diff)); ?>

                        </td>
                        <td>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $price)): ?>
                                <div class="flex justify-end">
                                    <form method="POST" action="<?php echo e(route('fiyatlar.destroy', $price)); ?>"
                                          onsubmit="return confirm('Bu özel fiyatı silmek istediğinize emin misiniz?');">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Sil</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php echo $__env->make('partials.empty', ['colspan' => 6, 'icon' => 'tag', 'title' => 'Henüz özel fiyat yok', 'desc' => 'Yukarıdaki formdan cari bazlı özel fiyat tanımlayın — o cariye satışlarda otomatik uygulanır.'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($prices->hasPages()): ?>
            <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-700">
                <?php echo e($prices->links()); ?>

            </div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/prices/index.blade.php ENDPATH**/ ?>