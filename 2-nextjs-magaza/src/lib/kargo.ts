/**
 * Kargo ve iade kuralları — ödeme, ürün sayfası ve iade politikası aynı kaynağı okur.
 */

export const UCRETSIZ_KARGO_LIMITI = 2000;

/** Bu saate kadar verilen siparişler aynı iş günü kargoya verilir. */
export const KARGO_KESIM_SAATI = 14;

/** Cayma hakkı: teslimden itibaren gün sayısı (arka ofiste Order::IADE_SURESI_GUN). */
export const IADE_SURESI_GUN = 14;

export const KARGO_BOLGELERI: { id: string; ad: string; fiyat: number; gun: [number, number] }[] = [
  { id: "istanbul-avr", ad: "İstanbul (Avrupa)", fiyat: 29, gun: [1, 1] },
  { id: "istanbul-anadolu", ad: "İstanbul (Anadolu)", fiyat: 34, gun: [1, 1] },
  { id: "ankara", ad: "Ankara", fiyat: 39, gun: [1, 2] },
  { id: "izmir", ad: "İzmir", fiyat: 42, gun: [1, 2] },
  { id: "bursa", ad: "Bursa", fiyat: 39, gun: [1, 2] },
  { id: "antalya", ad: "Antalya", fiyat: 45, gun: [2, 3] },
  { id: "adana", ad: "Adana", fiyat: 45, gun: [2, 3] },
  { id: "konya", ad: "Konya", fiyat: 42, gun: [2, 3] },
  { id: "diger", ad: "Diğer Şehirler", fiyat: 55, gun: [2, 3] },
];

export function teslimSuresi([enAz, enCok]: [number, number]): string {
  return enAz === enCok ? `${enAz} iş günü` : `${enAz}-${enCok} iş günü`;
}

function isGunuEkle(tarih: Date, gun: number): Date {
  const sonuc = new Date(tarih);
  let kalan = gun;
  while (kalan > 0) {
    sonuc.setDate(sonuc.getDate() + 1);
    if (sonuc.getDay() !== 0 && sonuc.getDay() !== 6) kalan--;
  }
  return sonuc;
}

/** Kargoya veriliş günü ve tahmini teslim aralığı (hafta sonları sayılmaz). */
export function tahminiTeslimat(simdi: Date, [enAz, enCok]: [number, number] = [1, 3]) {
  const haftaSonu = simdi.getDay() === 0 || simdi.getDay() === 6;
  const bugunKargoda = !haftaSonu && simdi.getHours() < KARGO_KESIM_SAATI;
  const kargoGunu = bugunKargoda ? new Date(simdi) : isGunuEkle(simdi, 1);

  return {
    bugunKargoda,
    kargoGunu,
    enErken: isGunuEkle(kargoGunu, enAz),
    enGec: isGunuEkle(kargoGunu, enCok),
  };
}
