<?php
/**
 * Webhook test alıcısı (yalnızca geliştirme/demo).
 * Panelin gönderdiği webhook'ları storage/logs/webhook-alici.log'a yazar
 * ve 200 döner. Ayarlar → Entegrasyon'daki webhook URL'si olarak
 * http://<sunucu>/webhook-alici.php girilir.
 */

declare(strict_types=1);

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '-';
$event = $_SERVER['HTTP_X_EVENT'] ?? 'bilinmeyen';

$satir = sprintf(
    "[%s] OLAY: %s | İMZA: %s | GÖVDE: %s\n",
    date('d.m.Y H:i:s'),
    $event,
    $signature,
    $payload ?: '(boş)'
);

file_put_contents(
    __DIR__ . '/../storage/logs/webhook-alici.log',
    $satir,
    FILE_APPEND
);

header('Content-Type: application/json');
echo json_encode(['ok' => true, 'event' => $event]);
