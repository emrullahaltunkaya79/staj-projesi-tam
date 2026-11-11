import Link from "next/link";
import { urun, urunler, ayniAldilar, indirimliFiyat, type Urun } from "@/lib/backoffice";
import { GuvenKartlari } from "@/components/GuvenKartlari";
import { Yorumlar } from "@/components/Yorumlar";
import { UrunDetayOzellikleri } from "@/components/UrunDetayOzellikleri";
import { SonGoruntulenenler } from "@/components/SonGoruntulenenler";
import { BirlikteAlinanlar, type PaketUrunu } from "@/components/BirlikteAlinanlar";
import { UrunSorulari } from "@/components/UrunSorulari";
import { FiyatAlarmi } from "@/components/FiyatAlarmi";
import { KarsilastirEkle, KarsilastirCubugu } from "@/components/KarsilastirCubugu";

const ACIKLAMALAR: Record<string, string> = {
  Bilgisayar: "Günlük iş akışınızı hızlandıran, ergonomik ve dayanıklı bilgisayar donanımları. İki yıl garanti dahil.",
  Elektronik: "Ses ve görüntü deneyiminizi yükselten, pil ömrü uzun, kompakt elektronik ürünler. Şarj kablosu kutu içeriğindedir.",
  Kırtasiye: "Ofis masanızın vazgeçilmezi, kaliteli malzemeden üretilmiş kırtasiye ürünleri. Toplu alımlarda stok fiyatı uygulanır.",
  Ofis: "Uzun çalışma saatlerini konforlu hale getiren, sağlam gövdeli ofis ürünleri. Kolay montaj kılavuzu kutudan çıkar.",
};

