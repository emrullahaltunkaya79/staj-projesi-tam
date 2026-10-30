import Hero from "@/components/Hero";
import { KategoriSeridi } from "@/components/KategoriSeridi";
import { KatalogIcerik } from "@/components/KatalogIcerik";
import { urunler } from "@/lib/backoffice";

export const dynamic = "force-dynamic";

export default async function Home() {
  let tumu: Awaited<ReturnType<typeof urunler>> = [];
  let hata: string | null = null;

  try {
    tumu = await urunler();
  } catch (e) {
    hata = e instanceof Error ? e.message : "Katalog yüklenemedi.";
  }

  return (
    <div>
      <Hero />

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
