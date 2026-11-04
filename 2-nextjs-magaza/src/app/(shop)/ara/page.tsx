import Link from "next/link";
import { BosDurum } from "@/components/BosDurum";
import { urunAra } from "@/lib/backoffice";
import { UrunKarti } from "@/components/Catalog";

export const dynamic = "force-dynamic";

export const metadata = { title: "Arama — HepsiBirarada" };

const SAYFA_BOYUTU = 24;
const SIRALALAR: [string, string][] = [
  ["", "Önerilen"],
  ["fiyat-artan", "Fiyat ↑"],
  ["fiyat-azalan", "Fiyat ↓"],
  ["yeni", "Yeni"],
];

export default async function AraSayfasi({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; sirala?: string; sayfa?: string }>;
}) {
  const { q, sirala, sayfa } = await searchParams;
  const terim = (q ?? "").trim();
  const aktifSirala = sirala ?? "";
  const aktifSayfa = Math.max(1, Number(sayfa) || 1);

  const sonuc = terim ? await urunAra(terim, aktifSirala, aktifSayfa, SAYFA_BOYUTU) : { urunler: [], toplam: 0, markalar: [] };
  const toplamSayfa = Math.max(1, Math.ceil(sonuc.toplam / SAYFA_BOYUTU));
  const href = (s: string, p: number) =>
    `/ara?q=${encodeURIComponent(q ?? "")}${s ? `&sirala=${encodeURIComponent(s)}` : ""}${p > 1 ? `&sayfa=${p}` : ""}`;

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="border-b border-black/20 pb-5">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div className="flex items-baseline gap-3">
              <h1 className="text-[15px] font-black uppercase tracking-wide text-black sm:text-[17px]">Arama Sonuçları</h1>
              <span className="text-[13px] font-medium text-black/30">
                {terim ? `"${terim}" için ${sonuc.toplam} sonuç` : "Bir arama terimi girin"}
              </span>
            </div>
            {terim && (
              <div className="flex flex-wrap gap-2">
                {SIRALALAR.map(([deger, etiket]) => (
                  <Link
                    key={deger || "onerilen"}
                    href={href(deger, 1)}
                    className={`rounded-full px-3 py-1 text-[12px] font-bold transition ${
                      aktifSirala === deger ? "bg-black text-white" : "text-black/50 hover:text-black"
                    }`}
                  >
                    {etiket}
                  </Link>
                ))}
              </div>
            )}
          </div>
        </div>

        {terim && sonuc.urunler.length === 0 ? (
          <div className="py-20 text-center">
            <BosDurum tur="arama" />
            <a href="/" className="mt-4 inline-block text-sm font-bold underline underline-offset-4">
              Tüm ürünlere göz at →
            </a>
          </div>
        ) : (
          sonuc.urunler.length > 0 && (
            <div className="mt-6">
              <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-5 2xl:grid-cols-6">
                {sonuc.urunler.map((u) => (
                  <UrunKarti key={u.id} urun={u} />
                ))}
              </div>

              {/* Sayfalama */}
              {toplamSayfa > 1 && (
                <div className="mt-10 flex items-center justify-center gap-1.5">
                  {aktifSayfa > 1 && (
                    <Link href={href(aktifSirala, aktifSayfa - 1)} className="rounded-lg border border-black/15 px-3 py-2 text-[12px] font-bold text-black/60 transition hover:border-black hover:text-black">
                      ‹ Önceki
                    </Link>
                  )}
                  {Array.from({ length: toplamSayfa }).map((_, i) => {
                    const n = i + 1;
                    if (toplamSayfa > 7 && Math.abs(n - aktifSayfa) > 2 && n !== 1 && n !== toplamSayfa) return null;
                    return (
                      <Link
                        key={n}
                        href={href(aktifSirala, n)}
                        className={`rounded-lg px-3 py-2 text-[12px] font-bold tabular-nums transition ${
                          n === aktifSayfa ? "bg-black text-white" : "text-black/50 hover:bg-black/5 hover:text-black"
                        }`}
                      >
                        {n}
                      </Link>
                    );
                  })}
                  {aktifSayfa < toplamSayfa && (
                    <Link href={href(aktifSirala, aktifSayfa + 1)} className="rounded-lg border border-black/15 px-3 py-2 text-[12px] font-bold text-black/60 transition hover:border-black hover:text-black">
                      Sonraki ›
                    </Link>
                  )}
                </div>
              )}
            </div>
          )
        )}
      </div>
    </div>
  );
}
