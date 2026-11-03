import type { Urun } from "./backoffice";

/** Outlet: %25 ve üzeri derin indirimli ürünler. */
export function outletUrunler(tumu: Urun[]): Urun[] {
  return tumu
    .filter((u) => (u.discount_percent ?? 0) >= 25)
    .sort((a, b) => (b.discount_percent ?? 0) - (a.discount_percent ?? 0));
}

/** Son kalan ölçü/beden: varyantlı ürünlerde en düşük varyant stoğu 5 ve altında. */
export function sonKalanUrunler(tumu: Urun[]): Urun[] {
  return tumu
    .filter((u) => u.varyant_min_stok != null && u.varyant_min_stok <= 5)
    .sort((a, b) => (a.varyant_min_stok ?? 99) - (b.varyant_min_stok ?? 99));
}

/** Son fırsat: müsait stoğu 5 ve altına inmiş, tükenmek üzere olan ürünler. */
export function sonFirsatUrunler(tumu: Urun[]): Urun[] {
  return tumu
    .filter((u) => u.in_stock && u.available_quantity <= 5)
    .sort((a, b) => a.available_quantity - b.available_quantity);
}
