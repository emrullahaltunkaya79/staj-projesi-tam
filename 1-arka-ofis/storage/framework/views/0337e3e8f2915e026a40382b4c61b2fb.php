<?php $__env->startSection('title', 'E-ticaret Siparişleri'); ?>

<?php $__env->startSection('content'); ?>

    <?php
        $durumAdlari = ['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'preparing' => 'Hazırlanıyor', 'shipped' => 'Kargoda', 'delivered' => 'Teslim', 'cancelled' => 'İptal'];
        $chips = [];
        if (! empty(request('status'))) $chips[] = ['label' => 'Durum', 'value' => $durumAdlari[request('status')] ?? request('status'), 'url' => request()->fullUrlWithQuery(['status' => null, 'page' => null])];
    ?>
    <?php echo $__env->make('partials.chips', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php
        $counts = [
            'pending' => $orders->getCollection()->where('status', 'pending')->count(),
            'paid' => $orders->getCollection()->where('status', 'paid')->count(),
            'shipping' => $orders->getCollection()->whereIn('status', ['preparing', 'shipped'])->count(),
            'revenue' => $orders->getCollection()->whereIn('status', ['paid', 'preparing', 'shipped', 'delivered'])->sum('total'),
        ];
    ?>

    <div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="card p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Bekleyen</div>
            <div class="mt-1 text-2xl font-bold text-amber-600"><?php echo e($counts['pending']); ?></div>
        </div>
        <div class="card p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Ödenen</div>
            <div class="mt-1 text-2xl font-bold text-emerald-600"><?php echo e($counts['paid']); ?></div>
        </div>
        <div class="card p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Kargoda</div>
            <div class="mt-1 text-2xl font-bold text-indigo-600"><?php echo e($counts['shipping']); ?></div>
        </div>
        <div class="card p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Bu Sayfa Ciro</div>
            <div class="mt-1 text-2xl font-bold text-slate-800"><?php echo e(number_format($counts['revenue'], 2, ',', '.')); ?> ₺</div>
        </div>
    </div>

    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <select name="status" class="input sm:w-48">
            <option value="">Tüm durumlar</option>
            <?php $__currentLoopData = ['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'preparing' => 'Hazırlanıyor', 'shipped' => 'Kargolandı', 'delivered' => 'Teslim Edildi', 'cancelled' => 'İptal']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($value); ?>" <?php if(($filters['status'] ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button type="submit" class="btn-secondary !h-10 sm:w-28">Filtrele</button>
    </form>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Sipariş No</th>
                    <th>Müşteri</th>
                    <th>Dış Referans</th>
                    <th class="!text-right">Tutar</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                        <td>
                            <a href="<?php echo e(route('siparisler.show', $order)); ?>" class="font-semibold text-indigo-600 hover:text-indigo-700"><?php echo e($order->order_no); ?></a>
                            <?php if($order->external_ref): ?>
                                <div class="text-xs text-slate-400"><?php echo e($order->external_ref); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($order->customer_name ?: ($order->contact->name ?? '—')); ?></td>
                        <td class="text-xs"><?php echo e($order->external_ref ?: '—'); ?></td>
                        <td class="text-right font-bold"><?php echo e(number_format((float) $order->total, 2, ',', '.')); ?> ₺</td>
                        <td><span class="<?php echo e($order->statusBadgeClass()); ?>"><?php echo e($order->statusLabel()); ?></span></td>
                        <td class="text-xs"><?php echo e($order->created_at->format('d.m.Y H:i')); ?></td>
                        <td class="text-right tabular-nums">
                            <div class="flex items-center justify-end gap-2">
                                <?php if(in_array($order->status, ['paid', 'preparing', 'shipped', 'delivered'])): ?>
                                    <a href="<?php echo e(route('siparisler.etiket', $order)); ?>" title="Kargo etiketini indir" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Etiket</a>
                                <?php endif; ?>
                                <a href="<?php echo e(route('siparisler.show', $order)); ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Detay</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php echo $__env->make('partials.empty', ['colspan' => 7, 'icon' => 'cart', 'title' => 'Henüz sipariş yok', 'desc' => "Siparişler e-ticaret API'sinden otomatik gelir (POST /api/v1/orders) — buradan ödeme, kargo ve iptal yönetilir."], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <?php echo e($orders->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/orders/index.blade.php ENDPATH**/ ?>