/** Adres verisi: kargo bölgesi il/ilçeden belirlenir (İstanbul'da yaka ilçeye göre). */

export const ILLER = [
  "Adana", "Adıyaman", "Afyonkarahisar", "Ağrı", "Aksaray", "Amasya", "Ankara", "Antalya", "Ardahan", "Artvin",
  "Aydın", "Balıkesir", "Bartın", "Batman", "Bayburt", "Bilecik", "Bingöl", "Bitlis", "Bolu", "Burdur",
  "Bursa", "Çanakkale", "Çankırı", "Çorum", "Denizli", "Diyarbakır", "Düzce", "Edirne", "Elazığ", "Erzincan",
  "Erzurum", "Eskişehir", "Gaziantep", "Giresun", "Gümüşhane", "Hakkari", "Hatay", "Iğdır", "Isparta", "İstanbul",
  "İzmir", "Kahramanmaraş", "Karabük", "Karaman", "Kars", "Kastamonu", "Kayseri", "Kırıkkale", "Kırklareli", "Kırşehir",
  "Kilis", "Kocaeli", "Konya", "Kütahya", "Malatya", "Manisa", "Mardin", "Mersin", "Muğla", "Muş",
  "Nevşehir", "Niğde", "Ordu", "Osmaniye", "Rize", "Sakarya", "Samsun", "Siirt", "Sinop", "Sivas",
  "Şanlıurfa", "Şırnak", "Tekirdağ", "Tokat", "Trabzon", "Tunceli", "Uşak", "Van", "Yalova", "Yozgat", "Zonguldak",
];

export const ISTANBUL_ILCELERI = [
  "Adalar", "Arnavutköy", "Ataşehir", "Avcılar", "Bağcılar", "Bahçelievler", "Bakırköy", "Başakşehir", "Bayrampaşa",
  "Beşiktaş", "Beykoz", "Beylikdüzü", "Beyoğlu", "Büyükçekmece", "Çatalca", "Çekmeköy", "Esenler", "Esenyurt",
  "Eyüpsultan", "Fatih", "Gaziosmanpaşa", "Güngören", "Kadıköy", "Kağıthane", "Kartal", "Küçükçekmece", "Maltepe",
  "Pendik", "Sancaktepe", "Sarıyer", "Silivri", "Sultanbeyli", "Sultangazi", "Şile", "Şişli", "Tuzla", "Ümraniye",
  "Üsküdar", "Zeytinburnu",
];

const ANADOLU_YAKASI = new Set([
  "Adalar", "Ataşehir", "Beykoz", "Çekmeköy", "Kadıköy", "Kartal", "Maltepe", "Pendik", "Sancaktepe",
  "Sultanbeyli", "Şile", "Tuzla", "Ümraniye", "Üsküdar",
]);

const IL_BOLGESI: Record<string, string> = {
  Ankara: "ankara", İzmir: "izmir", Bursa: "bursa", Antalya: "antalya", Adana: "adana", Konya: "konya",
};

/** KARGO_BOLGELERI anahtarı; il seçilmediyse null. */
export function kargoBolgesi(il: string, ilce: string): string | null {
  if (!il) return null;
  if (il === "İstanbul") return ilce ? (ANADOLU_YAKASI.has(ilce) ? "istanbul-anadolu" : "istanbul-avr") : null;
  return IL_BOLGESI[il] ?? "diger";
}

/** Türkiye cep telefonu: 05XX XXX XX XX biçimine getirir; geçersizse null. */
export function telefonDuzelt(girdi: string): string | null {
  let rakam = girdi.replace(/\D/g, "");
  if (rakam.startsWith("90")) rakam = rakam.slice(2);
  if (rakam.length === 10 && rakam.startsWith("5")) rakam = `0${rakam}`;
  if (!/^05\d{9}$/.test(rakam)) return null;
  return `${rakam.slice(0, 4)} ${rakam.slice(4, 7)} ${rakam.slice(7, 9)} ${rakam.slice(9)}`;
}
