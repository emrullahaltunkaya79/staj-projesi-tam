"use client";

import Link from "next/link";
import { sepet } from "@/components/CartProvider";

const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

export default function SepetSayfasi() {
  const { ogeler, araToplam, kdv, genelToplam, adetGuncelle, cikar } = sepet();

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[56px] font-black leading-none tracking-[-0.03em] sm:text-[84px]">Sepetim</h1>
        </div>

        {ogeler.length === 0 ? (
          <div className="py-20 text-center">
            <p className="text-sm text-black/50">Sepetiniz boş.</p>
            <Link href="/" className="mt-4 inline-block rounded-full bg-black px-6 py-3 text-sm font-bold text-white transition hover:bg-black/80">
              Alışverişe Başla
            </Link>
          </div>
        ) : (
          <div className="mt-10 grid grid-cols-1 gap-12 lg:grid-cols-3">
            <div className="space-y-6 lg:col-span-2">
              {ogeler.map((o) => (
                <div key={`${o.id}-${o.varyantId ?? 0}`} className="flex items-center gap-5">
                  <div className="flex h-24 w-24 shrink-0 items-center justify-center bg-[#f4f4f4]">
                    {o.gorsel ? (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img src={o.gorsel} alt={o.ad} className="h-full w-full object-contain p-2" />
                    ) : (
                      <span className="text-2xl font-black text-black/10">{o.ad.slice(0, 1)}</span>
                    )}
                  </div>
                  <div className="min-w-0 flex-1">
                    <Link href={`/urun/${o.id}`} className="text-[15px] font-semibold hover:underline underline-offset-4">
                      {o.ad}
                    </Link>
                    {o.secenek && <span className="ml-2 rounded-full bg-black/5 px-2 py-0.5 text-[11px] font-bold text-black/60">{o.secenek}</span>}
                    <p className="mt-1 text-sm text-black/50 tabular-nums">{fmt(o.fiyat)} ₺ / {o.birim ?? "adet"}</p>
                  </div>
                  <div className="flex h-10 items-center border border-black/20">
                    <button type="button" onClick={() => adetGuncelle(o.id, o.adet - 1, o.varyantId)} className="h-full w-9 font-bold text-black/60 hover:text-black">−</button>
                    <span className="w-10 text-center text-sm font-bold tabular-nums">{o.adet}</span>
                    <button type="button" onClick={() => adetGuncelle(o.id, o.adet + 1, o.varyantId)} className="h-full w-9 font-bold text-black/60 hover:text-black">+</button>
                  </div>
                  <div className="w-24 text-right text-[15px] font-bold tabular-nums">{fmt(o.fiyat * o.adet)} ₺</div>
                  <button type="button" onClick={() => cikar(o.id, o.varyantId)} aria-label="Kaldır" className="text-xs font-semibold text-black/30 hover:text-red-600">
                    Kaldır
                  </button>
                </div>
              ))}
            </div>

            <aside className="h-fit border-t border-black/20 pt-6 lg:border-t-0 lg:border-l lg:pl-8 lg:pt-0">
              <div className="space-y-3 text-sm">
                <div className="flex justify-between text-black/60"><span>Ara Toplam</span><span className="font-semibold text-black tabular-nums">{fmt(araToplam)} ₺</span></div>
                <div className="flex justify-between text-black/60"><span>KDV (%20)</span><span className="font-semibold text-black tabular-nums">{fmt(kdv)} ₺</span></div>
                <div className="flex justify-between border-t border-black/15 pt-4 text-xl font-black tabular-nums">{fmt(genelToplam)} ₺</div>
              </div>
              <Link href="/odeme" className="mt-8 flex h-12 items-center justify-center rounded-full bg-black text-sm font-bold text-white transition hover:bg-black/80 active:scale-95">
                Ödemeye Geç →
              </Link>
              <p className="mt-4 text-[11px] leading-relaxed text-black/40">
                Siparişi onayladığınızda ürünler arka ofiste 30 dakika rezerve tutulur.
              </p>
            </aside>
          </div>
        )}
      </div>
    </div>
  );
}
