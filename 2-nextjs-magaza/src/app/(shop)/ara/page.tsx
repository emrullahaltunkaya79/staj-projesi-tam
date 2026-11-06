import { BosDurum } from "@/components/BosDurum";
import { urunAra } from "@/lib/backoffice";
import { KatalogIcerik } from "@/components/KatalogIcerik";

export const dynamic = "force-dynamic";

export const metadata = { title: "Arama — HepsiBirarada" };

/** Sonuçlar istemcide anında filtrelenir; tek istekte bu kadarı alınır. */
const AZAMI_SONUC = 200;

export default async function AraSayfasi({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; sirala?: string }>;
}) {
  const { q, sirala } = await searchParams;
  const terim = (q ?? "").trim();

  const sonuc = terim
    ? await urunAra(terim, sirala ?? "", 1, AZAMI_SONUC)
    : { urunler: [], toplam: 0, markalar: {}, kategoriler: {} };

  if (terim && sonuc.urunler.length > 0) {
    return (
      <div className="pt-8">
        <KatalogIcerik tumUrunler={sonuc.urunler} kategori={null} baslik="Arama Sonuçları" aramaTerimi={terim} />
        {sonuc.toplam > AZAMI_SONUC && (
          <p className="-mt-16 pb-16 text-center text-[11px] font-medium text-black/40">
            İlk {AZAMI_SONUC} sonuç gösteriliyor — daha isabetli sonuç için aramanızı daraltın.
          </p>
        )}
      </div>
    );
  }

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="border-b border-black/20 pb-5">
          <div className="flex items-baseline gap-3">
            <h1 className="text-[15px] font-black uppercase tracking-wide text-black sm:text-[17px]">Arama Sonuçları</h1>
            <span className="text-[13px] font-medium text-black/30">
              {terim ? `"${terim}" için 0 sonuç` : "Bir arama terimi girin"}
            </span>
          </div>
        </div>

        {terim && (
          <div className="py-20 text-center">
            <BosDurum tur="arama" />
            <a href="/" className="mt-4 inline-block text-sm font-bold underline underline-offset-4">
              Tüm ürünlere göz at →
            </a>
          </div>
        )}
      </div>
    </div>
  );
}
