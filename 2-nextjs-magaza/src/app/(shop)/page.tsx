
import Hero from "@/components/Hero";
import { KatalogIcerik } from "@/components/KatalogIcerik";
import { VitrinSeridi } from "@/components/VitrinSeridi";
import { FlasSeridi } from "@/components/FlasSeridi";
import { SonGoruntulenenler } from "@/components/SonGoruntulenenler";
import { BegendiklerineBenzer } from "@/components/BegendiklerineBenzer";
import { urunler, indirimliFiyat, type Urun } from "@/lib/backoffice";

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

  // Yeni Gelenler: en son eklenen ürünler
  const yeniGelenler = [...tumu].sort((a, b) => b.id - a.id).slice(0, 12);

  // Zengin sonuç şemaları: mağaza kimliği + site içi arama (Google SearchAction)
  const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL ?? "http://127.0.0.1:3001";
  const kurumsalJsonLd = {
    "@context": "https://schema.org",
    "@type": "OnlineStore",
    name: "HepsiBirarada",
    url: SITE_URL,
    logo: `${SITE_URL}/icon-512.png`,
    description: "Teknoloji & Ofis — her şey tek yerde. Aynı gün kargo, 14 gün koşulsuz iade.",
  };
  const siteJsonLd = {
    "@context": "https://schema.org",
    "@type": "WebSite",
    name: "HepsiBirarada",
    url: SITE_URL,
    potentialAction: {
      "@type": "SearchAction",
      target: `${SITE_URL}/ara?q={search_term_string}`,
      "query-input": "required name=search_term_string",
    },
  };

  return (
    <div>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(kurumsalJsonLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(siteJsonLd) }} />
      <Hero />

      {/* 1. Kampanya vitrini — geri sayımlı flaş indirimler */}
      {!hata && flasUrunler.length > 0 && (
        <FlasSeridi
          urunler={flasUrunler}
          aciklama={`%${Math.max(...flasUrunler.map((u) => u.discount_percent ?? 0))}'e kadar`}
        />
      )}

      {/* 4. Güven verisi: çok satanlar */}
      {!hata && cokSatanlar.length > 0 && (
        <VitrinSeridi
          baslik="En Çok Satanlar"
          ikon="star"
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
          ikon="sparkles"
          tur="sezon"
          urunler={yeniGelenler}
          aciklama="Kataloğa en son eklenenler"
        />
      )}

      {/* Son baktıklarınız */}
      <SonGoruntulenenler />

      {/* Favori bazlı öneriler — giriş yapmış ve favorisi olan kullanıcıya */}
      {!hata && <BegendiklerineBenzer tumu={tumu} />}

      {/* 7. Tam katalog — temiz grid (filtreler kategori/kampanya sayfalarında) */}
      {hata ? (
        <div className="px-5 pb-20 sm:px-10">
          <div className="mx-auto max-w-7xl border-b border-black/20 pb-5">
            <h2 className="text-[17px] font-black uppercase tracking-wide text-black/40">Katalog</h2>
          </div>
          <p className="mt-8 text-sm text-black/50">{hata}</p>
        </div>
      ) : (
        <KatalogIcerik tumUrunler={tumu} kategori={null} baslik="Tüm Ürünler" sidebar={false} />
      )}
    </div>
  );
}
