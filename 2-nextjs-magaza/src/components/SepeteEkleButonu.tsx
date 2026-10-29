"use client";

import { useState } from "react";
import { sepet, type SepetOgesi } from "./CartProvider";

export function SepeteEkleButonu({ urun, musait }: { urun: Omit<SepetOgesi, "adet">; musait: number }) {
  const { ekle } = sepet();
  const [eklendi, setEklendi] = useState(false);
  const [adet, setAdet] = useState(1);

  if (musait < 1) {
    return (
      <button disabled className="h-12 w-full max-w-xs cursor-not-allowed bg-black/10 text-sm font-bold text-black/40">
        Stokta Yok
      </button>
    );
  }

  const ekleVeBildir = () => {
    ekle(urun, adet);
    setEklendi(true);
    setTimeout(() => setEklendi(false), 1600);
  };

  return (
    <div className="flex flex-wrap items-center gap-3">
      <div className="flex h-12 items-center border border-black/20">
        <button type="button" onClick={() => setAdet(Math.max(1, adet - 1))} className="h-full w-11 text-lg font-bold text-black/60 hover:text-black">
          −
        </button>
        <span className="w-10 text-center text-sm font-bold tabular-nums">{adet}</span>
        <button type="button" onClick={() => setAdet(Math.min(musait, adet + 1))} className="h-full w-11 text-lg font-bold text-black/60 hover:text-black">
          +
        </button>
      </div>

      <button
        type="button"
        onClick={ekleVeBildir}
        className="h-12 min-w-[180px] rounded-full bg-black px-8 text-sm font-bold text-white transition hover:bg-black/80 active:scale-95"
      >
        {eklendi ? "✓ Sepete Eklendi" : "Sepete Ekle"}
      </button>
    </div>
  );
}
