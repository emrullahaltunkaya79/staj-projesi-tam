"use client";

import { BosDurum } from "@/components/BosDurum";

import Link from "next/link";
import { favori } from "@/components/FavoriProvider";
import { KalpButonu } from "@/components/KalpButonu";
import { sepet } from "@/components/CartProvider";
import { sepeteUcur } from "@/lib/sepetFx";
import { Trash2, ShoppingCart } from "lucide-react";

const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

export default function FavorilerSayfasi() {
  const { favoriler, cikar } = favori();
  const { ekle } = sepet();

  const sepeteEkle = (f: { id: number; ad: string; fiyat: number }) => {
    ekle({ id: f.id, ad: f.ad, fiyat: f.fiyat, gorsel: `/api/img/${f.id}` }, 1);
    sepeteUcur(`/api/img/${f.id}`, document.querySelector(`[data-favori-kart='${f.id}']`));
  };

  const tumunuSepeteEkle = () => {
    for (const f of favoriler) {
      ekle({ id: f.id, ad: f.ad, fiyat: f.fiyat, gorsel: `/api/img/${f.id}` }, 1);
    }
  };

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="flex items-end justify-between border-b border-black/20 pb-5">
          <div className="flex items-baseline gap-3">
            <h1 className="text-[15px] font-black uppercase tracking-wide text-black sm:text-[17px]">Favorilerim</h1>
            <span className="text-[13px] font-medium text-black/30">{favoriler.length} ürün</span>
          </div>
          {favoriler.length > 0 && (
            <button
              type="button"
              onClick={tumunuSepeteEkle}
              className="rounded-full bg-[var(--marka)] px-5 py-2.5 text-[13px] font-bold text-white transition hover:opacity-90 active:scale-95"
            >
              Tümünü Sepete Ekle ({favoriler.length})
            </button>
          )}
        </div>

        {favoriler.length === 0 ? (
        <BosDurum tur="favori" />
      ) : (
          <div className="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {favoriler.map((f) => (
              <div key={f.id} className="group relative flex flex-col" data-favori-kart={f.id}>
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
                <button
                  type="button"
                  onClick={() => sepeteEkle(f)}
                  className="mt-3 flex h-10 w-full items-center justify-center gap-2 rounded-full bg-black text-[12px] font-bold text-white transition hover:bg-black/80 active:scale-[0.98]"
                >
                  <ShoppingCart className="h-3.5 w-3.5" /> Sepete Ekle
                </button>
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
