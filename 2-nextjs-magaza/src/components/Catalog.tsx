import type { Urun } from "@/lib/backoffice";
import { imgUrl } from "@/lib/img";
import Link from "next/link";
import { KalpButonu } from "./KalpButonu";

export function UrunKarti({ urun }: { urun: Urun }) {
  const fiyat = new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(urun.sale_price);

  return (
    <div className="group relative">
      <Link href={`/urun/${urun.id}`} className="block">
        <div className="relative flex aspect-[4/5] items-center justify-center overflow-hidden bg-[#f4f4f4] transition-transform duration-300 ease-out group-hover:-translate-y-1.5 group-hover:scale-[1.015]">
          {urun.image_url ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={imgUrl(urun.id, urun.image_url)} alt={urun.name} loading="lazy" className="h-full w-full object-contain p-8" />
          ) : (
            <span className="text-5xl font-black text-black/10">{urun.name.slice(0, 1)}</span>
          )}
          {!urun.in_stock && (
            <span className="absolute inset-0 flex items-center justify-center bg-white/70 text-xs font-bold uppercase tracking-widest text-black">
              Tükendi
            </span>
          )}
          {urun.in_stock && urun.available_quantity <= 5 && (
            <span className="absolute left-3 top-3 bg-black px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white">
              Son {urun.available_quantity} adet
            </span>
          )}
      </div>
      <div className="mt-4 flex items-baseline justify-between gap-2">
        <h3 className="text-[15px] font-semibold text-black">{urun.name}</h3>
        <span className="shrink-0 text-[15px] font-medium tabular-nums text-black/80">{fiyat} ₺</span>
      </div>
      </Link>
      <div className="absolute right-3 top-3 z-10">
        <KalpButonu
          urun={{ id: urun.id, ad: urun.name, fiyat: urun.sale_price, gorsel: imgUrl(urun.id, urun.image_url), kategori: urun.category }}
        />
      </div>
    </div>
  );
}

