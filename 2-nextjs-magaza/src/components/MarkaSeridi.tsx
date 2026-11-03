import Link from "next/link";

/** Ana sayfa marka şeridi — ürün sayısına göre en çok ürünü olan markalar önde. */
export function MarkaSeridi({ markalar }: { markalar: { ad: string; sayi: number }[] }) {
  if (markalar.length === 0) return null;

  return (
    <section className="px-5 pb-4 pt-6 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-3 flex items-baseline justify-between border-b border-black/20 pb-2.5">
          <h2 className="text-[15px] font-black uppercase tracking-wide sm:text-[17px]">Markalar</h2>
          <span className="text-[13px] font-medium text-black/30">{markalar.length} marka</span>
        </div>
        <div className="flex gap-2 overflow-x-auto pb-1.5 [scrollbar-width:thin]">
          {markalar.map((m) => (
            <Link
              key={m.ad}
              href={`/marka/${encodeURIComponent(m.ad)}`}
              className="group flex shrink-0 items-center gap-2.5 rounded-xl border border-black/10 bg-white px-3.5 py-2 transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--marka)] hover:shadow-md"
            >
              <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-black/[.04] text-[11px] font-black text-black/60 transition-colors group-hover:bg-[var(--marka)] group-hover:text-white">
                {m.ad.slice(0, 1)}
              </span>
              <span className="text-[13px] font-bold text-black/75 transition-colors group-hover:text-black">{m.ad}</span>
              <span className="text-[11px] font-medium tabular-nums text-black/30">{m.sayi}</span>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
}
