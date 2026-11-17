"use client";

import { Heart } from "lucide-react";
import { UrunKarti } from "./Catalog";
import { MarqueeSerit } from "./MarqueeSerit";
import { favori } from "./FavoriProvider";
import type { Urun } from "@/lib/backoffice";

/** "Beğendiklerine benzer" — favorilerdeki ürünlerin kategorisinden öneriler, sonsuz akan şerit. */
export function BegendiklerineBenzer({ tumu }: { tumu: Urun[] }) {
  const { favoriler } = favori();

  const idler = new Set(favoriler.map((f) => f.id));
  const kategoriler = new Set(favoriler.map((f) => f.kategori).filter((k): k is string => Boolean(k)));
  const adaylar = tumu
    .filter((u) => !idler.has(u.id) && u.in_stock && u.category && kategoriler.has(u.category))
    .slice(0, 8);

  if (favoriler.length === 0 || adaylar.length === 0) return null;

  return (
    <section className="px-5 pb-8 pt-6 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-4 flex items-center justify-between gap-3 border-b border-black/20 pb-3">
          <h2 className="flex items-center gap-2 text-[15px] font-black uppercase tracking-wide text-black">
            <Heart className="h-4 w-4 text-[var(--marka)]" /> Beğendiklerine benzer
          </h2>
          <span className="text-[11px] font-medium text-black/35">favorilerine göre önerildi</span>
        </div>
        <MarqueeSerit
          ogeler={adaylar}
          etiket="Beğendiklerine benzer ürünler — sonsuz kayan şerit"
          kart={(u, kopya) => (
            <div key={u.id} className="mr-4 w-[204px] shrink-0">
              <UrunKarti urun={u} odaklanabilir={kopya === 0} />
            </div>
          )}
        />
      </div>
    </section>
  );
}
