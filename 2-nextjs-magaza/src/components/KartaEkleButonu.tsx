"use client";

import { useState } from "react";
import { ShoppingCart } from "lucide-react";
import { sepet, type SepetOgesi } from "./CartProvider";
import { sepeteUcur } from "@/lib/sepetFx";

/** Hepsiburada tarzı yeşil kart butonu — stokta yoksa devre dışı. */
export function KartaEkleButonu({ urun, musait }: { urun: Omit<SepetOgesi, "adet">; musait: number }) {
  const { ekle, ogeler } = sepet();
  const [eklendi, setEklendi] = useState(false);

  // Sepetteki adet hesaba katılır: stoğun tamamı sepetteyse buton kilitlenir
  const sepetteki = ogeler.find((o) => o.id === urun.id && o.varyantId === urun.varyantId)?.adet ?? 0;
  const kalan = musait - sepetteki;

  if (musait < 1) {
    return (
      <button disabled className="mt-2 h-8 w-full rounded-full bg-black/5 text-[11px] font-bold text-black/35">
        Stokta Yok
      </button>
    );
  }

  if (kalan <= 0) {
    return (
      <button disabled className="mt-2 h-8 w-full rounded-full bg-black/5 text-[11px] font-bold text-black/35">
        ✓ Sepette (tükendi)
      </button>
    );
  }

  return (
    <button
      type="button"
      onClick={(e) => {
        e.preventDefault();
        e.stopPropagation();
        ekle(urun, Math.min(1, kalan), musait);
        sepeteUcur(urun.gorsel ?? null, e.currentTarget);
        setEklendi(true);
        setTimeout(() => setEklendi(false), 1500);
      }}
      className={`mt-2 flex h-8 w-full items-center justify-center gap-1.5 rounded-full text-[11px] font-bold text-white transition active:scale-[0.97] ${
        eklendi ? "bg-black/80" : "bg-[var(--marka)] hover:opacity-90"
      }`}
    >
      <ShoppingCart className="h-3 w-3" />
      {eklendi ? "✓ Eklendi" : "Sepete Ekle"}
    </button>
  );
}
