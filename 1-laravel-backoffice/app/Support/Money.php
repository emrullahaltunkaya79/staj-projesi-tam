<?php

namespace App\Support;

/**
 * Para tutarını Türkçe yazıya çevirir (resmi faturalardaki
 * "Yalnız ... Türk Lirası ... Kuruş" satırı için).
 */
class Money
{
    private const ONES = ['', 'bir', 'iki', 'üç', 'dört', 'beş', 'altı', 'yedi', 'sekiz', 'dokuz'];
    private const TENS = ['', 'on', 'yirmi', 'otuz', 'kırk', 'elli', 'altmış', 'yetmiş', 'seksen', 'doksan'];
    private const GROUPS = ['', 'bin', 'milyon', 'milyar'];

    public static function toTurkishWords(float $amount): string
    {
        $amount = round($amount, 2);
        $lira = (int) floor($amount);
        $kurus = (int) round(($amount - $lira) * 100);

        if ($lira === 0 && $kurus === 0) {
            return 'sıfır türk lirası';
        }

        $words = [];

        if ($lira > 0) {
            $chunks = [];
            $n = $lira;
            while ($n > 0) {
                array_unshift($chunks, $n % 1000);
                $n = intdiv($n, 1000);
            }

            $groupCount = count($chunks);
            foreach ($chunks as $i => $chunk) {
                if ($chunk === 0) {
                    continue;
                }

                $group = $groupCount - 1 - $i;
                $suffix = self::GROUPS[$group] ?? '';

                // "bir bin" değil, sadece "bin" denir
                if ($group === 1 && $chunk === 1) {
                    $words[] = 'bin';
                } else {
                    $words[] = trim(self::threeDigits($chunk).' '.$suffix);
                }
            }

            $words[] = 'türk lirası';
        }

        if ($kurus > 0) {
            $words[] = self::threeDigits($kurus).' kuruş';
        }

        return implode(' ', $words);
    }

    private static function threeDigits(int $n): string
    {
        $parts = [];

        if ($n >= 100) {
            $hundreds = intdiv($n, 100);
            // "bir yüz" değil, "yüz" denir
            $parts[] = ($hundreds === 1 ? '' : self::ONES[$hundreds]).'yüz';
            $n %= 100;
        }

        if ($n >= 10) {
            $parts[] = self::TENS[intdiv($n, 10)];
            $n %= 10;
        }

        if ($n > 0) {
            $parts[] = self::ONES[$n];
        }

        return trim(implode(' ', $parts));
    }
}
