"use client";

import { useState, useTransition } from "react";
import Link from "next/link";
import { ChevronDown, X, SlidersHorizontal } from "lucide-react";
import type { Urun } from "@/lib/backoffice";
import { indirimliFiyat } from "@/lib/backoffice";
import { urunRenkleri, urunBoyutlari, facetSecenekleri, RENKLER } from "@/lib/facetler";
import { useMemo } from "react";

export type Filtreler = {
  minFiyat: string;
  maxFiyat: string;
  sadeceStok: boolean;
  kdvDahil: boolean;
  renkler: string[];
  boyutlar: string[];
};

export const bosFiltreler: Filtreler = { minFiyat: "", maxFiyat: "", sadeceStok: false, kdvDahil: false, renkler: [], boyutlar: [] };

export function filtrele(urunler: Urun[], f: Filtreler): Urun[] {
  let sonuc = urunler;
  if (f.minFiyat) sonuc = sonuc.filter((u) => indirimliFiyat(u) >= Number(f.minFiyat));
  if (f.maxFiyat) sonuc = sonuc.filter((u) => indirimliFiyat(u) <= Number(f.maxFiyat));
  if (f.sadeceStok) sonuc = sonuc.filter((u) => u.in_stock);
  if (f.renkler.length > 0)
    sonuc = sonuc.filter((u) => {
      const ur = urunRenkleri(u.name, u.varyant_adlari);
      return f.renkler.some((r) => ur.includes(r));
    });
  if (f.boyutlar.length > 0)
    sonuc = sonuc.filter((u) => {
      const ub = urunBoyutlari(u.name);
      return f.boyutlar.some((b) => ub.includes(b));
    });
  return sonuc;
}

export function urunFiltrele(
  tumUrunler: Urun[],
  kategori: string | null,
  f: Filtreler,
  sirala?: string,
): Urun[] {
  let sonuc = kategori ? tumUrunler.filter((u) => u.category === kategori) : tumUrunler;
  sonuc = filtrele(sonuc, f);
  switch (sirala) {
    case "fiyat-artan": return [...sonuc].sort((a, b) => indirimliFiyat(a) - indirimliFiyat(b));
    case "fiyat-azalan": return [...sonuc].sort((a, b) => indirimliFiyat(b) - indirimliFiyat(a));
    case "yeni": return [...sonuc].sort((a, b) => b.id - a.id);
    default: return sonuc;
  }
}

/** Açılır-kapanır filtre grubu — smooth açılış efektli */
function Grup({ baslik, children, varsayilanAcik = true }: { baslik: string; children: React.ReactNode; varsayilanAcik?: boolean }) {
  const [acik, setAcik] = useState(varsayilanAcik);

  return (
    <div className="border-b border-black/10 pb-4">
      <button
        type="button"
        onClick={() => setAcik(!acik)}
        className="flex w-full items-center justify-between py-2 text-[13px] font-bold text-black transition hover:text-black/60"
      >
        {baslik}
        <ChevronDown
          className={`h-3.5 w-3.5 text-black/40 transition-transform duration-300 ${acik ? "rotate-180" : ""}`}
        />
      </button>
      <div
        className={`grid transition-[grid-template-rows,opacity] duration-300 ease-out ${
          acik ? "grid-rows-[1fr] opacity-100" : "grid-rows-[0fr] opacity-0"
        }`}
      >
        <div className="overflow-hidden">{children}</div>
      </div>
    </div>
  );
}

/**
 * Mağaza sol sidebar'ı: kategoriler + fiyat aralığı + stok durumu.
 * Masaüstünde sabit sol kolon, mobilde kayar overlay.
 */
