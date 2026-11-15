import Link from "next/link";
import { urunler } from "@/lib/backoffice";
import { kdvDahil } from "@/lib/fiyat";

export const metadata = { title: "Paylaşılan Favori Listesi — HepsiBirarada", robots: { index: false } };

const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

/** Paylaşılan favori listesi — ?ids=1,2,3 biçimindeki bağlantıyla herkese açık okunur görünüm. */
export default async function PaylasilanFavoriler({
  searchParams,
}: {
  searchParams: Promise<{ ids?: string }>;
}) {
  const { ids } = await searchParams;
  const istenen = (ids ?? "")
    .split(",")
    .map((x) => Number(x.trim()))
    .filter((x) => Number.isInteger(x) && x > 0);

  if (istenen.length === 0) {
    return (
      <div className="flex min-h-[50vh] flex-col items-center justify-center px-5 text-center">
        <p className="text-3xl">🤍</p>
        <h1 className="mt-3 text-[15px] font-black uppercase tracking-wide text-black">Paylaşılan liste bulunamadı</h1>
        <p className="mt-2 max-w-sm text-sm text-black/55">Bağlantı eksik veya bozuk görünüyor. Paylaşan kişiye listeyi yeniden göndermesini söyleyebilirsin.</p>
        <Link href="/" className="mt-6 h-11 rounded-full bg-black px-6 inline-flex items-center text-sm font-bold text-white transition hover:bg-black/80">
          Mağazaya git
        </Link>
      </div>
    );
  }

  const tumu = await urunler();
  const liste = istenen
    .map((id) => tumu.find((u) => u.id === id))
    .filter((u): u is NonNullable<typeof u> => Boolean(u));

  if (liste.length === 0) {
    return (
      <div className="flex min-h-[50vh] flex-col items-center justify-center px-5 text-center">
        <p className="text-3xl">🤍</p>
        <h1 className="mt-3 text-[15px] font-black uppercase tracking-wide text-black">Listedeki ürünler artık mağazada yok</h1>
        <Link href="/" className="mt-6 h-11 rounded-full bg-black px-6 inline-flex items-center text-sm font-bold text-white transition hover:bg-black/80">
          Mağazaya git
        </Link>
      </div>
    );
  }

  const toplam = liste.reduce((t, u) => t + kdvDahil(u.final_price ?? u.sale_price), 0);

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-5xl">
        <div className="border-b border-black/20 pb-5 text-center">
          <p className="text-3xl">🤍</p>
          <h1 className="mt-3 text-[15px] font-black uppercase tracking-wide text-black sm:text-[17px]">Paylaşılan Favori Listesi</h1>
          <p className="mt-2 text-sm text-black/50">
            {liste.length} ürün · toplam {fmt(toplam)} ₺
          </p>
        </div>

        <div className="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
          {liste.map((u) => (
            <Link key={u.id} href={`/urun/${u.id}`} className="group overflow-hidden rounded-2xl border border-black/10 bg-white transition hover:shadow-lg">
              <div className="aspect-square w-full bg-[#f4f4f4]">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={`/api/img/${u.id}?v=2026-11-11-1`} alt={u.name} loading="lazy" className="h-full w-full object-contain p-[6%]" />
              </div>
              <div className="p-3">
                {u.brand && <p className="text-[10px] font-black uppercase tracking-widest text-black/40">{u.brand}</p>}
                <p className="mt-0.5 line-clamp-2 min-h-[32px] text-[13px] font-semibold leading-snug text-black group-hover:underline">{u.name}</p>
                <p className="mt-1 text-sm font-black tabular-nums">{fmt(kdvDahil(u.final_price ?? u.sale_price))} ₺</p>
                <p className={`text-[11px] font-bold ${u.in_stock ? "text-emerald-600" : "text-red-500"}`}>
                  {u.in_stock ? "Stokta" : "Tükendi"}
                </p>
              </div>
            </Link>
          ))}
        </div>

        <div className="mt-10 text-center">
          <Link href="/" className="inline-flex h-11 items-center rounded-full bg-black px-8 text-sm font-bold text-white transition hover:bg-black/80">
            Bu listeyi ben de doldurmak istiyorum — mağazaya git
          </Link>
        </div>
      </div>
    </div>
  );
}
