"use client";

import Link from "next/link";
import { favori } from "@/components/FavoriProvider";
import { KalpButonu } from "@/components/KalpButonu";
import { Trash2 } from "lucide-react";

const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

export default function FavorilerSayfasi() {
  const { favoriler, cikar } = favori();

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="flex items-end justify-between border-b border-black/20 pb-5">
          <div className="flex items-baseline gap-3">
            <h1 className="text-[36px] font-black leading-none tracking-[-0.02em] sm:text-[48px]">Favorilerim</h1>
            <span className="text-[13px] font-medium text-black/30">{favoriler.length} ürün</span>
          </div>
        </div>

        {favoriler.length === 0 ? (
          <div className="py-20 text-center">
            <span className="text-5xl">🤍</span>
            <p className="mt-4 text-sm text-black/50">Henüz favori ürününüz yok.</p>
            <Link href="/" className="mt-4 inline-block rounded-full bg-black px-6 py-3 text-sm font-bold text-white transition hover:bg-black/80">
              Ürünlere Göz At
            </Link>
          </div>
        ) : (
          <div className="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {favoriler.map((f) => (
              <div key={f.id} className="group relative">
                <Link href={`/urun/${f.id}`} className="block">
                  <div className="relative aspect-[4/5] items-center justify-center overflow-hidden bg-[#f4f4f4] transition-transform duration-300 group-hover:-translate-y-1.5 group-hover:scale-[1.015]">
                    {f.gorsel ? (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img src={`/api/img/${f.id}`} alt={f.ad} className="h-full w-full object-contain p-6" />
                    ) : (
                      <span className="text-5xl font-black text-black/10">{f.ad.charAt(0)}</span>
                    )}
                  </div>
                  <div className="mt-3 flex items-baseline justify-between gap-2">
                    <div>
                      <h3 className="text-[14px] font-bold text-black">{f.ad}</h3>
                      {f.kategori && <p className="text-xs text-black/30">{f.kategori}</p>}
                    </div>
                    <span className="shrink-0 text-[14px] font-bold tabular-nums text-black/70">{fmt(f.fiyat)} ₺</span>
                  </div>
                </Link>
                <div className="absolute right-3 top-3 z-10 flex flex-col gap-1.5">
                  <KalpButonu urun={f} />
                  <button
                    type="button"
                    onClick={() => cikar(f.id)}
                    aria-label="Kaldır"
                    className="rounded-full bg-white/80 p-1.5 text-black/30 backdrop-blur transition hover:text-red-500"
                  >
                    <Trash2 className="h-3.5 w-3.5" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
