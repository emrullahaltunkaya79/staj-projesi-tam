// Ürün görseli ikili önbelleği — /api/img route'u ile webhook işleyicisi ortak kullanır.
// Panelde görsel değiştiğinde backoffice product.upserted webhook'u atar; işleyici
// gorselOnbellekTemizle ile ilgili ürünün (veya tamamının) önbellek girdisini düşürür
// ve mağaza, yeni görseli 10 dk TTL beklemeden hemen sunar.
//
// ÖNEMLİ: Harita globalThis'te tutulur — Turbopack rotaları ayrı paketlediğinde
// modülün iki kopyası oluşabilir; globalThis sayesinde tüm kopyalar AYNI haritayı
// paylaşır ve webhook'taki temizleme /api/img route'unu da etkiler.

const g = globalThis as { __gorselOnbellek?: Map<string, { data: Buffer; type: string; zaman: number }> };
const onbellek = (g.__gorselOnbellek ??= new Map());
const ONBELLEK_SURESI = 10 * 60 * 1000;
const ONBELLEK_KAPASITESI = 500;

export function onbellektenGetir(anahtar: string) {
  const kayit = onbellek.get(anahtar);
  if (kayit && Date.now() - kayit.zaman < ONBELLEK_SURESI) {
    return kayit;
  }
  onbellek.delete(anahtar);
  return null;
}

export function onbellegeYaz(anahtar: string, data: Buffer, type: string): void {
  if (onbellek.size >= ONBELLEK_KAPASITESI) {
    // En eski dörtte birini budur — tüm haritayı boşaltmak her seferinde
    // backoffice'e görsel patlaması yaratıyordu
    const silinecek = [...onbellek.entries()]
      .sort((a, b) => a[1].zaman - b[1].zaman)
      .slice(0, Math.ceil(ONBELLEK_KAPASITESI / 4));
    for (const [anahtar] of silinecek) onbellek.delete(anahtar);
  }
  onbellek.set(anahtar, { data, type, zaman: Date.now() });
}

/** Ürünün tüm görsellerini (ana + galeri) önbellekten düşürür; id yoksa tümünü temizler. */
export function gorselOnbellekTemizle(urunId?: number): number {
  if (urunId === undefined) {
    const adet = onbellek.size;
    onbellek.clear();
    return adet;
  }
  let adet = 0;
  const onek = `${urunId}:`;
  for (const anahtar of [...onbellek.keys()]) {
    if (anahtar.startsWith(onek)) {
      onbellek.delete(anahtar);
      adet++;
    }
  }
  return adet;
}
