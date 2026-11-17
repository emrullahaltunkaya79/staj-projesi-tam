"use client";

import Link from "next/link";
import { ChevronRight, Star, Sparkles, Clock, type LucideIcon } from "lucide-react";
import type { Urun } from "@/lib/backoffice";
import { UrunKarti } from "./Catalog";
import { GeriSayim } from "./GeriSayim";
import { MarqueeSerit } from "./MarqueeSerit";

/**
 * İkonlar STRING anahtar olarak alınır: sunucu bileşeninden client bileşenine
 * bileşen/fonksiyon prop'u geçilemez (RSC sınırı) — sayfa sunucudan çağrıldığında
 * bu, "Functions cannot be passed directly to Client Components" hatasına yol açar.
 */
const IKONLAR: Record<string, LucideIcon> = {
  star: Star,
  sparkles: Sparkles,
  clock: Clock,
};

/** Ana sayfa yatay ürün vitrini — Çok Satanlar / Sezon / Yeni Gelenler şeritleri.
 *  Sonsuz marquee ile sağdan sola akar; hover'da durur, kenarlarda yumuşak maske var. */
export function VitrinSeridi({
  baslik,
  ikon,
  urunler,
  tur,
  aciklama,
  geriSayim = false,
  tumunuGor,
}: {
  baslik: string;
  /** İkon anahtarı: "star" | "sparkles" | "clock" */
  ikon: string;
  urunler: Urun[];
  tur: "flas" | "cokSatan" | "sezon";
  aciklama?: string;
  geriSayim?: boolean;
  tumunuGor?: string;
}) {
  const Ikon = IKONLAR[ikon] ?? Star;
  if (urunler.length === 0) return null;

  return (
    <section className="px-5 pb-8 pt-6 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-4 flex items-center justify-between gap-3 border-b border-black/20 pb-3">
          <div className="flex min-w-0 items-center gap-3">
            <h2 className="flex items-center gap-2 text-[15px] font-black uppercase tracking-wide sm:text-[17px]">
              <Ikon className="h-4.5 w-4.5 shrink-0 text-black/40" />
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

        {/* Sonsuz akan şerit: kopyalarda odak tek kopyayla sınırlı (odaklanabilir) */}
        <MarqueeSerit
          ogeler={urunler}
          etiket={`${baslik} — sonsuz kayan ürün şeridi`}
          kart={(u, kopya) => (
            <div key={u.id} className="mr-3 w-[204px] shrink-0">
              <UrunKarti urun={u} odaklanabilir={kopya === 0} />
              {tur === "flas" && u.in_stock && (
                <div className="mt-1.5">
                  <div className="h-1.5 w-full overflow-hidden rounded-full bg-black/10">
                    <div
                      className="h-full rounded-full bg-red-600"
                      style={{ width: `${Math.max(8, Math.min(100, Math.round((u.available_quantity / Math.max(u.stock_quantity, 1)) * 100)))}%` }}
                    />
                  </div>
                  <p className="mt-1 text-[10px] font-bold text-red-600">
                    Son {u.available_quantity} adet!
                  </p>
                </div>
              )}
            </div>
          )}
        />
      </div>
    </section>
  );
}
