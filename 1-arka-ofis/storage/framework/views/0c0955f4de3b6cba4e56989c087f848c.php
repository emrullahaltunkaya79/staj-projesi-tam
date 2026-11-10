<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Onayı</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
    <div style="max-width:600px;margin:0 auto;padding:20px;">
        <div style="background:#1a1a2e;border-radius:12px 12px 0 0;padding:24px;text-align:center;">
            <h1 style="color:#fff;font-size:22px;margin:0;">HepsiBirarada</h1>
            <p style="color:#818cf8;font-size:13px;margin:4px 0 0;">Teknoloji & Ofis — her şey tek yerde</p>
        </div>

        <div style="background:#fff;border-radius:0 0 12px 12px;padding:28px;">
            <h2 style="color:#1a1a2e;font-size:18px;margin:0 0 8px;">Siparişiniz alındı! 🎉</h2>
            <p style="color:#555;font-size:14px;line-height:1.6;margin:0 0 16px;">
                Merhaba <?php echo e($order->customer_name); ?>,<br>
                Siparişiniz başarıyla oluşturuldu ve stoklar sizin adınıza rezerve edildi.
            </p>

            <div style="background:#f8f8f8;border-radius:8px;padding:16px;margin:16px 0;">
                <table style="width:100%;font-size:13px;color:#333;">
                    <tr>
                        <td style="padding:4px 0;color:#888;">Sipariş No:</td>
                        <td style="padding:4px 0;text-align:right;font-weight:bold;"><?php echo e($order->order_no); ?></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#888;">Toplam Tutar:</td>
                        <td style="padding:4px 0;text-align:right;font-weight:bold;color:#1a1a2e;"><?php echo e($total); ?></td>
                    </tr>
                    <?php if($order->expires_at): ?>
                    <tr>
                        <td style="padding:4px 0;color:#888;">Rezervasyon Bitişi:</td>
                        <td style="padding:4px 0;text-align:right;color:#e53e3e;font-weight:bold;">
                            <?php echo e(\Carbon\Carbon::parse($order->expires_at)->format('d.m.Y H:i')); ?>

                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <?php if(!empty($items)): ?>
            <h3 style="color:#1a1a2e;font-size:14px;margin:16px 0 8px;">Sipariş İçeriği:</h3>
            <table style="width:100%;font-size:13px;color:#333;border-collapse:collapse;">
                <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:8px 0;"><?php echo e(($item['quantity'] + 0)); ?> × <?php echo e($item['product_name'] ?? 'Ürün'); ?></td>
                    <td style="padding:8px 0;text-align:right;font-weight:600;"><?php echo e(number_format(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0), 2, ',', '.')); ?> ₺</td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </table>
            <?php endif; ?>

            <div style="text-align:center;margin:24px 0;">
                <a href="<?php echo e(config('app.url')); ?>/siparis/<?php echo e($order->order_no); ?>"
                   style="display:inline-block;background:#1a1a2e;color:#fff;text-decoration:none;padding:12px 32px;border-radius:24px;font-size:14px;font-weight:bold;">
                    Siparişi Takip Et →
                </a>
            </div>

            <p style="color:#888;font-size:12px;text-align:center;margin:16px 0 0;">
                Bu e-posta otomatik olarak gönderilmiştir. Sorularınız için:
                <a href="mailto:merhaba@hepsibirarada.test" style="color:#4f46e5;">merhaba@hepsibirarada.test</a>
            </p>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\Users\Asus\OneDrive\Desktop\ss\resources\views/emails/order-confirmation.blade.php ENDPATH**/ ?>