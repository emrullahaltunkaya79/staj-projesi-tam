
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title><?php echo e($quote->quote_no); ?></title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 11px; color: #334155; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .muted { color: #64748b; }
        .right { text-align: right; }
        .doc-title { font-size: 9px; letter-spacing: 3px; color: #4f46e5; font-weight: bold; }
        .quote-no { font-size: 20px; font-weight: bold; color: #0f172a; }
        .header { border-bottom: 3px solid #0f172a; padding-bottom: 12px; }
        .buyer { background-color: #f1f5f9; border: 1px solid #e2e8f0; padding: 10px 14px; margin-top: 16px; }
        .buyer-label { font-size: 8px; font-weight: bold; color: #94a3b8; letter-spacing: 2px; }
        .items { margin-top: 16px; }
        .items th { border-bottom: 2px solid #0f172a; text-align: left; font-size: 9px; text-transform: uppercase; color: #475569; padding: 6px 4px; }
        .items td { border-bottom: 1px solid #e2e8f0; padding: 8px 4px; }
        .totals td { padding: 5px 8px; }
        .grand { background-color: #0f172a; color: #ffffff; font-weight: bold; font-size: 12px; }
        .words { border: 1px solid #e2e8f0; padding: 8px 10px; color: #475569; }
        .sign { border-top: 1px dashed #94a3b8; padding-top: 6px; text-align: center; width: 40%; color: #64748b; font-size: 10px; }
        .footer { margin-top: 30px; text-align: center; color: #94a3b8; font-size: 8px; border-top: 1px solid #f1f5f9; padding-top: 8px; }
    </style>
</head>
<body>

<table class="header">
    <tr>
        <td style="width: 60%;">
            <?php if($quote->company->logoUrl()): ?>
                <img src="<?php echo e($quote->company->logoUrl()); ?>" style="height: 46px; margin-bottom: 6px;">
            <?php endif; ?>
            <div style="font-weight: bold; font-size: 15px; color: #0f172a;"><?php echo e($quote->company->name); ?></div>
            <div class="muted" style="line-height: 1.6;">
                <?php if($quote->company->address): ?> <?php echo e($quote->company->address); ?><br> <?php endif; ?>
                <?php if($quote->company->phone): ?> Tel: <?php echo e($quote->company->phone); ?><br> <?php endif; ?>
                <?php if($quote->company->tax_number): ?> Vergi No: <?php echo e($quote->company->tax_number); ?> <?php endif; ?>
            </div>
        </td>
        <td class="right" style="width: 40%;">
            <div class="doc-title">TEKLİF</div>
            <div class="quote-no"><?php echo e($quote->quote_no); ?></div>
            <div class="muted">Tarih: <?php echo e($quote->issue_date->format('d.m.Y')); ?></div>
            <?php if($quote->valid_until): ?>
                <div class="muted">Geçerlilik: <?php echo e($quote->valid_until->format('d.m.Y')); ?></div>
            <?php endif; ?>
        </td>
    </tr>
</table>

<div class="buyer">
    <div class="buyer-label">MÜŞTERİ (CARİ)</div>
    <div style="margin-top: 4px;">
        <strong><?php echo e($quote->contact->name); ?></strong>
        <?php if($quote->contact->tax_number): ?>
            <span class="muted"> — VKN: <?php echo e($quote->contact->tax_number); ?></span>
        <?php endif; ?>
    </div>
    <div class="muted">
        <?php if($quote->contact->phone): ?> <?php echo e($quote->contact->phone); ?> <?php endif; ?>
        <?php if($quote->contact->email): ?> · <?php echo e($quote->contact->email); ?> <?php endif; ?>
    </div>
</div>

<table class="items">
    <thead>
    <tr>
        <th style="width: 6%;">#</th>
        <th>Ürün / Açıklama</th>
        <th class="right">Miktar</th>
        <th class="right">Birim Fiyat</th>
        <th class="right">Tutar</th>
    </tr>
    </thead>
    <tbody>
    <?php $__currentLoopData = $quote->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td class="muted"><?php echo e($i + 1); ?></td>
            <td><strong><?php echo e($item->product_name); ?></strong></td>
            <td class="right"><?php echo e(rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',')); ?></td>
            <td class="right"><?php echo e(number_format($item->unit_price, 2, ',', '.')); ?> TL</td>
            <td class="right"><strong><?php echo e(number_format($item->line_total, 2, ',', '.')); ?> TL</strong></td>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>

<table style="margin-top: 16px;">
    <tr>
        <td style="width: 55%;">
            <div class="words"><strong>Yalnız:</strong> <?php echo e($quote->amountInWords()); ?></div>
            <?php if($quote->notes): ?>
                <div class="muted" style="margin-top: 6px;"><strong>Not:</strong> <?php echo e($quote->notes); ?></div>
            <?php endif; ?>
        </td>
        <td style="width: 45%; padding-left: 20px;">
            <table class="totals">
                <tr>
                    <td>Ara Toplam</td>
                    <td class="right"><?php echo e(number_format($quote->subtotal, 2, ',', '.')); ?> TL</td>
                </tr>
                <tr>
                    <td>KDV (%<?php echo e(rtrim(rtrim(number_format($quote->tax_rate, 2, ',', '.'), '0'), ',')); ?>)</td>
                    <td class="right"><?php echo e(number_format($quote->tax_amount, 2, ',', '.')); ?> TL</td>
                </tr>
                <tr>
                    <td class="grand">Genel Toplam</td>
                    <td class="right grand"><?php echo e(number_format($quote->total, 2, ',', '.')); ?> TL</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<?php if($quote->convertedInvoice): ?>
    <div style="margin-top: 12px; border: 1px solid #10b981; background-color: #ecfdf5; padding: 8px; color: #065f46;">
        <strong>Bu teklif faturalandırılmıştır.</strong>
    </div>
<?php else: ?>
    <div style="margin-top: 12px; border: 1px solid #e2e8f0; background-color: #f8fafc; padding: 8px; color: #64748b;">
        <strong>Onay:</strong> Bu teklifi kabul ediyorum — imza / tarih
    </div>
<?php endif; ?>

<table style="margin-top: 55px;">
    <tr>
        <td class="sign">Düzenleyen / İmza</td>
        <td style="width: 20%;"></td>
        <td class="sign">Teslim Alan / İmza</td>
    </tr>
</table>

<div class="footer">
    Bu teklif <?php echo e(config('app.name')); ?> sistemi ile oluşturulmuştur.
</div>

</body>
</html>
<?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/quotes/pdf.blade.php ENDPATH**/ ?>