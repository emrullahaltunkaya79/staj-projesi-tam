"use client";

import { useState } from "react";

export type Varyasyon = {
  ad: string;
  secenekler: string[];
};

export function VaryasyonSecici({
  varyasyonlar,
  secimler,
  onSec,
}: {
  varyasyonlar: Varyasyon[];
  secimler: Record<string, string>;
  onSec: (ad: string, deger: string) => void;
}) {
  if (varyasyonlar.length === 0) return null;

  return (
    <div className="space-y-4">
      {varyasyonlar.map((v) => (
        <div key={v.ad}>
          <p className="mb-2 text-[10px] font-bold uppercase tracking-widest text-black/40">
            {v.ad} <span className="text-red-400">*</span>
          </p>
          <div className="flex flex-wrap gap-2">
            {v.secenekler.map((sec) => {
              const aktif = secimler[v.ad] === sec;
              return (
                <button
                  key={sec}
                  type="button"
                  onClick={() => onSec(v.ad, sec)}
                  className={`min-w-[48px] rounded-xl border-2 px-4 py-2 text-sm font-bold transition ${
                    aktif
                      ? "border-black bg-black text-white"
                      : "border-black/10 bg-white text-black/60 hover:border-black/30"
                  }`}
                >
                  {sec}
                </button>
              );
            })}
          </div>
        </div>
      ))}
    </div>
  );
}

/**
 * Ürün adından varyasyon çıkarır (demo amaçlı):
 * "Tişört" → Beden + Renk, "Kablolu" → Renk vs.
 */
export function urunVaryasyonlari(urunAd: string): Varyasyon[] {
  const ad = urunAd.toLocaleLowerCase("tr");
  const sonuc: Varyasyon[] = [];

  if (ad.includes("tişört") || ad.includes("t-shirt") || ad.includes("gömlek") || ad.includes("sweat")) {
    sonuc.push({ ad: "Beden", secenekler: ["S", "M", "L", "XL", "XXL"] });
  }

  if (ad.includes("kablo") || ad.includes("kulaklık") || ad.includes("mouse") || ad.includes("klavye")) {
    sonuc.push({ ad: "Renk", secenekler: ["Siyah", "Beyaz", "Gri"] });
  }

  if (ad.includes("mat") || ad.includes("havlu")) {
    sonuc.push({ ad: "Renk", secenekler: ["Siyah", "Mavi", "Yeşil", "Kırmızı"] });
  }

  return sonuc;
}
