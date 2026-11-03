import Link from "next/link";
import { ChevronRight, type LucideIcon } from "lucide-react";
import type { Urun } from "@/lib/backoffice";
import { UrunKarti } from "./Catalog";
import { GeriSayim } from "./GeriSayim";

/** Ana sayfa yatay ürün vitrini — Flaş İndirimler / En Çok Satanlar şeritleri. */
export function VitrinSeridi({
  baslik,
  ikon: Ikon,
  urunler,
  tur,
  aciklama,
  geriSayim = false,
  tumunuGor,
}: {
  baslik: string;
  ikon: LucideIcon;
  urunler: Urun[];
  tur: "flas" | "cokSatan";
  aciklama?: string;
  geriSayim?: boolean;
  tumunuGor?: string;
}) {
  if (urunler.length === 0) return null;

  const kirmizi = tur === "flas";

  return (
    <section className="px-5 pb-8 pt-6 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-4 flex items-end justify-between gap-3 border-b border-black/20 pb-3">
          <div className="flex min-w-0 items-baseline gap-3">
            <h2
              className={`flex items-center gap-2 text-[15px] font-black uppercase tracking-wide sm:text-[17px] ${
                kirmizi ? "text-red-600" : ""
              }`}
            >
              <Ikon className={`h-4.5 w-4.5 shrink-0 ${kirmizi ? "fill-red-600 text-red-600" : "fill-amber-400 text-amber-400"}`} />
              {baslik}
            </h2>
            {aciklama && <span className="truncate text-[13px] font-medium text-black/30">{aciklama}</span>}
          </div>
          <div className="flex shrink-0 items-center gap-3">
            {geriSayim && (
              <span className="flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-bold text-red-600">
                Bugün bitiyor
                <span className="rounded bg-red-600 px-1.5 py-0.5 font-mono text-[10px] font-black tracking-wider text-white">
                  <GeriSayim kompakt />
                </span>
              </span>
            )}
            {tumunuGor && (
              <Link
                href={tumunuGor}
                className="hidden items-center gap-1 text-[12px] font-bold text-black/40 transition hover:text-black sm:flex"
              >
                Tümünü Gör <ChevronRight className="h-3.5 w-3.5" />
              </Link>
            )}
          </div>
        </div>

        <div className="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2 [scrollbar-width:thin]">
          {urunler.map((u) => (
            <div key={u.id} className="w-[196px] shrink-0 snap-start sm:w-[220px]">
              <UrunKarti urun={u} />
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
