<?php

namespace App\Jobs;

use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Webhook gönderimini kuyruk üzerinden çalıştırır.
 *
 * Senkron gönderim API isteğini bloklar (hedef yavaşsa 10 sn'ye kadar);
 * tek iş parçacıklı geliştirme sunucusunda ise kendine çağrı çıkmaza girer.
 * Kuyruk işi olarak ayrı süreçte gönderilir.
 */
class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $event,
        public array $payload,
        public string $url,
    ) {}

    public function handle(WebhookService $service): void
    {
        $service->deliver($this->event, $this->payload, $this->url, 1);
    }
}
