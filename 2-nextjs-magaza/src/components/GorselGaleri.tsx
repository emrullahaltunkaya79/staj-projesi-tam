"use client";

import { useState } from "react";
import { X, ChevronLeft, ChevronRight } from "lucide-react";

/** Çoklu görsel galerisi — tek görselde küçük resimler gizlenir, lightbox'ta gezinme var. */
export function GorselGaleri({ gorseller, urunAd }: { gorseller: string[]; urunAd: string }) {
  const [aktif, setAktif] = useState(0);
  const [lightbox, setLightbox] = useState(false);

  const adet = gorseller.length;
  const anaGorsel = gorseller[0] ?? null;

  // Görsel yok — harf yer tutucusu
  if (!anaGorsel) {
    return (
      <div className="flex aspect-square items-center justify-center bg-[#f4f4f4]">
        <span className="text-8xl font-black text-black/10">{urunAd.charAt(0)}</span>
      </div>
    );
  }

  const gosterilen = gorseller[aktif];

  const degistir = (yon: number) => {
    setAktif((s) => (s + yon + adet) % adet);
  };

  return (
    <>
      <div className="relative">
        <button
          type="button"
          onClick={() => setLightbox(true)}
          className="block w-full cursor-zoom-in"
          aria-label="Büyüt"
        >
          <div className="flex aspect-square items-center justify-center overflow-hidden bg-[#f4f4f4]">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img key={gosterilen} src={gosterilen} alt={urunAd} className="h-full w-full object-contain p-10 transition-transform duration-300 hover:scale-105" />
          </div>
        </button>

        {/* Yakınlaştırma göstergesi */}
        <span className="absolute bottom-3 right-3 rounded-full bg-black/60 px-3 py-1 text-[10px] font-bold text-white backdrop-blur">
          🔍 Büyüt
        </span>

        {/* Çoklu görsel: ok navigasyonu */}
        {adet > 1 && (
          <>
            <button type="button" onClick={() => degistir(-1)} aria-label="Önceki görsel"
                    className="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-2 shadow backdrop-blur transition hover:bg-white">
              <ChevronLeft className="h-4 w-4" />
            </button>
            <button type="button" onClick={() => degistir(1)} aria-label="Sonraki görsel"
                    className="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-2 shadow backdrop-blur transition hover:bg-white">
              <ChevronRight className="h-4 w-4" />
            </button>
            <span className="absolute left-3 top-3 rounded-full bg-black/60 px-2.5 py-1 text-[10px] font-bold text-white backdrop-blur">
              {aktif + 1} / {adet}
            </span>
          </>
        )}
      </div>

      {/* Küçük resimler */}
      {adet > 1 && (
        <div className="mt-3 flex gap-2">
          {gorseller.map((g, i) => (
            <button
              key={g + i}
              type="button"
              onClick={() => setAktif(i)}
              aria-label={`Görsel ${i + 1}`}
              className={`relative h-16 w-16 shrink-0 overflow-hidden border-2 bg-[#f4f4f4] transition ${
                i === aktif ? "border-black" : "border-transparent opacity-60 hover:opacity-100"
              }`}
            >
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={g} alt="" className="h-full w-full object-contain p-1.5" />
            </button>
          ))}
        </div>
      )}

      {/* Lightbox */}
      {lightbox && (
        <div className="fixed inset-0 z-[70] flex items-center justify-center p-4">
          <div onClick={() => setLightbox(false)} className="absolute inset-0 bg-black/90 backdrop-blur" />
          <button type="button" onClick={() => setLightbox(false)} aria-label="Kapat"
                  className="absolute right-4 top-4 z-10 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20">
            <X className="h-6 w-6" />
          </button>
          {adet > 1 && (
            <>
              <button type="button" onClick={() => degistir(-1)} aria-label="Önceki"
                      className="absolute left-4 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white transition hover:bg-white/20">
                <ChevronLeft className="h-6 w-6" />
              </button>
              <button type="button" onClick={() => degistir(1)} aria-label="Sonraki"
                      className="absolute right-4 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white transition hover:bg-white/20">
                <ChevronRight className="h-6 w-6" />
              </button>
            </>
          )}
          <div className="relative max-h-[85vh] max-w-[85vw]">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img key={gosterilen} src={gosterilen} alt={urunAd} className="max-h-[85vh] max-w-[85vw] object-contain" />
          </div>
        </div>
      )}
    </>
  );
}
