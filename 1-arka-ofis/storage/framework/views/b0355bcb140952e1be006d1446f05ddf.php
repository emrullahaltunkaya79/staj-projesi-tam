<?php $__env->startSection('title', 'Cari Ekstre: '.$contact->name); ?>

<?php
    $isCustomer = $contact->isCustomer();
?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(route('cariler.edit', $contact)); ?>" class="btn-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
        Düzenle
    </a>
    <?php if($isCustomer): ?>
        <a href="<?php echo e(route('payments.create', ['contact_id' => $contact->id])); ?>" data-modal-url="<?php echo e(route('payments.create', ['contact_id' => $contact->id])); ?>" data-modal-title="Yeni Tahsilat" class="btn-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Tahsilat Al
        </a>
    <?php else: ?>
        <a href="<?php echo e(route('payments.create_supplier', ['contact_id' => $contact->id])); ?>" data-modal-url="<?php echo e(route('payments.create_supplier', ['contact_id' => $contact->id])); ?>" data-modal-title="Tedarikçi Ödemesi" class="btn-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Ödeme Yap
        </a>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
        $balance = (float) $contact->balance;
    ?>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        
        <div class="card lg:col-span-2">
            <div class="card-header flex items-center justify-between">
                <span>Cari Bilgileri</span>
                <span class="<?php echo e($contact->isCustomer() ? 'badge-indigo' : 'badge-slate'); ?>"><?php echo e($contact->typeLabel()); ?></span>
            </div>
            <div class="grid grid-cols-1 gap-x-6 gap-y-4 p-5 sm:grid-cols-2">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Ünvan</div>
                    <div class="mt-1 text-lg font-bold text-slate-800"><?php echo e($contact->name); ?></div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Vergi / TC No</div>
                    <div class="mt-1 font-mono text-sm text-slate-700"><?php echo e($contact->tax_number ?: '—'); ?></div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Telefon</div>
                    <div class="mt-1 text-sm text-slate-700"><?php echo e($contact->phone ?: '—'); ?></div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">E-posta</div>
                    <div class="mt-1 text-sm text-slate-700"><?php echo e($contact->email ?: '—'); ?></div>
                </div>
                <div class="sm:col-span-2">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Adres</div>
                    <div class="mt-1 text-sm text-slate-700"><?php echo e($contact->address ?: '—'); ?></div>
                </div>
            </div>
        </div>

        
        <div class="card p-5">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Güncel Bakiye</div>
            <div class="mt-2 text-3xl font-bold <?php echo e($balance > 0 ? 'text-emerald-600' : ($balance < 0 ? 'text-red-600' : 'text-slate-700')); ?>">
                <?php echo e($balance > 0 ? '+' : ''); ?><?php echo e($fmt($balance)); ?>

            </div>
            <div class="mt-1 text-sm <?php echo e($balance > 0 ? 'text-emerald-600' : ($balance < 0 ? 'text-red-600' : 'text-slate-500')); ?>">
                <?php if($balance > 0): ?>
                    Cari size borçlu (alacak)
                <?php elseif($balance < 0): ?>
                    <?php echo e($isCustomer ? 'Cariye borçlusunuz' : 'Tedarikçiye borçlusunuz'); ?>

                <?php else: ?>
                    Borç / alacak yok
                <?php endif; ?>
            </div>

            <dl class="mt-5 space-y-2.5 border-t border-slate-100 pt-4 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Kesilen fatura</dt>
                    <dd class="font-semibold text-slate-700"><?php echo e($summary['invoice_count']); ?> adet</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Toplam fatura tutarı</dt>
                    <dd class="font-semibold text-slate-700"><?php echo e($fmt($summary['invoice_total'])); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Toplam <?php echo e($isCustomer ? 'tahsilat' : 'ödeme'); ?></dt>
                    <dd class="font-semibold text-emerald-600"><?php echo e($fmt($summary['payment_total'])); ?></dd>
                </div>
                <div class="border-t border-slate-100 pt-2.5"></div>
                <div class="pt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Yaşlandırma (kalan alacak)</div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Vadesi gelmemiş</dt>
                    <dd><?php echo e($fmt($summary['aging']['vadesi_gecmedi'])); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">1-30 gün gecikmiş</dt>
                    <dd class="<?php echo e($summary['aging']['gun_1_30'] > 0 ? 'font-semibold text-red-600' : ''); ?>"><?php echo e($fmt($summary['aging']['gun_1_30'])); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">31-60 gün gecikmiş</dt>
                    <dd class="<?php echo e($summary['aging']['gun_31_60'] > 0 ? 'font-semibold text-red-600' : ''); ?>"><?php echo e($fmt($summary['aging']['gun_31_60'])); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">60+ gün gecikmiş</dt>
                    <dd class="<?php echo e($summary['aging']['gun_60_ustu'] > 0 ? 'font-semibold text-red-600' : ''); ?>"><?php echo e($fmt($summary['aging']['gun_60_ustu'])); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Son işlem tarihi</dt>
                    <dd class="font-semibold text-slate-700"><?php echo e($summary['last_invoice_date'] ? \Carbon\Carbon::parse($summary['last_invoice_date'])->format('d.m.Y') : '—'); ?></dd>
                </div>
            </dl>
        </div>
    </div>

    
    <div class="card mt-6">
        <div class="card-header flex items-center justify-between">
            <span class="flex items-center gap-2">
                <svg class="h-5 w-5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Ekstre — <?php echo e($contact->name); ?>

            </span>
            <span class="text-xs font-normal text-slate-400">Bu cariye kesilen faturalar</span>
        </div>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Fatura No</th>
                    <th>Tarih</th>
                    <th>Vade</th>
                    <th class="!text-right">Ara Toplam</th>
                    <th class="!text-right">KDV</th>
                    <th class="!text-right">Tutar</th>
                    <th class="!text-right">Kalan</th>
                    <th>Durum</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $state = $statuses[$invoice->id] ?? ['status' => 'acik', 'remaining' => (float) $invoice->total, 'days_overdue' => 0];
                    ?>
                    <tr class="hover:bg-slate-50">
                        <td>
                            <a href="<?php echo e(route('faturalar.show', $invoice)); ?>" class="font-semibold text-indigo-600 hover:text-indigo-700"><?php echo e($invoice->invoice_no); ?></a>
                        </td>
                        <td><?php echo e($invoice->issue_date->format('d.m.Y')); ?></td>
                        <td>
                            <?php if($invoice->due_date): ?>
                                <span class="<?php echo e($state['days_overdue'] > 0 && $state['remaining'] > 0 ? 'font-semibold text-red-600' : 'text-slate-600'); ?>">
                                    <?php echo e($invoice->due_date->format('d.m.Y')); ?>

                                </span>
                            <?php else: ?>
                                <span class="text-slate-400">açık hesap</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right"><?php echo e($fmt($invoice->subtotal)); ?></td>
                        <td class="text-right"><?php echo e($fmt($invoice->tax_amount)); ?></td>
                        <td class="text-right font-bold"><?php echo e($fmt($invoice->total)); ?></td>
                        <td class="text-right <?php echo e($state['remaining'] > 0 ? 'font-semibold text-slate-700' : 'text-slate-400'); ?>">
                            <?php echo e($state['remaining'] > 0 ? $fmt($state['remaining']) : '—'); ?>

                        </td>
                        <td>
                            <?php if($state['days_overdue'] > 0 && $state['remaining'] > 0): ?>
                                <span class="badge-red"><?php echo e($state['days_overdue']); ?> gün gecikti</span>
                            <?php elseif($state['status'] === 'kapali'): ?>
                                <span class="badge-green">Kapandı</span>
                            <?php elseif($state['status'] === 'kismi'): ?>
                                <span class="badge-amber">Kısmi ödendi</span>
                            <?php else: ?>
                                <span class="badge-amber">Açık</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="py-10 text-center text-slate-400">Bu cariye henüz fatura kesilmedi.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($invoices->hasPages()): ?>
            <div class="border-t border-slate-100 px-4 py-3">
                <?php echo e($invoices->links()); ?>

            </div>
        <?php endif; ?>
    </div>

    
    <div class="card mt-6">
        <div class="card-header flex items-center justify-between">
            <span class="flex items-center gap-2">
                <svg class="h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <?php echo e($isCustomer ? 'Tahsilat' : 'Ödeme'); ?> Geçmişi
            </span>
            <a href="<?php echo e(route($isCustomer ? 'payments.create' : 'payments.create_supplier', ['contact_id' => $contact->id])); ?>"
               data-modal-url="<?php echo e(route($isCustomer ? 'payments.create' : 'payments.create_supplier', ['contact_id' => $contact->id])); ?>"
               data-modal-title="<?php echo e($isCustomer ? 'Yeni Tahsilat' : 'Tedarikçi Ödemesi'); ?>"
               class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">+ <?php echo e($isCustomer ? 'Tahsilat' : 'Ödeme'); ?> Ekle</a>
        </div>
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Tür</th>
                    <th class="!text-right">Tutar</th>
                    <th>Açıklama</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap"><?php echo e($payment->created_at->format('d.m.Y H:i')); ?></td>
                        <td>
                            <span class="<?php echo e($payment->isCash() ? 'badge-green' : 'badge-indigo'); ?>"><?php echo e($payment->typeLabel()); ?></span>
                        </td>
                        <td class="text-right font-semibold text-emerald-600">+<?php echo e($fmt($payment->amount)); ?></td>
                        <td class="text-slate-500"><?php echo e($payment->description ?: '—'); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="4" class="py-10 text-center text-slate-400">Bu cariden henüz tahsilat yapılmadı.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-3 text-xs text-slate-400">
        Not: Tahsilatlar cari bakiyesinden otomatik düşer; fatura durumları en eski faturadan başlayarak (FIFO) kapatılır.
    </p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/contacts/show.blade.php ENDPATH**/ ?>