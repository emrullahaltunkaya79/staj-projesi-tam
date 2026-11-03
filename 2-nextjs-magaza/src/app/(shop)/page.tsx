import { Zap, Star, Leaf, Sun, Snowflake, Flower2, type LucideIcon } from "lucide-react";
import Hero from "@/components/Hero";
import { KategoriSeridi } from "@/components/KategoriSeridi";
import { KoleksiyonSeridi } from "@/components/KoleksiyonSeridi";
import { KatalogIcerik } from "@/components/KatalogIcerik";
import { VitrinSeridi } from "@/components/VitrinSeridi";
import { urunler, indirimliFiyat, type Urun } from "@/lib/backoffice";
import { sezonUrunleri, mevsimBul, MEVSIMLER, type Mevsim } from "@/lib/sezonlar";
import { outletUrunler, sonKalanUrunler, sonFirsatUrunler } from "@/lib/koleksiyonlar";

const MEVSIM_IKONLARI: Record<Mevsim, LucideIcon> = {
  ilkbahar: Flower2,
  yaz: Sun,
  sonbahar: Leaf,
  kis: Snowflake,
};

export const dynamic = "force-dynamic";

export default async function Home() {
  let tumu: Awaited<ReturnType<typeof urunler>> = [];
  let hata: string | null = null;

  try {
    tumu = await urunler();
  } catch (e) {
    hata = e instanceof Error ? e.message : "Katalog yüklenemedi.";
  }

  // Flaş İndirimler: indirim oranı, eşitlikte kazanç tutarı büyük olan önce
  const tasarruf = (u: Urun) => u.sale_price - indirimliFiyat(u);
  const flasUrunler = tumu
    .filter((u) => (u.discount_percent ?? 0) > 0 && u.in_stock)
    .sort((a, b) => (b.discount_percent ?? 0) - (a.discount_percent ?? 0) || tasarruf(b) - tasarruf(a))
    .slice(0, 12);

  // En Çok Satanlar: arka ofis fatura satırlarından hesaplanan bayrak
  const cokSatanlar = tumu.filter((u) => u.is_bestseller).slice(0, 12);

  // Sezon vitrini: bulunduğun ayın mevsimine göre öneriler
  const mevsim = mevsimBul();
  const sezonHepsi = sezonUrunleri(tumu, mevsim);
  const sezonUrunleri12 = sezonHepsi.slice(0, 12);

  // Koleksiyon sayaçları
  const outletSayisi = outletUrunler(tumu).length;
  const sonKalanSayisi = sonKalanUrunler(tumu).length;
  const sonFirsatSayisi = sonFirsatUrunler(tumu).length;

  return (
    <div>
      <Hero />

      {!hata && flasUrunler.length > 0 && (
        <VitrinSeridi
          baslik="Flaş İndirimler"
          ikon={Zap}
          tur="flas"
          urunler={flasUrunler}
          geriSayim
          aciklama={`%${Math.max(...flasUrunler.map((u) => u.discount_percent ?? 0))}'e kadar`}
          tumunuGor="/flas-indirimler"
        />
      )}

      {!hata && (
        <KategoriSeridi
          kategoriler={Object.entries(
            tumu.reduce<Record<string, number>>((m, u) => {
              const ad = u.category ?? "Diğer";
              m[ad] = (m[ad] ?? 0) + 1;
              return m;
            }, {}),
          )
            .sort((a, b) => a[0].localeCompare(b[0], "tr"))
            .map(([ad, sayi]) => ({ ad, sayi }))}
        />
      )}

      {!hata && (
        <KoleksiyonSeridi
          outletSayisi={outletSayisi}
          sonKalanSayisi={sonKalanSayisi}
          sonFirsatSayisi={sonFirsatSayisi}
          sezonSayisi={sezonHepsi.length}
        />
      )}

      {!hata && cokSatanlar.length > 0 && (
        <VitrinSeridi
          baslik="En Çok Satanlar"
          ikon={Star}
          tur="cokSatan"
          urunler={cokSatanlar}
          aciklama="Satış verilerinden"
          tumunuGor="/cok-satanlar"
        />
      )}

      {!hata && sezonUrunleri12.length > 0 && (
        <VitrinSeridi
          baslik={`${MEVSIMLER[mevsim].ad} Önerileri`}
          ikon={MEVSIM_IKONLARI[mevsim]}
          tur="sezon"
          urunler={sezonUrunleri12}
          aciklama={MEVSIMLER[mevsim].aciklama}
          tumunuGor="/sezon"
        />
      )}

      {hata ? (
        <div className="px-5 pb-20 sm:px-10">
          <div className="mx-auto max-w-7xl border-b border-black/20 pb-5">
            <h2 className="text-[36px] font-black leading-none tracking-[-0.03em] text-black/40">Katalog</h2>
          </div>
          <p className="mt-8 text-sm text-black/50">{hata}</p>
        </div>
      ) : (
        <KatalogIcerik tumUrunler={tumu} kategori={null} baslik="Tüm Ürünler" />
      )}
    </div>
  );
}
