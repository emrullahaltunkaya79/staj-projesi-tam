"use client";

import { Heart } from "lucide-react";
import { favori, type FavoriUrun } from "./FavoriProvider";

export function KalpButonu({ urun, boyut = "normal" }: { urun: FavoriUrun; boyut?: "normal" | "buyuk" }) {
  const { varMi, degistir } = favori();
  const aktif = varMi(urun.id);
  const h = boyut === "buyuk" ? "h-5 w-5" : "h-4 w-4";

  return (
    <button
      type="button"
      onClick={(e) => {
        e.preventDefault();
        e.stopPropagation();
        degistir(urun);
      }}
      aria-label={aktif ? "Favorilerden çıkar" : "Favorilere ekle"}
      className={`transition hover:scale-110 active:scale-90 ${aktif ? "text-red-500" : "text-black/20 hover:text-red-400"}`}
    >
      <Heart className={`${h} ${aktif ? "fill-current" : ""}`} strokeWidth={2} />
    </button>
  );
}
