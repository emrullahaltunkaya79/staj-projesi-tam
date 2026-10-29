/**
 * Ürün görseli için proxy URL döner.
 * Base64'ü HTML'e gömmek yerine /api/img/{id} üzerinden binary servis edilir.
 * Bu, sayfa boyutunu ~23MB'tan ~200KB'a düşürür.
 */
export function imgUrl(urunId: number, fallback?: string | null): string {
  if (!urunId) return fallback ?? "";
  return `/api/img/${urunId}`;
}
