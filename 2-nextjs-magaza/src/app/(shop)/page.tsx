import { Zap, Star, Leaf, Sun, Snowflake, Flower2, Sparkles, type LucideIcon } from "lucide-react";
import Hero from "@/components/Hero";
import { KategoriSeridi } from "@/components/KategoriSeridi";
import { MarkaSeridi } from "@/components/MarkaSeridi";
import { KoleksiyonSeridi } from "@/components/KoleksiyonSeridi";
import { KatalogIcerik } from "@/components/KatalogIcerik";
import { VitrinSeridi } from "@/components/VitrinSeridi";
import { FlasSeridi } from "@/components/FlasSeridi";
import { GuvenKartlari } from "@/components/GuvenKartlari";
import { GununFirsati } from "@/components/GununFirsati";
import { YorumVitrini } from "@/components/YorumVitrini";
import { SonGoruntulenenler } from "@/components/SonGoruntulenenler";
import { KargoTakip } from "@/components/KargoTakip";
import { urunler, indirimliFiyat, sonYorumlar, type Urun } from "@/lib/backoffice";
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

  // Günün Fırsatı: stok azalmış, indirimi yüksek ürün (yoksa en yüksek indirimli)
  const gununFirsatiUrun =
    tumu
      .filter((u) => u.in_stock && (u.discount_percent ?? 0) >= 20 && u.available_quantity <= 10)
      .sort((a, b) => (b.discount_percent ?? 0) - (a.discount_percent ?? 0))[0] ?? flasUrunler[0];

  // Yeni Gelenler: en son eklenen ürünler
  const yeniGelenler = [...tumu].sort((a, b) => b.id - a.id).slice(0, 12);
  const yorumlar = await sonYorumlar();

  // Markalar: ürün sayısına göre en çok ürünü olanlar önde (şeritte ilk 20)
  const markaSayilari = tumu.reduce<Record<string, number>>((m, u) => {
    if (u.brand) m[u.brand] = (m[u.brand] ?? 0) + 1;
    return m;
  }, {});
  const tumMarkalar = Object.entries(markaSayilari)
    .map(([ad, sayi]) => ({ ad, sayi }))
    .sort((a, b) => b.sayi - a.sayi || a.ad.localeCompare(b.ad, "tr"));

  // Kategori kartlarındaki açılır marka listeleri
  const kategoriMarkalari: Record<string, { ad: string; sayi: number }[]> = {};
  for (const u of tumu) {
    if (!u.category || !u.brand) continue;
    (kategoriMarkalari[u.category] ??= []);
    const mevcut = kategoriMarkalari[u.category].find((m) => m.ad === u.brand);
    if (mevcut) mevcut.sayi += 1;
    else kategoriMarkalari[u.category].push({ ad: u.brand, sayi: 1 });
  }
  for (const liste of Object.values(kategoriMarkalari)) {
    liste.sort((a, b) => b.sayi - a.sayi || a.ad.localeCompare(b.ad, "tr"));
  }

  return (
    <div>
      <Hero />

      {/* Güven şeridi — hızlı teslimat / güvenli ödeme / iade */ }
      {!hata && (
        <section className="px-5 pt-5 sm:px-10">
          <div className="mx-auto max-w-7xl">
            <GuvenKartlari kompakt />
          </div>
        </section>
      )}

      {/* Günün Fırsatı — tek büyük fırsat bloğu */}
      {!hata && gununFirsatiUrun && <GununFirsati urun={gununFirsatiUrun} />}

      {/* Kargo takip widget'ı */}
      <section className="px-5 pt-3 sm:px-10">
        <div className="mx-auto max-w-7xl">
          <KargoTakip />
        </div>
      </section>

      {/* 1. Kampanya vitrini — geri sayımlı flaş indirimler */}
      {!hata && flasUrunler.length > 0 && (
        <FlasSeridi
          urunler={flasUrunler}
          aciklama={`%${Math.max(...flasUrunler.map((u) => u.discount_percent ?? 0))}'e kadar`}
        />
      )}

      {/* 2. Kampanya kısayol kutuları */}
      {!hata && (
        <KoleksiyonSeridi
          outletSayisi={outletSayisi}
          sonKalanSayisi={sonKalanSayisi}
          sonFirsatSayisi={sonFirsatSayisi}
          sezonSayisi={sezonHepsi.length}
        />
      )}

      {/* 3. Kategori vitrini (kartlarda açılır markalar) */}
      {!hata && (
        <KategoriSeridi kategoriler={
          Object.entries(
            tumu.reduce<Record<string, number>>((m, u) => {
              const ad = u.category ?? "Diğer";
              m[ad] = (m[ad] ?? 0) + 1;
              return m;
            }, {}),
          )
            .sort((a, b) => a[0].localeCompare(b[0], "tr"))
            .map(([ad, sayi]) => ({ ad, sayi }))
        } markalar={kategoriMarkalari} />
      )}

      {/* 4. Güven verisi: çok satanlar */}
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

      {/* Yeni Gelenler */}
      {!hata && yeniGelenler.length > 0 && (
        <VitrinSeridi
          baslik="Yeni Gelenler"
          ikon={Sparkles}
          tur="sezon"
          urunler={yeniGelenler}
          aciklama="Kataloğa en son eklenenler"
        />
      )}

      {/* Müşteriler Ne Diyor — onaylı gerçek yorumlar */}
      {!hata && <YorumVitrini yorumlar={yorumlar} />}

      {/* 5. Sezon önerileri */}
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

      {/* 6. Markalar — keşfin kapanışı */}
      {!hata && <MarkaSeridi markalar={tumMarkalar.slice(0, 20)} />}

      {/* Son baktıklarınız */}
      <SonGoruntulenenler />

      {/* 7. Tam katalog — temiz grid (filtreler kategori/kampanya sayfalarında) */}
      {hata ? (
        <div className="px-5 pb-20 sm:px-10">
          <div className="mx-auto max-w-7xl border-b border-black/20 pb-5">
            <h2 className="text-[36px] font-black leading-none tracking-[-0.03em] text-black/40">Katalog</h2>
          </div>
          <p className="mt-8 text-sm text-black/50">{hata}</p>
        </div>
      ) : (
        <KatalogIcerik tumUrunler={tumu} kategori={null} baslik="Tüm Ürünler" sidebar={false} />
      )}
    </div>
  );
}
