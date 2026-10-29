<?php

namespace App\Services;

/**
 * Oturum tabanlı sepet. Ödeme onayına kadar hiçbir şey arka ofise yazılmaz;
 * sipariş anında stok arka ofiste rezerve edilir.
 */
class Cart
{
    /** @return array<int, array{name: string, price: float, qty: int, image: ?string, unit: string}> */
    public static function items(): array
    {
        return session('cart', []);
    }

    public static function add(int $productId, string $name, float $price, int $qty = 1, ?string $image = null, string $unit = 'adet'): void
    {
        $cart = self::items();
        $mevcut = $cart[$productId]['qty'] ?? 0;
        $cart[$productId] = [
            'name' => $name,
            'price' => $price,
            'qty' => $mevcut + $qty,
            'image' => $image,
            'unit' => $unit,
        ];
        session(['cart' => $cart]);
    }

    public static function update(int $productId, int $qty): void
    {
        $cart = self::items();
        if ($qty <= 0) {
            unset($cart[$productId]);
        } elseif (isset($cart[$productId])) {
            $cart[$productId]['qty'] = $qty;
        }
        session(['cart' => $cart]);
    }

    public static function remove(int $productId): void
    {
        $cart = self::items();
        unset($cart[$productId]);
        session(['cart' => $cart]);
    }

    public static function clear(): void
    {
        session()->forget('cart');
    }

    public static function count(): int
    {
        return array_sum(array_column(self::items(), 'qty'));
    }

    public static function subtotal(): float
    {
        return round(array_sum(array_map(fn ($i) => $i['price'] * $i['qty'], self::items())), 2);
    }

    public static function tax(float $rate = 20): float
    {
        return round(self::subtotal() * $rate / 100, 2);
    }

    public static function total(float $rate = 20): float
    {
        return round(self::subtotal() + self::tax($rate), 2);
    }
}
