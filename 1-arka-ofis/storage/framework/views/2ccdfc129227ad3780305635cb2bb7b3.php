<?php $__env->startSection('title', 'Fatura '.$invoice->invoice_no); ?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(route('faturalar.index')); ?>" class="btn-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Geri Dön
    </a>
    <?php if($invoice->isSale() && $invoice->refunds->sum('total') < $invoice->total - 0.001): ?>
        <a href="<?php echo e(route('faturalar.iade.form', $invoice)); ?>" class="btn-secondary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6M3 10l6-6"/>
            </svg>
            İade Faturası Kes
        </a>
    <?php endif; ?>
    <form method="POST" action="<?php echo e(route('faturalar.email', $invoice)); ?>"
          onsubmit="return confirm('Fatura PDF olarak carinin e-posta adresine gönderilsin mi?');">
        <?php echo csrf_field(); ?>
        <button type="submit" class="btn-secondary" title="Cariye e-posta ile gönder">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            E-posta ile Gönder
        </button>
    </form>
    <a href="<?php echo e(route('faturalar.pdf', $invoice)); ?>" class="btn-secondary" title="Faturayı PDF dosyası olarak indir">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
        </svg>
        PDF İndir
    </a>
    <button type="button" onclick="window.print()" class="btn-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zM9 7V5a2 2 0 012-2h2a2 2 0 012 2v4H9z"/>
        </svg>
        Yazdır / PDF Kaydet
    </button>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    ?>

    
    <?php if($invoice->refundOf): ?>
        <div class="mx-auto mb-4 max-w-4xl rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Bu fatura, <a href="<?php echo e(route('faturalar.show', $invoice->refundOf)); ?>" class="font-semibold underline"><?php echo e($invoice->refundOf->invoice_no); ?></a>
            numaralı satış faturasının tam iadesidir — stoklar geri yüklenmiş, cari bakiyesi düşürülmüştür.
        </div>
    <?php endif; ?>
    <?php if($invoice->refunds->isNotEmpty()): ?>
        <?php $__currentLoopData = $invoice->refunds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $refund): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="mx-auto mb-4 max-w-4xl rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                ⚠ Bu fatura iade edilmiştir:
                <a href="<?php echo e(route('faturalar.show', $refund)); ?>" class="underline"><?php echo e($refund->invoice_no); ?></a>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>

    
    <div class="mx-auto max-w-4xl rounded-xl border border-slate-200 bg-white p-8 shadow-sm print:max-w-none print:rounded-none print:border-0 print:p-0 print:shadow-none sm:p-10">

        
        <div class="flex flex-col gap-6 border-b-2 border-slate-800 pb-6 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-4">
                <?php if($invoice->company->logoUrl()): ?>
                    <img src="<?php echo e($invoice->company->logoUrl()); ?>" alt="<?php echo e($invoice->company->name); ?>" class="h-14 w-auto rounded-lg border border-slate-200 bg-white p-1">
                <?php else: ?>
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-sm font-black text-white">ÖM</div>
                <?php endif; ?>
                <div>
                    <div class="text-lg font-bold text-slate-900"><?php echo e($invoice->company->name); ?></div>
                    <div class="mt-1 text-xs leading-5 text-slate-500">
                        <?php if($invoice->company->address): ?> <?php echo e($invoice->company->address); ?><br> <?php endif; ?>
                        <?php if($invoice->company->phone): ?> Tel: <?php echo e($invoice->company->phone); ?> <?php endif; ?>
                        <?php if($invoice->company->tax_number): ?>
                            <br>Vergi No: <?php echo e($invoice->company->tax_number); ?>

                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="sm:text-right">
                <div class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600"><?php echo e($invoice->typeLabel()); ?></div>
                <div class="mt-1 text-2xl font-black tracking-wide text-slate-900"><?php echo e($invoice->invoice_no); ?></div>
                <div class="mt-1 text-sm text-slate-500">Tarih: <?php echo e($invoice->issue_date->format('d.m.Y')); ?></div>
            </div>
        </div>

        
        <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 px-5 py-4 print:bg-slate-50">
            <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Alıcı (Cari)</div>
            <div class="mt-1 grid grid-cols-1 gap-x-8 gap-y-1 text-sm sm:grid-cols-2">
                <div>
                    <span class="font-bold text-slate-800"><?php echo e($invoice->contact->name); ?></span>
                    <?php if($invoice->contact->tax_number): ?>
                        <span class="text-slate-500"> — VKN: <?php echo e($invoice->contact->tax_number); ?></span>
                    <?php endif; ?>
                </div>
                <div class="text-slate-500">
                    <?php if($invoice->contact->phone): ?> <?php echo e($invoice->contact->phone); ?> <?php endif; ?>
                    <?php if($invoice->contact->email): ?> · <?php echo e($invoice->contact->email); ?> <?php endif; ?>
                </div>
                <?php if($invoice->contact->address): ?>
                    <div class="text-slate-500 sm:col-span-2"><?php echo e($invoice->contact->address); ?></div>
                <?php endif; ?>
            </div>
        </div>

        
        <table class="mt-6 w-full text-left text-sm">
            <thead>
            <tr class="border-b-2 border-slate-800 text-[11px] font-bold uppercase tracking-wide text-slate-600">
                <th class="py-2.5 pr-2 w-8">#</th>
                <th class="py-2.5 pr-2">Ürün / Açıklama</th>
                <th class="py-2.5 pr-2 text-right">Miktar</th>
                <th class="py-2.5 pr-2 text-right">Birim Fiyat</th>
                <th class="py-2.5 text-right">Tutar</th>
            </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $invoice->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="border-b border-slate-100">
                    <td class="py-3 pr-2 text-slate-400"><?php echo e($i + 1); ?></td>
                    <td class="py-3 pr-2 font-medium text-slate-800"><?php echo e($item->product_name); ?></td>
                    <td class="py-3 pr-2 text-right"><?php echo e(rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',')); ?></td>
                    <td class="py-3 pr-2 text-right"><?php echo e($fmt($item->unit_price)); ?></td>
                    <td class="py-3 text-right font-semibold"><?php echo e($fmt($item->line_total)); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>

        
        <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-sm text-xs">
                <div class="rounded border border-slate-200 px-3 py-2 leading-5 text-slate-600">
                    <span class="font-bold">Yalnız:</span> <?php echo e($invoice->amountInWords()); ?>

                </div>
                <?php if($invoice->notes): ?>
                    <div class="mt-2 px-1 text-slate-500">
                        <span class="font-semibold">Not:</span> <?php echo e($invoice->notes); ?>

                    </div>
                <?php endif; ?>
            </div>

            <div class="w-full max-w-xs space-y-1.5 text-sm sm:w-64">
                <div class="flex justify-between text-slate-600">
                    <span>Ara Toplam</span>
                    <span><?php echo e($fmt($invoice->subtotal)); ?></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>KDV (%<?php echo e(rtrim(rtrim(number_format($invoice->tax_rate, 2, ',', '.'), '0'), ',')); ?>)</span>
                    <span><?php echo e($fmt($invoice->tax_amount)); ?></span>
                </div>
                <div class="flex justify-between rounded-lg bg-slate-900 px-3 py-2 text-base font-bold text-white print:bg-slate-900">
                    <span>Genel Toplam</span>
                    <span><?php echo e($fmt($invoice->total)); ?></span>
                </div>
            </div>
        </div>

        
        <div class="mt-10 grid grid-cols-2 gap-10 text-center text-xs text-slate-500 print:break-inside-avoid">
            <div>
                <div class="mx-auto border-t border-dashed border-slate-400 pt-2">Düzenleyen / İmza</div>
            </div>
            <div>
                <div class="mx-auto border-t border-dashed border-slate-400 pt-2">Teslim Alan / İmza</div>
            </div>
        </div>

        <div class="mt-8 border-t border-slate-100 pt-3 text-center text-[10px] text-slate-400">
            Bu fatura <?php echo e(config('app.name')); ?> sistemi ile <?php echo e(now()->format('d.m.Y H:i')); ?> tarihinde oluşturulmuştur.
        </div>
    </div>

    
    <p class="mx-auto mt-4 max-w-4xl text-center text-xs text-slate-400 print:hidden">
        Çıktı almak için <span class="font-semibold">Yazdır / PDF Kaydet</span> butonunu kullanabilirsiniz;
        yazdırmada menü ve butonlar otomatik gizlenir.
    </p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/invoices/show.blade.php ENDPATH**/ ?>