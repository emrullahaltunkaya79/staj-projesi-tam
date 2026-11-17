/**
 * Ürün görseli için proxy URL döner.
 * Base64'ü HTML'e gömmek yerine /api/img/{id} üzerinden binary servis edilir.
 * Bu, sayfa boyutunu ~23MB'tan ~200KB'a düşürür.
 *
 * ?v= yapısal sürüm: görsel sistemini toplu değiştirdiğinde yükselt.
 * ?s= ürün sürümü: panelde tek ürünün görseli değişince backoffice'in
 * gönderdiği gorsel_surum değeri buraya geçer → tarayıcı o ürünün görselini
 * anında yeniler (diğer ürünlerin önbelleği bozulmaz).
 */
const GORSEL_SURUM = "2026-11-17-1";

export function imgUrl(urunId: number, fallback?: string | null, surum?: number): string {
  if (!urunId) return fallback ?? "";
  return `/api/img/${urunId}?v=${GORSEL_SURUM}&s=${surum ?? 0}`;
}
