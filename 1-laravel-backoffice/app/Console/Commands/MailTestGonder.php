<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * E-posta bağlantı testi: aktif mail ayarlarıyla test mesajı gönderir.
 * Kullanım: php artisan mail:test --to=sizin@adresiniz.com
 */
class MailTestGonder extends Command
{
    protected $signature = 'mail:test {--to= : Test mesajının gideceği adres}';

    protected $description = 'Aktif e-posta ayarlarıyla test mesajı gönderir ve sonucu raporlar';

    public function handle(): int
    {
        $alici = $this->option('to') ?: config('mail.from.address');

        if (! $alici) {
            $this->error('--to=adres verin ya da MAIL_FROM_ADDRESS tanımlayın.');

            return self::FAILURE;
        }

        $surucu = config('mail.default');

        try {
            Mail::raw(
                "HepsiBirarada e-posta bağlantı testi.\n\nBu mesajı görüyorsanız mail ayarlarınız çalışıyor demektir.\nSürücü: {$surucu}\nZaman: " . now()->format('d.m.Y H:i'),
                function ($m) use ($alici) {
                    $m->to($alici)->subject('✉️ HepsiBirarada mail testi');
                }
            );
        } catch (\Throwable $e) {
            $this->error('GÖNDERİLEMEDİ: ' . $e->getMessage());
            $this->line('Sürücü: ' . $surucu . ' — MAIL_MAILER=smtp için MAIL_HOST/PORT/USERNAME/PASSWORD değerlerini kontrol edin.');

            return self::FAILURE;
        }

        if ($surucu === 'log') {
            $this->warn("Sürücü 'log' — mesaj storage/logs/laravel.log dosyasına yazıldı, gerçek e-posta GİTMEDİ.");
            $this->line('Gerçek gönderim için: MAIL_MAILER=smtp + SMTP değerleri → sonra tekrar deneyin.');
        } else {
            $this->info("{$alici} adresine test mesajı gönderildi (sürücü: {$surucu}).");
        }

        return self::SUCCESS;
    }
}
