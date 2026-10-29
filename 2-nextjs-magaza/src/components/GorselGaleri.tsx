"use client";

import { useState, useEffect } from "react";
import { X, ChevronLeft, ChevronRight } from "lucide-react";

export function GorselGaleri({ anaGorsel, urunAd }: { anaGorsel: string | null; urunAd: string }) {
  const [aktif, setAktif] = useState(0);
  const [lightbox, setLightbox] = useState(false);

  // Tek görsel — galeri gösterme
  if (!anaGorsel) {
    return (
      <div className="flex aspect-square items-center justify-center bg-[#f4f4f4]">
        <span className="text-8xl font-black text-black/10">{urunAd.charAt(0)}</span>
      </div>
    );
  }

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
            <img src={anaGorsel} alt={urunAd} className="h-full w-full object-contain p-10 transition-transform duration-300 hover:scale-105" />
          </div>
        </button>

        {/* Yakınlaştırma göstergesi */}
        <span className="absolute bottom-3 right-3 rounded-full bg-black/60 px-3 py-1 text-[10px] font-bold text-white backdrop-blur">
          🔍 Büyüt
        </span>
      </div>

      {/* Lightbox */}
      {lightbox && (
        <div className="fixed inset-0 z-[70] flex items-center justify-center p-4">
          <div onClick={() => setLightbox(false)} className="absolute inset-0 bg-black/90 backdrop-blur" />
          <button type="button" onClick={() => setLightbox(false)} aria-label="Kapat"
                  className="absolute right-4 top-4 z-10 rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20">
            <X className="h-6 w-6" />
          </button>
          <div className="relative max-h-[85vh] max-w-[85vw]">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={anaGorsel} alt={urunAd} className="max-h-[85vh] max-w-[85vw] object-contain" />
          </div>
        </div>
      )}
    </>
  );
}
