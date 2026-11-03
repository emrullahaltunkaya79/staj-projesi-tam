"use client";

import { useState } from "react";
import Link from "next/link";
import {
  Laptop, Cpu, Home, Sparkles, NotebookPen, Music, Briefcase, Gamepad2, Dumbbell, LayoutGrid, Camera, Clapperboard, HeartPulse, ChevronDown,
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
  "Fotoğraf & Kamera": Camera,
  "Ev Sineması": Clapperboard,
  "Wellness & Sağlık": HeartPulse,
};

/**
 * Ana sayfa kategori vitrini — tek satırda yatay kaydırmalı kartlar.
 * Ok işaretine tıklayınca kart büyür ve o kategorinin markaları listelenir.
 */
export function KategoriSeridi({
  kategoriler,
  markalar = {},
}: {
  kategoriler: { ad: string; sayi: number }[];
  markalar?: Record<string, { ad: string; sayi: number }[]>;
}) {
  const [acik, setAcik] = useState<string | null>(null);

  if (kategoriler.length === 0) return null;

  return (
    <section className="px-5 pb-10 pt-10 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-5 flex items-end justify-between border-b-2 border-black pb-3">
          <div className="flex items-center gap-3">
            <span className="h-6 w-1.5 shrink-0 rounded-full bg-[var(--marka)]" aria-hidden />
            <h2 className="text-[19px] font-black uppercase tracking-wide text-black sm:text-[22px]">Kategoriler</h2>
            <span className="rounded-full border border-[var(--marka)] px-2.5 py-0.5 text-[11px] font-bold tabular-nums text-[var(--marka)]">
              {kategoriler.length} bölüm
            </span>
          </div>
          <span className="hidden items-center gap-1 text-[11px] font-bold text-black/30 sm:flex">
            ok işaretiyle markaları görün <ChevronDown className="h-3.5 w-3.5" />
          </span>
        </div>

        <div className="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2 [scrollbar-width:thin]">
          {kategoriler.map(({ ad, sayi }) => {
            const Ikon = IKONLAR[ad] ?? LayoutGrid;
            const kategoriMarkalari = markalar[ad] ?? [];
            const kartAcik = acik === ad;
            return (
              <div
                key={ad}
                className={`group flex shrink-0 snap-start flex-col rounded-2xl border border-black/10 bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--marka)] hover:shadow-lg ${
                  kartAcik ? "w-[280px]" : "min-w-[190px]"
                }`}
              >
                <div className="flex items-center gap-3.5 px-4 py-3.5">
                  <Link href={`/kategori/${encodeURIComponent(ad)}`} className="flex min-w-0 flex-1 items-center gap-3.5">
                    <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-black/[.04] text-black/70 transition-colors group-hover:bg-[var(--marka)] group-hover:text-white">
                      <Ikon className="h-5.5 w-5.5" />
                    </span>
                    <span className="min-w-0">
                      <span className="block truncate text-[13px] font-bold text-black/75 transition-colors group-hover:text-black">{ad}</span>
                      <span className="block text-[11px] font-medium text-black/35">{sayi} ürün</span>
                    </span>
                  </Link>
                  {kategoriMarkalari.length > 0 && (
                    <button
                      type="button"
                      onClick={() => setAcik(kartAcik ? null : ad)}
                      aria-label={`${ad} markaları`}
                      aria-expanded={kartAcik}
                      title={`${ad} markaları (${kategoriMarkalari.length})`}
                      className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-black/30 transition hover:bg-black/5 hover:text-black ${
                        kartAcik ? "bg-[var(--marka)] text-white hover:bg-[var(--marka)] hover:text-white" : ""
                      }`}
                    >
                      <ChevronDown className={`h-4 w-4 transition-transform duration-300 ${kartAcik ? "rotate-180" : ""}`} />
                    </button>
                  )}
                </div>
                {kartAcik && (
                  <div className="border-t border-black/10 px-3 py-2.5">
                    <p className="mb-1.5 text-[9.5px] font-black uppercase tracking-widest text-black/35">Markalar</p>
                    <div className="flex max-h-[132px] flex-wrap gap-1 overflow-y-auto [scrollbar-width:thin]">
                      {kategoriMarkalari.map((m) => (
                        <Link
                          key={m.ad}
                          href={`/marka/${encodeURIComponent(m.ad)}`}
                          className="flex items-center gap-1 rounded-full border border-black/10 px-2 py-1 text-[10.5px] font-bold text-black/60 transition hover:border-[var(--marka)] hover:bg-[var(--marka)] hover:text-white"
                        >
                          {m.ad}
                          <span className="text-black/30">{m.sayi}</span>
                        </Link>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
