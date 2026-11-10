<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Etiket <?php echo e($order->order_no); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; }
        .label { width: 186mm; padding: 5mm; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 3mm; margin-bottom: 4mm; }
        .header .firm { font-size: 13px; font-weight: bold; }
        .header .firm small { display: block; font-weight: normal; color: #475569; font-size: 9px; margin-top: 1mm; }
        .order-no { font-size: 15px; font-weight: bold; text-align: right; }
        .order-no small { display: block; font-size: 8px; color: #475569; font-weight: normal; }

        .carrier { background: #0f172a; color: #fff; font-size: 20px; font-weight: bold; text-transform: uppercase; text-align: center; padding: 3mm; border-radius: 2mm; margin-bottom: 4mm; letter-spacing: 2px; }

        .parties { display: flex; gap: 4mm; margin-bottom: 4mm; }
        .box { flex: 1; border: 1px solid #cbd5e1; border-radius: 2mm; padding: 3mm; }
        .box .title { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 1.5mm; }
        .box .name { font-size: 12px; font-weight: bold; margin-bottom: 1mm; }
        .box .line { font-size: 9px; color: #334155; line-height: 1.5; }

        .tracking { border: 2px solid #0f172a; border-radius: 2mm; padding: 3mm 4mm; text-align: center; margin-bottom: 4mm; }
        .tracking .label-text { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; }
        .tracking .code { font-size: 22px; font-weight: bold; letter-spacing: 3px; margin-top: 1mm; font-family: DejaVu Sans Mono, monospace; }
        .tracking .bars { font-size: 26px; letter-spacing: -1px; margin-top: 1mm; color: #0f172a; }

        .footer { display: flex; justify-content: space-between; font-size: 9px; color: #475569; border-top: 1px solid #cbd5e1; padding-top: 2.5mm; }
        .footer strong { color: #0f172a; }

        .items { font-size: 9px; color: #334155; margin-bottom: 3mm; line-height: 1.6; }
        .items li { margin-left: 5mm; }
    </style>
</head>
<body>
    <div class="label">
        <div class="header">
            <div class="firm">
                GÖNDEREN
                <small><?php echo e($company->name); ?></small>
                <small><?php echo e($company->address); ?></small>
                <small><?php echo e($company->phone); ?></small>
            </div>
            <div class="order-no">
                <?php echo e($order->order_no); ?>

                <small><?php echo e($order->created_at->format('d.m.Y H:i')); ?></small>
            </div>
        </div>

        <?php if($order->carrier): ?>
            <div class="carrier"><?php echo e($order->carrier); ?></div>
        <?php endif; ?>

        <div class="parties">
            <div class="box">
                <div class="title">Alıcı</div>
                <div class="name"><?php echo e($order->customer_name ?: $order->contact?->name); ?></div>
                <div class="line">
                    <?php echo e($order->contact?->phone); ?><br>
                    <?php echo e($order->contact?->email); ?>

                </div>
            </div>
            <div class="box">
                <div class="title">Teslim Adresi</div>
                <div class="line" style="font-size:10px;">
                    <?php echo e($order->shipping_address ?: 'Adres belirtilmemiş'); ?>

                </div>
            </div>
        </div>

        <div class="tracking">
            <div class="label-text">Takip No</div>
            <div class="bars">||| ||| || |||| ||| || ||| |||| ||| || ||| ||||</div>
            <div class="code"><?php echo e($order->tracking_no ?: '—'); ?></div>
        </div>

        <ul class="items">
            <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e(($item['quantity'] + 0)); ?> × <?php echo e($item['product_name'] ?? 'Ürün #'.$item['product_id']); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>

        <div class="footer">
            <span>Sipariş: <strong><?php echo e($order->order_no); ?></strong></span>
            <span>Durum: <strong><?php echo e($order->statusLabel()); ?></strong></span>
            <span>Toplam: <strong><?php echo e(number_format((float) $order->total, 2, ',', '.')); ?> ₺</strong></span>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/orders/label.blade.php ENDPATH**/ ?>