export function Sidebar({
  kategoriler,
  aktifKategori,
  filtreler,
  onFiltreDegistir,
  onTemizle,
  toplamUrun,
  urunler = [],
}: {
  kategoriler: { ad: string; sayi: number }[];
  aktifKategori: string | null;
  filtreler: Filtreler;
  onFiltreDegistir: (f: Filtreler) => void;
  onTemizle: () => void;
  toplamUrun: number;
  urunler?: Urun[];
}) {
  const [mobilAcik, setMobilAcik] = useState(false);
  const [masaustuAcik, setMasaustuAcik] = useState(true);
  const [, startTransition] = useTransition();

  const degistir = (kismi: Partial<Filtreler>) => {
    startTransition(() => onFiltreDegistir({ ...filtreler, ...kismi }));
  };

  const renkSecenekleri = useMemo(
    () => facetSecenekleri(urunler.map((u) => ({ degerler: urunRenkleri(u.name, u.varyant_adlari) })), 12),
    [urunler],
  );
  const boyutSecenekleri = useMemo(
    () => facetSecenekleri(urunler.map((u) => ({ degerler: urunBoyutlari(u.name) })), 14),
    [urunler],
  );

  const aktifFiltreSayisi =
    (filtreler.minFiyat ? 1 : 0) + (filtreler.maxFiyat ? 1 : 0) + (filtreler.sadeceStok ? 1 : 0) +
    filtreler.renkler.length + filtreler.boyutlar.length;

  const icerik = (
    <div className="flex h-full flex-col">
      <div className="flex items-center justify-between border-b border-black/15 pb-3">
        <span className="flex items-center gap-2 text-[15px] font-black text-black">
          <SlidersHorizontal className="h-4 w-4" /> Filtreler
          {aktifFiltreSayisi > 0 && (
            <span className="rounded-full bg-black px-2 py-0.5 text-[10px] font-bold text-white">{aktifFiltreSayisi}</span>
          )}
        </span>
        {aktifFiltreSayisi > 0 && (
          <button type="button" onClick={onTemizle} className="text-[11px] font-bold text-black/40 underline underline-offset-2 transition hover:text-black">
            Temizle
          </button>
        )}
      </div>

      <Grup baslik="Kategoriler">
        <div className="space-y-1 pt-1">
          {kategoriler.map((k) => (
            <Link
              key={k.ad}
              href={aktifKategori === k.ad ? "/" : `/kategori/${encodeURIComponent(k.ad)}`}
              className={`flex items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition ${
                aktifKategori === k.ad
                  ? "bg-black text-white"
                  : "text-black/60 hover:bg-black/5 hover:text-black"
              }`}
            >
              {k.ad}
              <span className={`text-[11px] tabular-nums ${aktifKategori === k.ad ? "text-white/60" : "text-black/30"}`}>
                {k.sayi}
              </span>
            </Link>
          ))}
        </div>
      </Grup>

      <Grup baslik="Fiyat Aralığı (₺)">
        <div className="flex items-center gap-2 pt-1">
          <input
            type="number"
            placeholder="min"
            value={filtreler.minFiyat}
            onChange={(e) => degistir({ minFiyat: e.target.value })}
            className="h-9 w-full border border-black/15 px-2.5 text-[12px] tabular-nums focus:border-black focus:outline-none"
          />
          <span className="text-black/30">—</span>
          <input
            type="number"
            placeholder="max"
            value={filtreler.maxFiyat}
            onChange={(e) => degistir({ maxFiyat: e.target.value })}
            className="h-9 w-full border border-black/15 px-2.5 text-[12px] tabular-nums focus:border-black focus:outline-none"
          />
        </div>
      </Grup>

      {renkSecenekleri.length > 0 && (
        <Grup baslik="Renk">
          <div className="flex flex-wrap gap-1.5 pt-1">
            {renkSecenekleri.map(({ deger, sayi }) => {
              const secili = filtreler.renkler.includes(deger);
              const hex = RENKLER.find((r) => r.ad === deger)?.hex ?? "#888";
              return (
                <button
                  key={deger}
                  type="button"
                  onClick={() =>
                    degistir({
                      renkler: secili
                        ? filtreler.renkler.filter((r) => r !== deger)
                        : [...filtreler.renkler, deger],
                    })
                  }
                  title={`${deger} (${sayi})`}
                  className={`flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-bold transition ${
                    secili ? "border-black bg-black text-white" : "border-black/15 text-black/60 hover:border-black/40"
                  }`}
                >
                  <span
                    className="h-3 w-3 rounded-full border border-black/20"
                    style={{ background: hex }}
                  />
                  {deger}
                  <span className={secili ? "text-white/50" : "text-black/30"}>{sayi}</span>
                </button>
              );
            })}
          </div>
        </Grup>
      )}

      {boyutSecenekleri.length > 0 && (
        <Grup baslik="Boyut / Ölçü">
          <div className="flex flex-wrap gap-1.5 pt-1">
            {boyutSecenekleri.map(({ deger, sayi }) => {
              const secili = filtreler.boyutlar.includes(deger);
              return (
                <button
                  key={deger}
                  type="button"
                  onClick={() =>
                    degistir({
                      boyutlar: secili
                        ? filtreler.boyutlar.filter((b) => b !== deger)
                        : [...filtreler.boyutlar, deger],
                    })
                  }
                  className={`rounded-full border px-2.5 py-1 text-[11px] font-bold tabular-nums transition ${
                    secili ? "border-black bg-black text-white" : "border-black/15 text-black/60 hover:border-black/40"
                  }`}
                >
                  {deger}
                  <span className={`ml-1 ${secili ? "text-white/50" : "text-black/30"}`}>{sayi}</span>
                </button>
              );
            })}
          </div>
        </Grup>
      )}

      <Grup baslik="Stok Durumu">
        <label className="flex cursor-pointer items-center gap-3 pt-1 text-[13px] text-black/70 transition hover:text-black">
          <input
            type="checkbox"
            checked={filtreler.sadeceStok}
            onChange={(e) => degistir({ sadeceStok: e.target.checked })}
            className="h-4 w-4 rounded border-black/20 accent-black"
          />
          Sadece stokta olanlar
        </label>
      </Grup>

      <div className="mt-auto pt-4">
        <p className="text-[11px] text-black/30">{toplamUrun} ürün listeleniyor</p>
      </div>
    </div>
  );

  return (
    <>
      {/* Masaüstü: açılır-kapanır sidebar (sticky yok — sayfa ile birlikte kayar) */}
      <div
        className={`hidden h-fit shrink-0 overflow-hidden transition-[width,opacity,margin] duration-300 ease-in-out lg:block ${
          masaustuAcik ? "w-56 opacity-100" : "w-0 -mr-8 opacity-0"
        }`}
      >
        <div className="w-56">{icerik}</div>
      </div>

      {/* Masaüstü: sidebar aç/kapa butonu */}
      <button
        type="button"
        onClick={() => setMasaustuAcik(!masaustuAcik)}
        title={masaustuAcik ? "Filtreleri gizle" : "Filtreleri göster"}
        className={`hidden h-9 w-9 shrink-0 items-center justify-center rounded-full border border-black/15 text-black/40 transition-all duration-300 hover:border-black hover:text-black lg:flex ${
          masaustuAcik ? "" : "rotate-180"
        }`}
      >
        <ChevronDown className="h-4 w-4 -rotate-90" />
      </button>

      {/* Mobil: açma butonu (tam genişlik, üstte) */}
      <button
        type="button"
        onClick={() => setMobilAcik(true)}
        className="flex w-full items-center justify-center gap-2 rounded-full border border-black/15 px-4 py-2.5 text-[13px] font-bold text-black/70 transition hover:border-black hover:text-black lg:hidden"
      >
        <SlidersHorizontal className="h-3.5 w-3.5" />
        Filtreler {aktifFiltreSayisi > 0 && <span className="rounded-full bg-black px-1.5 text-[10px] text-white">{aktifFiltreSayisi}</span>}
      </button>

      {/* Overlay */}
      <div
        className={`fixed inset-0 z-50 lg:hidden ${mobilAcik ? "" : "pointer-events-none"}`}
        aria-hidden={!mobilAcik}
      >
        <div
          onClick={() => setMobilAcik(false)}
          className={`absolute inset-0 bg-black/40 backdrop-blur-[2px] transition-opacity duration-300 ${mobilAcik ? "opacity-100" : "opacity-0"}`}
        />
        <div
          className={`absolute inset-y-0 left-0 w-72 bg-white p-5 shadow-2xl transition-transform duration-300 ease-out ${
            mobilAcik ? "translate-x-0" : "-translate-x-full"
          }`}
        >
          <button
            type="button"
            onClick={() => setMobilAcik(false)}
            aria-label="Kapat"
            className="absolute right-3 top-3 text-black/40 transition hover:text-black"
          >
            <X className="h-5 w-5" />
          </button>
          {icerik}
        </div>
      </div>
    </>
  );
}
