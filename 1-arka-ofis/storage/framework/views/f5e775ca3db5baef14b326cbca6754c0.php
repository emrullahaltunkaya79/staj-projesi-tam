<?php $__env->startSection('title', 'Sipariş '.$order->order_no); ?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(route('siparisler.index')); ?>" class="btn-secondary">← Siparişler</a>

    <?php if(in_array($order->status, ['paid', 'preparing', 'shipped', 'delivered'])): ?>
        <a href="<?php echo e(route('siparisler.etiket', $order)); ?>" class="btn-secondary" title="Kargo kutu etiketini PDF olarak indir">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            Kargo Etiketi
        </a>
    <?php endif; ?>

    <?php if($order->status === 'pending'): ?>
        <form method="POST" action="<?php echo e(route('siparisler.cspr.start', $order)); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                CSPR ile Öde
            </button>
        </form>
        <form method="POST" action="<?php echo e(route('siparisler.odendi', $order)); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Ödendi — Faturalaştır
            </button>
        </form>
        <form method="POST" action="<?php echo e(route('siparisler.iptal', $order)); ?>"
              onsubmit="return confirm('Siparişi iptal etmek istediğinize emin misiniz? Rezerve stok serbest bırakılacak.');">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn-danger">İptal</button>
        </form>
    <?php endif; ?>

    <?php if(in_array($order->status, ['paid', 'preparing']) ): ?>
        <form method="POST" action="<?php echo e(route('siparisler.kargo', $order)); ?>" class="flex flex-col items-end gap-2 sm:flex-row sm:items-end">
            <?php echo csrf_field(); ?>
            <div>
                <label class="label !mb-1 text-xs">Kargo Firması</label>
                <select name="carrier" required class="input !h-10 w-36">
                    <?php $__currentLoopData = ['Yurtiçi Kargo', 'Aras Kargo', 'MNG Kargo', 'PTT Kargo', 'Sürat Kargo', 'Diğer']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $firm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($firm); ?>"><?php echo e($firm); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="label !mb-1 text-xs">Takip No</label>
                <input type="text" name="tracking_no" required class="input !h-10 w-36" placeholder="Takip No">
            </div>
            <button type="submit" class="btn-secondary !h-10">Kargola</button>
        </form>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    
    <?php
        $adimlar = ['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'preparing' => 'Hazırlanıyor', 'shipped' => 'Kargoda', 'delivered' => 'Teslim'];
        $adimKeys = array_keys($adimlar);
        $aktifIndex = array_search($order->status, $adimKeys);
    ?>
    <div class="card mb-6 p-5">
        <div class="mb-4 flex items-center justify-between">
            <span class="text-sm font-bold text-slate-800 dark:text-slate-100">Sipariş Akışı</span>
            <span class="<?php echo e($order->statusBadgeClass()); ?>"><?php echo e($order->statusLabel()); ?></span>
        </div>
        <?php if($order->status === 'cancelled'): ?>
            <div class="flex items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-5 text-sm font-bold text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                Bu sipariş iptal edildi<?php echo e($order->tracking_no ? '' : ' — rezerve stok serbest bırakıldı'); ?>.
            </div>
        <?php else: ?>
            <div class="flex items-start">
                <?php $__currentLoopData = $adimlar; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $i = $loop->index;
                        $tamamlanan = $i < $aktifIndex;
                        $aktif = $i === $aktifIndex;
                    ?>
                    <?php if(! $loop->first): ?>
                        <div class="mt-[18px] h-0.5 flex-1 <?php echo e($tamamlanan || $aktif ? 'bg-indigo-500' : 'bg-slate-200 dark:bg-slate-700'); ?>"></div>
                    <?php endif; ?>
                    <div class="flex w-20 shrink-0 flex-col items-center gap-2 sm:w-24">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full border-2 transition
                            <?php echo e($aktif ? 'border-indigo-600 bg-indigo-600 text-white shadow-lg shadow-indigo-500/30'
                                : ($tamamlanan ? 'border-indigo-500 bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-300'
                                : 'border-slate-300 bg-white text-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-600')); ?>">
                            <?php if($tamamlanan): ?>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <?php elseif($aktif && $key === 'pending'): ?>
                                <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-white"></span>
                            <?php else: ?>
                                <span class="text-xs font-black"><?php echo e($i + 1); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="text-center text-[11px] font-semibold leading-tight <?php echo e($aktif ? 'text-indigo-600 dark:text-indigo-300' : ($tamamlanan ? 'text-slate-600 dark:text-slate-300' : 'text-slate-400')); ?>"><?php echo e($label); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="card-header">Sipariş Kalemleri</div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>Ürün</th>
                        <th class="!text-right">Miktar</th>
                        <th class="!text-right">Birim Fiyat</th>
                        <th class="!text-right">Tutar</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="font-medium text-slate-700 dark:text-slate-200"><?php echo e($item['product_name']); ?></td>
                            <td class="text-right"><?php echo e($item['quantity']); ?></td>
                            <td class="text-right"><?php echo e(number_format($item['unit_price'], 2, ',', '.')); ?> ₺</td>
                            <td class="text-right font-bold"><?php echo e(number_format($item['line_total'], 2, ',', '.')); ?> ₺</td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-700 p-5">
                <div class="flex justify-end">
                    <div class="w-full max-w-xs space-y-1.5">
                        <div class="flex justify-between text-sm"><span class="text-slate-500">Ara Toplam</span><span><?php echo e(number_format((float) $order->subtotal, 2, ',', '.')); ?> ₺</span></div>
                        <div class="flex justify-between text-sm"><span class="text-slate-500">KDV (%<?php echo e($order->tax_rate); ?>)</span><span><?php echo e(number_format((float) $order->tax_amount, 2, ',', '.')); ?> ₺</span></div>
                        <div class="flex justify-between rounded-lg bg-slate-900 px-3 py-2 text-base font-bold text-white dark:bg-indigo-600"><span>Genel Toplam</span><span><?php echo e(number_format((float) $order->total, 2, ',', '.')); ?> ₺</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Durum</span>
                <span class="<?php echo e($order->statusBadgeClass()); ?>"><?php echo e($order->statusLabel()); ?></span>
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Sipariş No</dt><dd class="font-semibold"><?php echo e($order->order_no); ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Müşteri</dt><dd><?php echo e($order->customer_name ?: ($order->contact->name ?? '—')); ?></dd></div>
                <?php if($order->customer_email): ?><div class="flex justify-between"><dt class="text-slate-500">E-posta</dt><dd class="text-xs"><?php echo e($order->customer_email); ?></dd></div><?php endif; ?>
                <?php if($order->invoice): ?>
                    <div class="flex justify-between"><dt class="text-slate-500">Fatura</dt><dd><a href="<?php echo e(route('faturalar.show', $order->invoice_id)); ?>" class="font-semibold text-indigo-600"><?php echo e($order->invoice->invoice_no); ?></a></dd></div>
                <?php endif; ?>
                <?php if($order->tracking_no): ?>
                    <div class="flex justify-between"><dt class="text-slate-500">Takip No</dt><dd class="font-mono text-xs"><?php echo e($order->tracking_no); ?></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Kargo</dt><dd><?php echo e($order->carrier); ?></dd></div>
                    <?php if($order->trackingUrl()): ?>
                        <div class="pt-1">
                            <a href="<?php echo e($order->trackingUrl()); ?>" target="_blank" rel="noopener"
                               class="flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                Kargoyu Takip Et →
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </dl>
            <?php if($order->shipping_address): ?>
                <div class="mt-3 rounded-lg bg-slate-50 dark:bg-slate-700 p-3 text-xs text-slate-600 dark:text-slate-300">
                    <span class="font-semibold">Teslim Adresi:</span><br><?php echo e($order->shipping_address); ?>

                </div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/orders/show.blade.php ENDPATH**/ ?>