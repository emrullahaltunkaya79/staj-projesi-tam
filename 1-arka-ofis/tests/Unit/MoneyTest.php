<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    /**
     * @dataProvider tutarSaglayicisi
     */
    public function test_tutarlari_turkce_yaziya_cevirir(float $amount, string $expected): void
    {
        $this->assertSame($expected, Money::toTurkishWords($amount));
    }

    public static function tutarSaglayicisi(): array
    {
        return [
            'sıfır' => [0.0, 'sıfır türk lirası'],
            'basit' => [240.0, 'ikiyüz kırk türk lirası'],
            'bin' => [1080.0, 'bin seksen türk lirası'],
            'kuruslu' => [1973.4, 'bin dokuzyüz yetmiş üç türk lirası kırk kuruş'],
            'sadece_kurus' => [0.5, 'elli kuruş'],
            'buyuk' => [1250000.0, 'bir milyon ikiyüz elli bin türk lirası'],
        ];
    }
}
