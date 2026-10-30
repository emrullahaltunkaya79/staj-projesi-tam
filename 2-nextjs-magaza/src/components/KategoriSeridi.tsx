import Link from "next/link";
import {
  Laptop, Cpu, Home, Sparkles, NotebookPen, Music, Briefcase, Gamepad2, Dumbbell, LayoutGrid, ChevronRight,
  type LucideIcon,
} from "lucide-react";

const IKONLAR: Record<string, LucideIcon> = {
  "Bilgisayar": Laptop,
  "Elektronik": Cpu,
  "Ev & Yaşam": Home,
  "Kişisel Bakım": Sparkles,
  "Kırtasiye": NotebookPen,
  "Müzik": Music,
  "Ofis": Briefcase,
  "Oyun & Eğlence": Gamepad2,
  "Spor & Outdoor": Dumbbell,
};

/** Ana sayfa kategori vitrini — tek satırda yatay kaydırmalı kartlar, ikon solda. */
export function KategoriSeridi({ kategoriler }: { kategoriler: { ad: string; sayi: number }[] }) {
  if (kategoriler.length === 0) return null;

  return (
    <section className="px-5 pb-14 pt-16 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-5 flex items-end justify-between border-b border-black/20 pb-3">
          <div className="flex items-baseline gap-3">
            <h2 className="text-[28px] font-black leading-none tracking-[-0.02em] sm:text-[36px]">Kategoriler</h2>
            <span className="text-[13px] font-medium text-black/30">{kategoriler.length} bölüm</span>
          </div>
          <span className="hidden items-center gap-1 text-[11px] font-bold text-black/30 sm:flex">
            kaydırabilirsiniz <ChevronRight className="h-3.5 w-3.5" />
          </span>
        </div>

        <div className="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2 [scrollbar-width:thin]">
          {kategoriler.map(({ ad, sayi }) => {
            const Ikon = IKONLAR[ad] ?? LayoutGrid;
            return (
              <Link
                key={ad}
                href={`/kategori/${encodeURIComponent(ad)}`}
                className="group flex min-w-[190px] shrink-0 snap-start items-center gap-3.5 rounded-2xl border border-black/10 bg-white px-4 py-3.5 transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--marka)] hover:shadow-lg"
              >
                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-black/[.04] text-black/70 transition-colors group-hover:bg-[var(--marka)] group-hover:text-white">
                  <Ikon className="h-5.5 w-5.5" />
                </span>
                <span className="min-w-0">
                  <span className="block truncate text-[13px] font-bold text-black/75 transition-colors group-hover:text-black">{ad}</span>
                  <span className="block text-[11px] font-medium text-black/35">{sayi} ürün</span>
                </span>
                <ChevronRight className="ml-auto h-4 w-4 shrink-0 text-black/20 transition group-hover:translate-x-0.5 group-hover:text-[var(--marka)]" />
              </Link>
            );
          })}
        </div>
      </div>
    </section>
  );
}
