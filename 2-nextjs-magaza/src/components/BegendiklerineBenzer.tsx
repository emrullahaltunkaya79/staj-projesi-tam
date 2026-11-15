"use client";

import { Heart } from "lucide-react";
import { UrunKarti } from "./Catalog";
import { favori } from "./FavoriProvider";
import type { Urun } from "@/lib/backoffice";

/** "Beğendiklerine benzer" — favorilerdeki ürünlerin kategorisinden öneriler. */
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
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
          {adaylar.map((u) => (
            <UrunKarti key={u.id} urun={u} />
          ))}
        </div>
      </div>
    </section>
  );
}
