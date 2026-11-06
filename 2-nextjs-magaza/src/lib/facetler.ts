/**
 * Ürün adından ve varyant adlarından türetilen facet (renk / boyut) çıkarımı.
 * Demo katalogda yapısal öznitelik olmadığı için isim taramasıyla çalışır.
 */

export const RENKLER: { ad: string; hex: string; kalip: RegExp }[] = [
  { ad: "Siyah", hex: "#111827", kalip: /siyah|black/i },
  { ad: "Beyaz", hex: "#ffffff", kalip: /beyaz|white/i },
  { ad: "Gri", hex: "#9ca3af", kalip: /\bgri\b|gray|gümüş gri/i },
  { ad: "Gümüş", hex: "#cbd5e1", kalip: /gümüş|silver|alüminyum|alumin/i },
  { ad: "Mavi", hex: "#3b82f6", kalip: /mavi|blue/i },
  { ad: "Yeşil", hex: "#22c55e", kalip: /yeşil|green/i },
  { ad: "Pembe", hex: "#ec4899", kalip: /pembe|pink/i },
  { ad: "Kırmızı", hex: "#ef4444", kalip: /kırmızı|red\b/i },
  { ad: "Turuncu", hex: "#f97316", kalip: /turuncu|orange/i },
  { ad: "Mor", hex: "#8b5cf6", kalip: /\bmor\b|purple|mor\b/i },
  { ad: "Lacivert", hex: "#1e3a8a", kalip: /lacivert|navy/i },
  { ad: "Antrasit", hex: "#374151", kalip: /antrasit/i },
  { ad: "Altın", hex: "#d4af37", kalip: /altın|gold/i },
  { ad: "Kahverengi", hex: "#92400e", kalip: /kahverengi|brown|deri/i },
];

/** Ürünün taşıdığı renk etiketleri (ad + varyant adları taranır). */
export function urunRenkleri(ad: string, varyantlar: string[] = []): string[] {
  const metin = [ad, ...varyantlar].join(" ");
  return RENKLER.filter((r) => r.kalip.test(metin)).map((r) => r.ad);
}

const BOYUT_KALIP = /(\d+(?:[.,]\d+)?)\s*(cm|mm|"|″|ml|l|gb|tb|mah|m|kg)\b/gi;

/** Beden facet sıralaması — küçükten büyüğe. */
export const BEDEN_SIRASI = ["S", "M", "L", "XL", "XXL", "S/M", "M/L", "L/XL"];

const BIRIM_GOSTER: Record<string, string> = {
  '"': "″", "″": "″", cm: "cm", mm: "mm", ml: "ml", l: "L",
  gb: "GB", tb: "TB", mah: "mAh", m: "m", kg: "kg",
};

/** Ürün adından ölçü/boyut etiketleri: "24″", "500 ml", "128 GB", "2 m"... */
export function urunBoyutlari(ad: string): string[] {
  const sonuc = new Set<string>();
  for (const [, hamSayi, hamBirim] of ad.matchAll(BOYUT_KALIP)) {
    const birim = hamBirim.toLowerCase();
    const sayi = hamSayi.replace(",", ".");
    sonuc.add(`${sayi} ${BIRIM_GOSTER[birim === "″" ? "″" : birim] ?? birim}`);
  }
  return [...sonuc];
}

/** Facet seçeneklerini sayılarıyla üretir (çoktan aza sıralı, en fazla `sinir` adet). */
export function facetSecenekleri<T>(
  urunler: { degerler: T[] }[],
  sinir = 14,
): { deger: T; sayi: number }[] {
  const sayac = new Map<T, number>();
  for (const u of urunler) {
    for (const d of new Set(u.degerler)) sayac.set(d, (sayac.get(d) ?? 0) + 1);
  }
  return [...sayac.entries()]
    .sort((a, b) => b[1] - a[1])
    .slice(0, sinir)
    .map(([deger, sayi]) => ({ deger, sayi }));
}
