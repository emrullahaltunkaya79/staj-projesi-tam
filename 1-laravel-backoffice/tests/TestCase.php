<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Güvenlik sigortası (proses düzeyi, Laravel'e bağımsız):
        // php artisan optimize config'i önbelleğe alırsa phpunit.xml'deki
        // sqlite ayarı yok sayılır ve RefreshDatabase GERÇEK MySQL'i siler
        // (17 Kasım veri kaybının kök nedeni). Böyle bir durumda testler
        // veritabanına dokunmadan derhal durur.
        if (getenv('DB_CONNECTION') !== 'sqlite') {
            fwrite(STDERR, "\n!!! GÜVENLİK DURDURMASI: DB_CONNECTION '".getenv('DB_CONNECTION')."' — testler yalnızca sqlite üzerinde koşar. Gerçek veritabanı korunmak için iptal edildi. (Çözüm: php artisan config:clear)\n");
            exit(1);
        }
        if (file_exists(__DIR__.'/../bootstrap/cache/config.php')) {
            fwrite(STDERR, "\n!!! GÜVENLİK DURDURMASI: bootstrap/cache/config.php var — önbellekli konfigürasyon sqlite ayarını ezer. Testler iptal edildi. (Çözüm: php artisan config:clear)\n");
            exit(1);
        }

        parent::setUp();
    }
}
