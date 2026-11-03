import Link from "next/link";
import { MessageSquareQuote, Star } from "lucide-react";
import type { Yorum } from "@/lib/backoffice";

/** Ana sayfa sosyal kanıt vitrini — onaylı gerçek müşteri yorumları. */
export function YorumVitrini({ yorumlar }: { yorumlar: Yorum[] }) {
  if (yorumlar.length === 0) return null;

  return (
    <section className="px-5 pb-8 pt-6 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-4 flex items-end justify-between gap-3 border-b border-black/20 pb-3">
          <div className="flex min-w-0 items-baseline gap-3">
            <h2 className="flex items-center gap-2 text-[15px] font-black uppercase tracking-wide sm:text-[17px]">
              <MessageSquareQuote className="h-4.5 w-4.5 shrink-0 text-[var(--marka)]" />
              Müşteriler Ne Diyor?
            </h2>
            <span className="truncate text-[13px] font-medium text-black/30">Onaylı gerçek yorumlar</span>
          </div>
          <span className="shrink-0 text-[13px] font-medium text-black/30">{yorumlar.length} yorum</span>
        </div>

        <div className="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
          {yorumlar.map((y, i) => (
            <div
              key={`${y.product_id}-${i}`}
              className="flex w-[250px] shrink-0 snap-start flex-col rounded-2xl border border-black/10 bg-white p-4 transition hover:shadow-lg"
            >
              <span className="flex gap-0.5 text-amber-400">
                {Array.from({ length: 5 }).map((_, s) => (
                  <Star key={s} className={`h-3.5 w-3.5 ${s < Math.round(y.rating) ? "fill-amber-400" : "text-black/15"}`} />
                ))}
                <span className="ml-1.5 text-[11px] font-black text-black/60">{y.rating}.0</span>
              </span>
              <p className="mt-2.5 line-clamp-4 min-h-[64px] text-[12.5px] leading-relaxed text-black/70">“{y.comment}”</p>
              <div className="mt-auto pt-3">
                <p className="text-[11px] font-bold text-black">{y.user_name}</p>
                <p className="text-[10.5px] text-black/35">
                  {new Date(y.created_at).toLocaleDateString("tr-TR", { dateStyle: "long" })}
                </p>
                <Link
                  href={`/urun/${y.product_id}`}
                  className="mt-1.5 block truncate rounded-lg bg-black/[.04] px-2.5 py-1.5 text-[11px] font-bold text-black/55 transition hover:bg-[var(--marka)] hover:text-white"
                >
                  {y.product_name}
                </Link>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
