<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Günlük özet e-postası: kritik stok + vadesi geçen/yaklaşan faturalar.
// Çalışması için sunucuda her dakika çalışacak bir cron kaydı gerekir:
//   * * * * * cd /proje-yolu && php artisan schedule:run >> /dev/null 2>&1
// (Yerel geliştirmede `php artisan schedule:work` ya da komutun elle
//  çalıştırılması yeterlidir: `php artisan bildirim:gundelik-ozet`)
Schedule::command('bildirim:gundelik-ozet')->dailyAt('08:00');

// Vadesi gelen tekrarlayan faturaları kes (aylık kira/abonelik vb.)
Schedule::command('tekrarlayan:faturalar-kes')->dailyAt('00:05');

// Ödemesi gelmeyen rezerve siparişleri serbest bırak (her 5 dakikada)
Schedule::command('siparisler:rezerv-serbest-birak')->everyFiveMinutes();

// CSPR kripto ödemelerini blok zincirde kontrol et (her 2 dakikada)
Schedule::command('casper:odemeler-kontrol')->everyTwoMinutes();

// Haftalık veritabanı yedeği (spatie/laravel-backup → storage/app/backups)
Schedule::command('backup:run --only-db')->weeklyOn(1, '03:00');
