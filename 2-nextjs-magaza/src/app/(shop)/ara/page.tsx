import Link from "next/link";
import { BosDurum } from "@/components/BosDurum";
import { urunler, indirimliFiyat } from "@/lib/backoffice";
import { UrunKarti } from "@/components/Catalog";

export const dynamic = "force-dynamic";

export default async function AraSayfasi({ searchParams }: { searchParams: Promise<{ q?: string; sirala?: string }> }) {
  const { q, sirala } = await searchParams;
  const terim = (q ?? "").trim().toLocaleLowerCase("tr");

  const tumu = await urunler();
  let sonuc = terim
    ? tumu.filter((u) =>
        [u.name, u.code, u.barcode, u.category, u.brand]
          .filter(Boolean)
          .some((f) => (f as string).toLocaleLowerCase("tr").includes(terim)),
      )
    : [];

  // Sıralama
  switch (sirala) {
    case "fiyat-artan":
      sonuc = [...sonuc].sort((a, b) => indirimliFiyat(a) - indirimliFiyat(b));
      break;
    case "fiyat-azalan":
      sonuc = [...sonuc].sort((a, b) => indirimliFiyat(b) - indirimliFiyat(a));
      break;
    case "yeni":
      sonuc = [...sonuc].sort((a, b) => b.id - a.id);
      break;
  }

  const secenekler: [string, string][] = [
    ["", "Önerilen"],
    ["fiyat-artan", "Fiyat ↑"],
    ["fiyat-azalan", "Fiyat ↓"],
    ["yeni", "Yeni"],
  ];

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[56px] font-black leading-none tracking-[-0.03em] sm:text-[84px]">Arama</h1>
          <p className="mt-2 text-sm text-black/40">
            {terim ? `"${terim}" için ${sonuc.length} sonuç` : "Bir arama terimi girin"}
          </p>
          {terim && (
            <div className="mt-4 flex flex-wrap gap-2">
              {secenekler.map(([deger, etiket]) => (
                <Link
                  key={deger || "onerilen"}
                  href={`/ara?q=${encodeURIComponent(q ?? "")}${deger ? `&sirala=${deger}` : ""}`}
                  className={`rounded-full px-3 py-1 text-[12px] font-bold transition ${
                    (sirala ?? "") === deger ? "bg-black text-white" : "text-black/50 hover:text-black"
                  }`}
                >
                  {etiket}
                </Link>
              ))}
            </div>
          )}
        </div>

        {terim && sonuc.length === 0 && (
          <div className="py-20 text-center">
            <BosDurum tur="arama" />
            <a href="/" className="mt-4 inline-block text-sm font-bold underline underline-offset-4">Tüm ürünlere göz at →</a>
          </div>
        )}

        {sonuc.length > 0 && (
          <div className="mt-5 grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-5 2xl:grid-cols-6">
            {sonuc.map((u) => (
              <UrunKarti key={u.id} urun={u} />
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
