export type CerezKategorisi = "fonksiyonel" | "analitik" | "pazarlama";

export type CerezSecimi = {
  fonksiyonel: boolean;
  analitik: boolean;
  pazarlama: boolean;
  /** Rıza tarihi (ISO) — KVKK kayıt yükümlülüğü için saklanır */
  tarih: string;
};

const ANAHTAR = "hba-cerez-onayi";

/**
 * Kayıtlı çerez tercihini okur. Eski ikili kaydı ("tam" / "zorunlu") yeni
 * kategori modeline çevirir. Kayıt yoksa null döner — banner gösterilir.
 */
export function cerezSecimi(): CerezSecimi | null {
  if (typeof window === "undefined") return null;
  try {
    const ham = localStorage.getItem(ANAHTAR);
    if (!ham) return null;
    if (ham === "tam") return { fonksiyonel: true, analitik: true, pazarlama: true, tarih: "eski-kayit" };
    if (ham === "zorunlu") return { fonksiyonel: false, analitik: false, pazarlama: false, tarih: "eski-kayit" };
    const veri = JSON.parse(ham) as Partial<CerezSecimi>;
    if (typeof veri !== "object" || veri === null) return null;
    return {
      fonksiyonel: Boolean(veri.fonksiyonel),
      analitik: Boolean(veri.analitik),
      pazarlama: Boolean(veri.pazarlama),
      tarih: String(veri.tarih ?? ""),
    };
  } catch {
    return null;
  }
}

/** Zorunlu çerezler her zaman açıktır; diğerleri yalnızca rızayla. */
export function cerezKategorisiAktif(kategori: CerezKategorisi): boolean {
  if (kategori === "fonksiyonel" || kategori === "analitik" || kategori === "pazarlama") {
    return cerezSecimi()?.[kategori] ?? false;
  }
  return true;
}

/** Kullanıcının tercihini kaydeder ve tüm sayfalara haber verir. */
export function cerezKaydet(secim: Omit<CerezSecimi, "tarih">): CerezSecimi {
  const kayit: CerezSecimi = { ...secim, tarih: new Date().toISOString() };
  try {
    localStorage.setItem(ANAHTAR, JSON.stringify(kayit));
  } catch {}
  window.dispatchEvent(new Event("hba-cerez-guncellendi"));
  return kayit;
}
