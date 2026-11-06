"use client";

import { useState } from "react";
import Link from "next/link";
import { Plus, Check } from "lucide-react";
import { sepet } from "./CartProvider";
import { kdvDahil } from "@/lib/fiyat";

export type PaketUrunu = {
  id: number;
  ad: string;
  marka: string | null;
  fiyat: number;
  gorsel: string | null;
  birim: string;
  musait: number;
};

const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

/** Ürün sayfası çapraz satış: ana ürün + birlikte alınan ürünler, seçilenler tek tıkla sepete. */
export function BirlikteAlinanlar({
  ana,
  ekler,
  kaynak,
}: {
  ana: PaketUrunu;
  ekler: PaketUrunu[];
  /** ortak: gerçek fatura birlikteliği · tamamlayici: aynı kategoriden daha uygun fiyatlı ürünler */
  kaynak: "ortak" | "tamamlayici";
}) {
  const { ekle } = sepet();
  const [secili, setSecili] = useState<Set<number>>(() => new Set(ekler.map((e) => e.id)));
  const [eklendi, setEklendi] = useState(false);

  const paket = [ana, ...ekler.filter((e) => secili.has(e.id))];
  const toplam = paket.reduce((t, u) => t + u.fiyat, 0);

  const degistir = (id: number) => {
    setEklendi(false);
    setSecili((s) => {
      const yeni = new Set(s);
      if (yeni.has(id)) yeni.delete(id);
      else yeni.add(id);
      return yeni;
    });
  };

  const sepeteEkle = () => {
    for (const u of paket) {
      ekle({ id: u.id, ad: u.ad, fiyat: u.fiyat, gorsel: u.gorsel, birim: u.birim }, 1, u.musait);
    }
    setEklendi(true);
  };

  return (
    <section className="rounded-2xl border border-black/10 p-5 sm:p-6">
      <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
        <h2 className="text-[15px] font-black uppercase tracking-wide text-black">
          {kaynak === "ortak" ? "Birlikte Sıkça Alınanlar" : "Bununla İyi Gider"}
        </h2>
        <span className="text-[12px] font-medium text-black/35">
          {kaynak === "ortak" ? "Gerçek satış verilerinden" : "Aynı kategoriden tamamlayıcı ürünler"}
        </span>
      </div>

      <div className="mt-5 flex flex-col gap-6 lg:flex-row lg:items-center">
        <div className="flex items-center gap-2 sm:gap-3">
          {[ana, ...ekler].map((u, i) => (
            <div key={u.id} className="flex items-center gap-2 sm:gap-3">
              {i > 0 && <Plus className="h-4 w-4 shrink-0 text-black/30" />}
              <Link
                href={`/urun/${u.id}`}
                className={`flex h-20 w-20 shrink-0 items-center justify-center bg-[#f4f4f4] transition sm:h-24 sm:w-24 ${
                  u.id === ana.id || secili.has(u.id) ? "" : "opacity-35"
                }`}
              >
                {u.gorsel ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={u.gorsel} alt={u.ad} loading="lazy" className="h-full w-full object-contain p-2" />
                ) : (
                  <span className="text-2xl font-black text-black/10">{u.ad.charAt(0)}</span>
                )}
              </Link>
            </div>
          ))}
        </div>

        <div className="min-w-0 flex-1 lg:border-l lg:border-black/10 lg:pl-6">
          <ul className="space-y-2">
            {[ana, ...ekler].map((u) => {
              const anaUrun = u.id === ana.id;
              return (
                <li key={u.id}>
                  <label className={`flex items-center gap-2.5 text-[13px] ${anaUrun ? "" : "cursor-pointer"}`}>
                    <input
                      type="checkbox"
                      checked={anaUrun || secili.has(u.id)}
                      disabled={anaUrun}
                      onChange={() => degistir(u.id)}
                      className="h-4 w-4 shrink-0 accent-black"
                    />
                    <span className="min-w-0 flex-1 truncate">
                      {anaUrun && <span className="font-bold text-black">Bu ürün: </span>}
                      <span className="text-black/75">{u.ad}</span>
                    </span>
                    <span className="shrink-0 font-bold tabular-nums text-black">{fmt(kdvDahil(u.fiyat))} ₺</span>
                  </label>
                </li>
              );
            })}
          </ul>

          <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-black/10 pt-4">
            <p className="text-[13px] text-black/55">
              {paket.length} ürün toplamı:{" "}
              <span className="text-[17px] font-black tabular-nums text-black">{fmt(kdvDahil(toplam))} ₺</span>
            </p>
            {eklendi ? (
              <Link href="/sepet" className="flex h-11 items-center gap-1.5 rounded-full bg-emerald-600 px-5 text-[13px] font-bold text-white transition hover:bg-emerald-700">
                <Check className="h-4 w-4" /> Sepete eklendi — Sepete git
              </Link>
            ) : (
              <button
                type="button"
                onClick={sepeteEkle}
                className="h-11 rounded-full bg-black px-5 text-[13px] font-bold text-white transition hover:bg-black/80 active:scale-95"
              >
                {paket.length === 1 ? "Sepete ekle" : `${paket.length} ürünü birlikte sepete ekle`}
              </button>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}
