import type { Urun } from "@/lib/backoffice";
import { indirimliFiyat } from "@/lib/backoffice";
import { imgUrl } from "@/lib/img";
import Link from "next/link";
import { KalpButonu } from "./KalpButonu";
import { QuickViewButonu } from "./QuickView";
import { Star } from "lucide-react";
import { KartaEkleButonu } from "./KartaEkleButonu";

export function UrunKarti({ urun }: { urun: Urun }) {
  const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);
  const indirim = urun.discount_percent ?? 0;
  const fiyat = indirim > 0 ? indirimliFiyat(urun) : urun.sale_price;

  return (
    <div className="group relative flex flex-col">
      <Link href={`/urun/${urun.id}`} className="block">
        <div className="relative flex aspect-square items-center justify-center overflow-hidden bg-[#f4f4f4] transition-transform duration-300 ease-out group-hover:-translate-y-1.5 group-hover:scale-[1.015]">
          {urun.image_url ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={imgUrl(urun.id, urun.image_url)} alt={urun.name} loading="lazy" className="h-full w-full object-contain p-4" />
          ) : (
            <span className="text-5xl font-black text-black/10">{urun.name.slice(0, 1)}</span>
          )}
          {(indirim > 0 || urun.is_bestseller) && (
            <span className={`absolute left-3 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide ${indirim > 0 ? "top-3 bg-white/95 text-orange-600 shadow-sm" : "top-3 bg-white/95 text-amber-600 shadow-sm"}`}>
              {indirim > 0 ? `%${indirim} İndirim` : "★ Çok Satan"}
            </span>
          )}
          {indirim > 0 && urun.is_bestseller && (
            <span className="absolute left-3 top-11 bg-white/95 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-amber-600 shadow-sm">
              ★ Çok Satan
            </span>
          )}
          <QuickViewButonu urunId={urun.id} />
          {!urun.in_stock && (
            <span className="absolute inset-0 flex items-center justify-center bg-white/70 text-xs font-bold uppercase tracking-widest text-black">
              Tükendi
            </span>
          )}
          {urun.in_stock && urun.available_quantity <= 5 && (
            <span className={`absolute bg-white/95 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-orange-600 shadow-sm ${indirim > 0 ? "left-3 top-11" : "left-3 top-3"}`}>
              Son {urun.available_quantity} adet
            </span>
          )}
      </div>
      <div className="mt-2 flex items-baseline justify-between gap-2">
        <div className="min-w-0">
            {urun.brand && (
              <span className="mb-0.5 block text-[10px] font-black uppercase tracking-widest text-black/45">{urun.brand}</span>
            )}
            <h3 className="line-clamp-2 min-h-[32px] text-[12.5px] font-semibold leading-tight text-black">{urun.name}</h3>
            <span className="mt-1 flex h-4 items-center gap-1 text-[11px] font-bold text-amber-500">
              {urun.rating_avg != null ? (
                <>
                  <Star className="h-3 w-3 fill-amber-400 text-amber-400" />
                  {urun.rating_avg}
                  <span className="font-medium text-black/35">({urun.review_count ?? 0})</span>
                </>
              ) : (
                <span className="text-transparent">—</span>
              )}
            </span>
          </div>
        <div className="flex shrink-0 flex-col items-end">
          <span className="text-[15px] font-black tabular-nums text-black sm:text-[17px]">{fmt(fiyat)} ₺</span>
          {indirim > 0 && (
            <span className="text-xs tabular-nums text-black/40 line-through">{fmt(urun.sale_price)} ₺</span>
          )}
        </div>
      </div>
      </Link>
      <div className="mt-auto">
        <KartaEkleButonu
          urun={{ id: urun.id, ad: urun.name, fiyat, gorsel: imgUrl(urun.id, urun.image_url) }}
          musait={urun.in_stock ? urun.available_quantity : 0}
        />
      </div>
      <div className="absolute right-3 top-3 z-10">
        <KalpButonu
          urun={{ id: urun.id, ad: urun.name, fiyat: urun.sale_price, gorsel: imgUrl(urun.id, urun.image_url), kategori: urun.category }}
        />
      </div>
    </div>
  );
}

