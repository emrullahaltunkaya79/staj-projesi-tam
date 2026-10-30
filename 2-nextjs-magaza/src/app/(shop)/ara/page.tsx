import { BosDurum } from "@/components/BosDurum";
import { urunler } from "@/lib/backoffice";
import { UrunKarti } from "@/components/Catalog";

export const dynamic = "force-dynamic";

export default async function AraSayfasi({ searchParams }: { searchParams: Promise<{ q?: string }> }) {
  const { q } = await searchParams;
  const terim = (q ?? "").trim().toLocaleLowerCase("tr");

  const tumu = await urunler();
  const sonuc = terim
    ? tumu.filter((u) =>
        [u.name, u.code, u.barcode, u.category]
          .filter(Boolean)
          .some((f) => (f as string).toLocaleLowerCase("tr").includes(terim)),
      )
    : [];

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[56px] font-black leading-none tracking-[-0.03em] sm:text-[84px]">Arama</h1>
          <p className="mt-2 text-sm text-black/40">
            {terim ? `"${terim}" için ${sonuc.length} sonuç` : "Bir arama terimi girin"}
          </p>
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
