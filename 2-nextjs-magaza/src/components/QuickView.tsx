"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { X, Loader2, Minus, Plus } from "lucide-react";
import { sepet, type SepetOgesi } from "./CartProvider";
import { sepeteUcur } from "@/lib/sepetFx";

type Ozet = {
  id: number;
  name: string;
  category: string | null;
  sale_price: number;
  discount_percent?: number | null;
  final_price?: number;
  available_quantity: number;
  in_stock: boolean;
  image_url: string | null;
  variants?: { id: number; name: string; stock_quantity: number; in_stock: boolean }[];
  rating_avg?: number | null;
  review_count?: number;
};

/** Kart hover'ında "Hızlı Bakış" — sayfadan ayrılmadan galeri + sepete ekle. */
export function QuickViewButonu({ urunId }: { urunId: number }) {
  const [acik, setAcik] = useState(false);
  return (
    <>
      <button
        type="button"
        onClick={(e) => { e.preventDefault(); e.stopPropagation(); setAcik(true); }}
        className="absolute inset-x-3 bottom-3 z-10 h-9 rounded-full bg-white/95 text-xs font-bold text-black opacity-0 shadow-lg backdrop-blur transition-all duration-200 hover:bg-white group-hover:opacity-100"
      >
        👁 Hızlı Bakış
      </button>
      {acik && <QuickViewModal urunId={urunId} kapat={() => setAcik(false)} />}
    </>
  );
}

function QuickViewModal({ urunId, kapat }: { urunId: number; kapat: () => void }) {
  const { ekle } = sepet();
  const [ozet, setOzet] = useState<Ozet | null>(null);
  const [hata, setHata] = useState<string | null>(null);
  const [adet, setAdet] = useState(1);
  const [varyantId, setVaryantId] = useState<number | null>(null);
  const [eklendi, setEklendi] = useState(false);
  const [yukleniyor, setYukleniyor] = useState(true);

  // Modal açılınca ürün özetini çek
  useEffect(() => {
    let iptal = false;
    fetch(`/api/urun-ozet/${urunId}`)
      .then((r) => (r.ok ? r.json() : Promise.reject()))
      .then((d) => { if (!iptal) { setOzet(d.data); setYukleniyor(false); } })
      .catch(() => { if (!iptal) { setHata("Ürün yüklenemedi."); setYukleniyor(false); } });
    return () => { iptal = true; };
  }, [urunId]);

  const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);
  const seciliVaryant = ozet?.variants?.find((v) => v.id === varyantId) ?? null;
  const stok = ozet ? (ozet.variants?.length ? (seciliVaryant?.stock_quantity ?? 0) : ozet.available_quantity) : 0;
  const fiyat = ozet ? (ozet.final_price ?? ozet.sale_price) : 0;

  const sepeteEkle = (e: React.MouseEvent) => {
    if (!ozet) return;
    const oge: Omit<SepetOgesi, "adet"> = {
      id: ozet.id,
      ad: ozet.name,
      fiyat,
      gorsel: `/api/img/${ozet.id}`,
      ...(seciliVaryant ? { secenek: seciliVaryant.name, varyantId: seciliVaryant.id } : {}),
    };
    ekle(oge, adet);
    sepeteUcur(oge.gorsel, e.currentTarget as HTMLElement);
    setEklendi(true);
    setTimeout(() => setEklendi(false), 1600);
  };

  return (
    <div className="fixed inset-0 z-[80] flex items-center justify-center p-4" role="dialog" aria-label="Hızlı bakış">
      <div onClick={kapat} className="absolute inset-0 bg-black/50 backdrop-blur-sm" />
      <button type="button" onClick={kapat} aria-label="Kapat"
              className="absolute right-4 top-4 z-10 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20">
        <X className="h-5 w-5" />
      </button>

      <div className="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
        {yukleniyor && (
          <div className="flex h-72 items-center justify-center">
            <Loader2 className="h-8 w-8 animate-spin text-black/30" />
          </div>
        )}
        {hata && <p className="py-16 text-center text-sm font-bold text-red-600">{hata}</p>}

        {ozet && (
          <div className="grid gap-6 sm:grid-cols-2">
            <div className="flex aspect-square items-center justify-center overflow-hidden rounded-2xl bg-[#f4f4f4]">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={`/api/img/${ozet.id}`} alt={ozet.name} className="h-full w-full object-contain p-8" />
            </div>

            <div className="flex flex-col">
              {ozet.category && (
                <span className="text-[10px] font-bold uppercase tracking-widest text-black/35">{ozet.category}</span>
              )}
              <h2 className="mt-1 text-xl font-black leading-tight">{ozet.name}</h2>

              <div className="mt-4 flex flex-wrap items-baseline gap-2">
                <span className="text-2xl font-black tabular-nums text-[var(--marka)]">{fmt(fiyat)} ₺</span>
                {(ozet.discount_percent ?? 0) > 0 && (
                  <>
                    <span className="text-sm tabular-nums text-black/40 line-through">{fmt(ozet.sale_price)} ₺</span>
                    <span className="rounded-full bg-red-600 px-2 py-0.5 text-[10px] font-black text-white">%{ozet.discount_percent} indirim</span>
                  </>
                )}
              </div>

              <p className="mt-2 text-xs font-medium text-black/50">
                {ozet.in_stock ? `Stokta ${stok} adet müsait` : "Tükendi"}
              </p>

              {ozet.variants && ozet.variants.length > 0 && (
                <div className="mt-4">
                  <p className="mb-2 text-[10px] font-bold uppercase tracking-widest text-black/40">Seçenek</p>
                  <div className="flex flex-wrap gap-2">
                    {ozet.variants.map((v) => (
                      <button
                        key={v.id}
                        type="button"
                        disabled={!v.in_stock}
                        onClick={() => setVaryantId(v.id)}
                        className={`min-w-[56px] rounded-xl border-2 px-3 py-1.5 text-xs font-bold transition ${
                          varyantId === v.id
                            ? "border-[var(--marka)] bg-[var(--marka)] text-white"
                            : v.in_stock
                              ? "border-black/10 bg-white text-black/60 hover:border-black/30"
                              : "cursor-not-allowed border-black/5 bg-black/5 text-black/25 line-through"
                        }`}
                      >
                        {v.name}
                      </button>
                    ))}
                  </div>
                </div>
              )}

              <div className="mt-auto pt-5">
                <div className="flex items-center gap-3">
                  <div className="flex h-11 items-center border border-black/15">
                    <button type="button" onClick={() => setAdet(Math.max(1, adet - 1))} className="h-full w-10 text-lg font-bold text-black/60 hover:text-black">−</button>
                    <span className="w-8 text-center text-sm font-bold tabular-nums">{adet}</span>
                    <button type="button" onClick={() => setAdet(Math.min(Math.max(stok, 1), adet + 1))} className="h-full w-10 text-lg font-bold text-black/60 hover:text-black">+</button>
                  </div>
                  <button
                    type="button"
                    disabled={!ozet.in_stock || ((ozet.variants?.length ?? 0) > 0 && !seciliVaryant)}
                    onClick={sepeteEkle}
                    className="h-11 flex-1 rounded-full bg-[var(--marka)] text-sm font-bold text-white transition hover:opacity-90 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                  >
                    {eklendi ? "✓ Sepete Eklendi" : "Sepete Ekle"}
                  </button>
                </div>
                <Link href={`/urun/${ozet.id}`} onClick={kapat} className="mt-3 block text-center text-xs font-bold text-black/50 underline underline-offset-4 transition hover:text-black">
                  Detaylı bilgiler →
                </Link>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
