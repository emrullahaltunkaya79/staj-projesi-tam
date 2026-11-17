"use client";

import Link from "next/link";
import { Star, Zap } from "lucide-react";
import type { Urun } from "@/lib/backoffice";
import { indirimliFiyat } from "@/lib/backoffice";
import { kdvDahil } from "@/lib/fiyat";
import { imgUrl } from "@/lib/img";
import { GeriSayim } from "./GeriSayim";
import { KartaEkleButonu } from "./KartaEkleButonu";
import { MarqueeSerit } from "./MarqueeSerit";

/**
 * KAMPANYA VURGUSU: Bu bölümdeki turuncu (orange-500/600) tonlar bilinçli olarak
 * temadan bağımsızdır — seçilen marka rengi ne olursa olsun kampanya alanı aynı
 * sıcak vurguyu taşır (tek istisna, tasarım kararı).
 */
const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

/** Flaş İndirimler — sonsuz marquee şerit: hover'da durur, kenarlarda yumuşak maske. */
export function FlasSeridi({ urunler, aciklama }: { urunler: Urun[]; aciklama?: string }) {
  if (urunler.length === 0) return null;

  return (
    <section className="px-5 pb-8 pt-6 sm:px-10">
      <div className="mx-auto max-w-7xl">
        {/* Başlık satırı */}
        <div className="mb-4 flex items-center justify-between gap-3 border-b border-orange-300 pb-3">
          <div className="flex min-w-0 items-center gap-3">
            <h2 className="flex items-center gap-2 text-[15px] font-black uppercase tracking-wide text-orange-600 sm:text-[17px]">
              <Zap className="h-4.5 w-4.5 shrink-0 fill-orange-500" />
              Flaş İndirimler
            </h2>
            {aciklama && <span className="truncate text-[13px] font-medium text-black/40">{aciklama}</span>}
          </div>
          <div className="flex shrink-0 items-center gap-3">
            <span className="flex items-center gap-1.5 rounded-lg bg-orange-50 px-2.5 py-1.5 text-[11px] font-bold text-orange-600">
              Bugün bitiyor
              <span className="rounded bg-orange-500 px-1.5 py-0.5 font-mono text-[11px] font-black tracking-wider text-white">
                <GeriSayim kompakt />
              </span>
            </span>
            <Link href="/flas-indirimler" className="hidden items-center gap-1 text-[12px] font-bold text-black/40 transition hover:text-black sm:flex">
              Tümünü Gör
            </Link>
          </div>
        </div>

        {/* Sonsuz akan şerit — ortak MarqueeSerit omurgası */}
        <MarqueeSerit
          ogeler={urunler}
          etiket="Flaş indirim ürünleri — sonsuz kayan şerit"
          kart={(u, kopya) => {
            const indirim = u.discount_percent ?? 0;
            const fiyat = indirim > 0 ? indirimliFiyat(u) : u.sale_price;
            const kalanOran = Math.max(8, Math.min(100, Math.round((u.available_quantity / Math.max(u.stock_quantity, 1)) * 100)));
            return (
              <div key={u.id} className="mr-3 w-[204px] shrink-0 overflow-hidden rounded-xl border border-black/10 bg-white transition hover:shadow-lg">
                <Link href={`/urun/${u.id}`} tabIndex={kopya > 0 ? -1 : undefined} className="block">
                  <div className="relative aspect-square bg-[#f4f4f4]">
                    {u.image_url ? (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img src={imgUrl(u.id, u.image_url)} alt={kopya > 0 ? "" : u.name} loading="lazy" className="h-full w-full object-contain p-4" />
                    ) : (
                      <span className="flex h-full items-center justify-center text-5xl font-black text-black/10">{u.name.charAt(0)}</span>
                    )}
                    {indirim > 0 && (
                      <span className="absolute left-2.5 top-2.5 rounded-md bg-white/95 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-orange-600 shadow-sm">
                        %{indirim} indirim
                      </span>
                    )}
                    {!u.in_stock && (
                      <span className="absolute inset-0 flex items-center justify-center bg-white/70 text-xs font-bold uppercase tracking-widest text-black">
                        Tükendi
                      </span>
                    )}
                  </div>
                  <div className="px-3 pb-2.5 pt-2.5">
                    {u.brand && <p className="mb-0.5 truncate text-[10px] font-black uppercase tracking-widest text-black/45">{u.brand}</p>}
                    <h3 className="line-clamp-2 h-[34px] text-[12.5px] font-semibold leading-snug text-black">{u.name}</h3>
                    <div className="mt-1 flex h-4 items-center gap-1">
                      {u.rating_avg != null ? (
                        <>
                          <Star className="h-3 w-3 fill-amber-400 text-amber-400" />
                          <span className="text-[11px] font-bold text-amber-500">{u.rating_avg}</span>
                          <span className="text-[10.5px] font-medium text-black/35">({u.review_count ?? 0})</span>
                        </>
                      ) : (
                        <span className="text-[10.5px] text-transparent">—</span>
                      )}
                    </div>
                    <div className="mt-1 flex items-baseline gap-1.5">
                      <span className="text-[15px] font-black tabular-nums text-black">{fmt(kdvDahil(fiyat))} ₺</span>
                      {indirim > 0 && <span className="text-[11px] tabular-nums text-black/35 line-through">{fmt(kdvDahil(u.sale_price))} ₺</span>}
                    </div>
                  </div>
                </Link>
                <div className="px-3 pb-3">
                  {u.in_stock && (
                    <>
                      <div className="h-1.5 w-full overflow-hidden rounded-full bg-black/10">
                        <div className="h-full rounded-full bg-orange-400" style={{ width: `${kalanOran}%` }} />
                      </div>
                      <p className="mb-1.5 mt-1 text-[10px] font-bold text-orange-600">Son {u.available_quantity} adet!</p>
                    </>
                  )}
                  <KartaEkleButonu
                    urun={{ id: u.id, ad: u.name, fiyat, gorsel: imgUrl(u.id, u.image_url) }}
                    musait={u.in_stock ? u.available_quantity : 0}
                  />
                </div>
              </div>
            );
          }}
        />
      </div>
    </section>
  );
}
