/**
 * Ürün görseli için proxy URL döner.
 * Base64'ü HTML'e gömmek yerine /api/img/{id} üzerinden binary servis edilir.
 * Bu, sayfa boyutunu ~23MB'tan ~200KB'a düşürür.
 *
 * ?v= sürüm sabiti: görselleri toplu değiştirdiğinde bu sabiti yükselt —
 * tarayıcı eski önbelleği anında bırakır (SSR/istemci tutarlıdır).
 */
const GORSEL_SURUM = "2026-11-11-1";

export function imgUrl(urunId: number, fallback?: string | null): string {
  if (!urunId) return fallback ?? "";
  return `/api/img/${urunId}?v=${GORSEL_SURUM}`;
}
