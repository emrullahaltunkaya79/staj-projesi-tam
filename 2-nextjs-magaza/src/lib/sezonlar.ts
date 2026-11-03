import type { Urun } from "./backoffice";

export type Mevsim = "ilkbahar" | "yaz" | "sonbahar" | "kis";

/** Mevsime göre vitrin — anahtar kelimeler mevcut katalogdaki gerçek ürünlere göre seçildi. */
export const MEVSIMLER: Record<Mevsim, { ad: string; emoji: string; aciklama: string; kelimeler: string[] }> = {
  ilkbahar: {
    ad: "İlkbahar",
    emoji: "🌸",
    aciklama: "temizlik ve yenilenme zamanı",
    kelimeler: [
      "temizlik", "steriliz", "organiz", "havlu", "raf", "duvar saati",
      "ayakkabılık", "minder", "yoga", "pilates", "çöp kutusu", "yüz temizleme",
    ],
  },
  yaz: {
    ad: "Yaz",
    emoji: "☀️",
    aciklama: "tatil ve açık hava sezonu",
    kelimeler: [
      "güneş", "vantilatör", "fan", "bisiklet", "seyahat", "valiz",
      "matara", "selfie", "drone", "aksiyon kamera", "sırt çantası", "nem alma",
    ],
  },
  sonbahar: {
    ad: "Sonbahar",
    emoji: "🍂",
    aciklama: "okul ve ofis dönüşü",
    kelimeler: [
      "defter", "kalem", "not", "post-it", "ofis", "masa ", "masaüstü",
      "laptop", "monitör", "klavye", "sandalye", "dosya", "lamba",
    ],
  },
  kis: {
    ad: "Kış",
    emoji: "❄️",
    aciklama: "sıcak ve konforlu günler",
    kelimeler: ["ısıtıcı", "ısıtmalı", "termos", "mum", "battaniye", "difüzör", "masaj"],
  },
};

export function mevsimBul(tarih = new Date()): Mevsim {
  const ay = tarih.getMonth() + 1;
  if (ay >= 6 && ay <= 8) return "yaz";
  if (ay >= 3 && ay <= 5) return "ilkbahar";
  if (ay >= 9 && ay <= 11) return "sonbahar";
  return "kis";
}

/** Bulunulan mevsimin anahtar kelimeleriyle eşleşen ürünler. */
export function sezonUrunleri(urunler: Urun[], mevsim: Mevsim = mevsimBul()): Urun[] {
  const kelimeler = MEVSIMLER[mevsim].kelimeler;
  return urunler.filter((u) => {
    const ad = u.name.toLocaleLowerCase("tr");
    return kelimeler.some((k) => ad.includes(k));
  });
}
