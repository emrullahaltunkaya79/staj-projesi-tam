/**
 * Arka ofis fiyatları KDV hariç tutar (muhasebe); sepet ve sipariş hesabı da KDV hariç tutarla yapılır
 * ki toplam arka ofisle kuruşu kuruşuna aynı olsun. Müşteriye gösterilen her fiyat KDV dahildir.
 */
export const KDV_ORANI = 20;

export function kdvDahil(tutar: number): number {
  return Math.round(tutar * (100 + KDV_ORANI)) / 100;
}

export function tl(tutar: number): string {
  return new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(tutar);
}
