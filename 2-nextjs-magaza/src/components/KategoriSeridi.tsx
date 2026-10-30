import Link from "next/link";
import {
  Laptop, Cpu, Home, Sparkles, NotebookPen, Music, Briefcase, Gamepad2, Dumbbell, LayoutGrid,
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

/** Ana sayfa kategori vitrini — ikonlu kartlar, tek bakışta gezinme. */
export function KategoriSeridi({ kategoriler }: { kategoriler: { ad: string; sayi: number }[] }) {
  if (kategoriler.length === 0) return null;

  return (
    <section className="px-5 pb-14 pt-16 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-6 flex items-end justify-between border-b border-black/20 pb-3">
          <div className="flex items-baseline gap-3">
            <h2 className="text-[28px] font-black leading-none tracking-[-0.02em] sm:text-[36px]">Kategoriler</h2>
            <span className="text-[13px] font-medium text-black/30">{kategoriler.length} bölüm</span>
          </div>
        </div>

        <div className="grid grid-cols-3 gap-3 sm:grid-cols-5 lg:grid-cols-9">
          {kategoriler.map(({ ad, sayi }) => {
            const Ikon = IKONLAR[ad] ?? LayoutGrid;
            return (
              <Link
                key={ad}
                href={`/kategori/${encodeURIComponent(ad)}`}
                className="group flex flex-col items-center gap-2.5 rounded-2xl border border-black/10 bg-white px-2 py-5 text-center transition-all duration-200 hover:-translate-y-1 hover:border-[var(--marka)] hover:shadow-lg"
              >
                <span className="flex h-12 w-12 items-center justify-center rounded-xl bg-black/[.04] text-black/70 transition-colors group-hover:bg-[var(--marka)] group-hover:text-white">
                  <Ikon className="h-6 w-6" />
                </span>
                <span className="text-[11px] font-bold leading-tight text-black/70 group-hover:text-black">{ad}</span>
                <span className="text-[10px] font-medium text-black/30">{sayi} ürün</span>
              </Link>
            );
          })}
        </div>
      </div>
    </section>
  );
}
