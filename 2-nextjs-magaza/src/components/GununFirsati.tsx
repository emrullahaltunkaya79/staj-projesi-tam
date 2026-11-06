import Link from "next/link";
import { Zap } from "lucide-react";
import type { Urun } from "@/lib/backoffice";
import { indirimliFiyat } from "@/lib/backoffice";
import { kdvDahil } from "@/lib/fiyat";
import { GeriSayim } from "./GeriSayim";
import { SepeteEkleButonu } from "./SepeteEkleButonu";

const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

/** Amazon tarzı "Günün Fırsatı" — tek büyük fırsat bloğu, geri sayım ve stok çubuğu. */
export function GununFirsati({ urun }: { urun: Urun }) {
  const indirim = urun.discount_percent ?? 0;
  const fiyat = indirim > 0 ? indirimliFiyat(urun) : urun.sale_price;
  const kalanOran = Math.max(6, Math.min(100, Math.round((urun.available_quantity / Math.max(urun.stock_quantity, 1)) * 100)));

  return (
    <section className="px-5 pb-4 pt-6 sm:px-10">
      <div
        className="mx-auto max-w-7xl overflow-hidden rounded-2xl"
        style={{ background: "linear-gradient(100deg, var(--marka) 0%, #334155 85%)" }}
      >
        <div className="flex flex-col items-center gap-6 p-5 sm:flex-row sm:p-6">
          {/* Görsel */}
          <Link href={`/urun/${urun.id}`} className="shrink-0">
            <div className="flex h-36 w-36 items-center justify-center rounded-2xl bg-white p-3 sm:h-44 sm:w-44">
              {urun.image_url ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={urun.image_url} alt={urun.name} className="h-full w-full object-contain" />
              ) : (
                <span className="text-5xl font-black text-black/10">{urun.name.charAt(0)}</span>
              )}
            </div>
          </Link>

          {/* Bilgi */}
          <div className="min-w-0 flex-1 text-white">
            <p className="flex items-center gap-2 text-[11px] font-black uppercase tracking-[0.2em] text-white/80">
              <Zap className="h-4 w-4 text-white/90" /> Günün Fırsatı
            </p>
            <Link href={`/urun/${urun.id}`} className="mt-1 block truncate text-xl font-black hover:underline sm:text-2xl">
              {urun.name}
            </Link>
            {urun.brand && <p className="mt-0.5 text-xs font-bold uppercase tracking-widest text-white/50">{urun.brand}</p>}

            <div className="mt-2.5 flex flex-wrap items-baseline gap-2.5">
              <span className="text-3xl font-black tabular-nums">{fmt(kdvDahil(fiyat))} ₺</span>
              {indirim > 0 && (
                <>
                  <span className="text-base tabular-nums text-white/40 line-through">{fmt(kdvDahil(urun.sale_price))} ₺</span>
                  <span className="rounded-full bg-white/95 px-2.5 py-0.5 text-[11px] font-black text-black">%{indirim} indirim</span>
                </>
              )}
            </div>

            {/* Stok çubuğu */}
            <div className="mt-3 max-w-md">
              <div className="h-2 w-full overflow-hidden rounded-full bg-white/15">
                <div className="h-full rounded-full bg-white/90" style={{ width: `${kalanOran}%` }} />
              </div>
              <p className="mt-1 text-[11px] font-bold text-white/70">
                {kalanOran < 100
                  ? `Son ${urun.available_quantity} adet — stoğun yalnızca %${kalanOran}'i kaldı`
                  : `Sadece ${urun.available_quantity} adet — gün boyu için sınırlı!`}
              </p>
            </div>

            <p className="mt-3 flex items-center gap-2 text-sm font-medium text-white/70">
              Fırsat bugün gece bitiyor:
              <span className="rounded-lg bg-white/15 px-2.5 py-1 font-mono text-base font-black tabular-nums text-white">
                <GeriSayim />
              </span>
            </p>
          </div>

          {/* Aksiyon */}
          <div className="w-full shrink-0 sm:w-52">
            <SepeteEkleButonu
              urun={{ id: urun.id, ad: urun.name, fiyat, gorsel: `/api/img/${urun.id}` }}
              musait={urun.available_quantity}
            />
            <Link
              href={`/urun/${urun.id}`}
              className="mt-2 block text-center text-xs font-bold text-white/60 underline underline-offset-4 transition hover:text-white"
            >
              Ürün detayına git →
            </Link>
          </div>
        </div>
      </div>
    </section>
  );
}