export async function generateMetadata({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const u = await urun(Number(id));
  if (!u) return { title: "Ürün bulunamadı — HepsiBirarada" };
  const fiyat = new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(
    u.final_price ?? u.sale_price,
  );
  return {
    title: `${u.name} — HepsiBirarada`,
    description: `${u.name}${u.brand ? ` (${u.brand})` : ""} — ${fiyat} ₺. ${ACIKLAMALAR[u.category ?? ""] ?? "Sade ve zamansız tasarım, kaliteli malzeme."} Aynı gün kargo, 14 gün içinde ücretsiz iade.`,
    openGraph: {
      title: `${u.name} — ${fiyat} ₺`,
      description: u.name,
      images: [`/api/img/${u.id}`],
    },
  };
}

export default async function UrunSayfasi({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const u = await urun(Number(id));

  if (!u) {
    return (
      <div className="flex min-h-[40vh] items-center justify-center">
        <p className="text-sm text-black/50">Ürün bulunamadı.</p>
      </div>
    );
  }

  const aciklama = ACIKLAMALAR[u.category ?? ""] ?? "Sade ve zamansız tasarım, kaliteli malzeme.";
  const [ortakSatinAlmalar, tumUrunler] = await Promise.all([ayniAldilar(u.id), urunler()]);

  // Çapraz satış adayları katalogdan okunur: ortak satın alma listesi varyant bilgisi taşımaz
  const katalog = new Map(tumUrunler.map((x) => [x.id, x]));
  const sepeteHazir = (x: Urun) => x.id !== u.id && x.in_stock && !x.varyantlar?.length;
  const anaFiyat = indirimliFiyat(u);

  // Birlikte alınanlar: gerçek fatura birlikteliği; yoksa aynı kategorinin daha uygun fiyatlı ürünleri
  const ortaklar = ortakSatinAlmalar
    .map((o) => katalog.get(o.id))
    .filter((x): x is Urun => !!x && sepeteHazir(x))
    .slice(0, 2);
  const paket = ortaklar.length > 0
    ? ortaklar
    : tumUrunler
        .filter((x) => sepeteHazir(x) && x.category === u.category && indirimliFiyat(x) < anaFiyat)
        .sort((a, b) => Number(!!b.is_bestseller) - Number(!!a.is_bestseller) || (b.rating_avg ?? 0) - (a.rating_avg ?? 0))
        .slice(0, 2);
  const paketGoster = u.in_stock && !katalog.get(u.id)?.varyantlar?.length && paket.length > 0;
  const paketUrunu = (x: Urun): PaketUrunu => ({
    id: x.id,
    ad: x.name,
    marka: x.brand ?? null,
    fiyat: indirimliFiyat(x),
    gorsel: x.image_url,
    birim: x.unit,
    musait: x.available_quantity,
  });

  // Bunları da beğenebilirsiniz: aynı kategoride fiyatı en yakın ürünler + bir üst segment (up-sell)
  const paketteki = new Set(paket.map((x) => x.id));
  const adaylar = tumUrunler.filter((x) => x.id !== u.id && !paketteki.has(x.id) && x.in_stock && x.category === u.category);
  const yakinlar = [...adaylar]
    .sort((a, b) => Math.abs(indirimliFiyat(a) - anaFiyat) - Math.abs(indirimliFiyat(b) - anaFiyat))
    .slice(0, 4);
  const ustSegment = adaylar
    .filter((x) => !yakinlar.includes(x) && indirimliFiyat(x) > anaFiyat)
    .sort((a, b) => (b.rating_avg ?? 0) - (a.rating_avg ?? 0))
    .slice(0, 2);
  const benzerler = [...yakinlar, ...ustSegment];
  if (benzerler.length < 6) {
    benzerler.push(
      ...tumUrunler
        .filter((x) => x.id !== u.id && !paketteki.has(x.id) && x.in_stock && x.is_bestseller && !benzerler.includes(x))
        .slice(0, 6 - benzerler.length),
    );
  }

  // Google zengin sonuçları için Product schema
  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "Product",
    name: u.name,
    description: `${u.name}${u.brand ? ` — ${u.brand}` : ""} — HepsiBirarada mağazasında ${new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(u.final_price ?? u.sale_price)} ₺`,
    image: [`/api/img/${u.id}`],
    brand: { "@type": "Brand", name: u.brand ?? "HepsiBirarada" },
    sku: u.code ?? String(u.id),
    ...(u.rating_avg != null && {
      aggregateRating: { "@type": "AggregateRating", ratingValue: u.rating_avg, reviewCount: Math.max(1, u.review_count ?? 1) },
    }),
    offers: {
      "@type": "Offer",
      price: u.final_price ?? u.sale_price,
      priceCurrency: "TRY",
      availability: u.in_stock ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
    },
  };

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10"><div className="mx-auto max-w-7xl">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />
      <div className="px-5 pb-6 pt-8 sm:px-10">
        <div className="mx-auto max-w-7xl text-[13px] font-medium text-black/50">
          <a href="/" className="hover:underline underline-offset-4">Mağaza</a>
          <span className="mx-2">/</span>
          {u.category && (
            <>
              <Link href={`/kategori/${encodeURIComponent(u.category)}`} className="hover:underline underline-offset-4">
                {u.category}
              </Link>
              <span className="mx-2">/</span>
            </>
          )}
          <span className="font-semibold text-black/70">{u.name}</span>
        </div>
      </div>

      <section className="px-5 pb-10 sm:px-10">
        <div className="mx-auto grid max-w-7xl grid-cols-1 gap-12 md:grid-cols-2 [&>*]:min-w-0">
          <UrunDetayOzellikleri urun={u} />

          <div className="flex flex-col">
            <div className="mt-2 flex items-center gap-2 text-[13px] text-black/40">
              {u.rating_avg != null ? (
                <>
                  <span className="flex text-amber-500">{"★".repeat(Math.round(u.rating_avg))}<span className="text-black/15">{"★".repeat(5 - Math.round(u.rating_avg))}</span></span>
                  <span className="font-bold text-black">{u.rating_avg.toFixed(1)}</span>
                  <a href="#yorumlar" className="underline-offset-2 hover:underline">({u.review_count ?? 0} değerlendirme)</a>
                </>
              ) : (
                <span>Henüz değerlendirme yok</span>
              )}
            </div>

            <KarsilastirEkle urun={{ id: u.id, ad: u.name, kategori: u.category, fiyat: u.final_price ?? u.sale_price, marka: u.brand ?? null, puan: u.rating_avg ?? null, stokta: u.in_stock, gorsel: u.image_url }} />
            <FiyatAlarmi urunId={u.id} />

            <p className="mt-8 max-w-md text-[15px] leading-relaxed text-black/70">{aciklama}</p>

            <div className="mt-auto space-y-5">
              <div className="space-y-3 border-t border-black/15 pt-6 text-[13px] leading-relaxed text-black/60">
                <p>Sipariş verdiğinizde stok arka ofiste 30 dakika boyunca sizin adınıza rezerve edilir.</p>
                <p>Ödeme onayında faturanız otomatik kesilir; kargoya verildiğinde takip bağlantısı sipariş sayfanızda görünür.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Yakında İnceledikleriniz — iki kolonun altında tam genişlik şerit (grid içinde kalırsa sağ kolona biniyordu) */}
      <SonGoruntulenenler suAnki={{
        id: u.id,
        ad: u.name,
        fiyat: u.final_price ?? u.sale_price,
        gorsel: u.id ? `/api/img/${u.id}` : null,
      }} />

      {/* Güven kartları */}
      <div className="px-5 sm:px-10">
        <div className="mx-auto max-w-7xl">
          <GuvenKartlari ince />
        </div>
      </div>
      {/* Gerçek değerlendirmeler */}
      <div id="yorumlar" className="scroll-mt-36">
        <Yorumlar urunId={u.id} />
      </div>

      {/* Ürün soru-cevap */}
      <UrunSorulari urunId={u.id} />

      {/* Karşılaştırma çubuğu (≥2 ürün seçiliyse görünür) */}
      <KarsilastirCubugu />

      {/* Çapraz satış 1 — birlikte sıkça alınanlar paketi */}
      {paketGoster && (
        <div className="px-5 pt-10 sm:px-10">
          <div className="mx-auto max-w-7xl">
            <BirlikteAlinanlar
              kaynak={ortaklar.length > 0 ? "ortak" : "tamamlayici"}
              ana={paketUrunu(u)}
              ekler={paket.map(paketUrunu)}
            />
          </div>
        </div>
      )}

      {/* Çapraz satış 2 — benzer ürünler ve bir üst segment */}
      {benzerler.length > 0 && (
        <div className="px-5 pt-12 sm:px-10">
          <div className="mx-auto max-w-7xl border-t border-black/10 pt-8">
            <div className="flex items-baseline justify-between gap-3">
              <h2 className="text-[15px] font-black uppercase tracking-wide text-black">Bunları da beğenebilirsiniz</h2>
              <span className="text-[12px] font-medium text-black/35">Benzer ve bir üst segment ürünler</span>
            </div>
            <div className="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
              {benzerler.map((b) => (
                <Link key={b.id} href={`/urun/${b.id}`} className="group block">
                  <div className="flex aspect-square items-center justify-center overflow-hidden bg-[#f4f4f4]">
                    {b.image_url ? (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img src={b.image_url} alt={b.name} loading="lazy" className="h-full w-full object-contain p-3 transition-transform duration-300 group-hover:scale-[1.04]" />
                    ) : (
                      <span className="text-4xl font-black text-black/10">{b.name.charAt(0)}</span>
                    )}
                  </div>
                  {b.brand && <p className="mt-2 text-[9px] font-black uppercase tracking-widest text-black/40">{b.brand}</p>}
                  <p className="mt-0.5 line-clamp-2 text-[12px] font-semibold leading-snug text-black group-hover:underline">{b.name}</p>
                  <p className="mt-1 text-[13px] font-bold tabular-nums text-black">
                    {new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(indirimliFiyat(b))} ₺
                  </p>
                </Link>
              ))}
            </div>
          </div>
        </div>
      )}

    </div></div>
  );
}
