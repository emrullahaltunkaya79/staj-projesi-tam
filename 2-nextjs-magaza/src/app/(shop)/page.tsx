import { Zap, Star } from "lucide-react";
import Hero from "@/components/Hero";
import { KategoriSeridi } from "@/components/KategoriSeridi";
import { KatalogIcerik } from "@/components/KatalogIcerik";
import { VitrinSeridi } from "@/components/VitrinSeridi";
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
