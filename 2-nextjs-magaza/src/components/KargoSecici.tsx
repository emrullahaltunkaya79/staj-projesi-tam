"use client";

import { useState } from "react";

const KARGO_BOLGELERI = [
  { id: "istanbul-avr", ad: "İstanbul (Avrupa)", fiyat: 29 },
  { id: "istanbul-anadolu", ad: "İstanbul (Anadolu)", fiyat: 34 },
  { id: "ankara", ad: "Ankara", fiyat: 39 },
  { id: "izmir", ad: "İzmir", fiyat: 42 },
  { id: "bursa", ad: "Bursa", fiyat: 39 },
  { id: "antalya", ad: "Antalya", fiyat: 45 },
  { id: "adana", ad: "Adana", fiyat: 45 },
  { id: "konya", ad: "Konya", fiyat: 42 },
  { id: "diger", ad: "Diğer Şehirler", fiyat: 55 },
];

const UCRETSIZ_KARGO_LIMITI = 2000;

export function KargoSecici({
  toplamTutar,
  secilen,
  onSec,
}: {
  toplamTutar: number;
  secilen: string;
  onSec: (bolgeId: string, fiyat: number) => void;
}) {
  const ucretsiz = toplamTutar >= UCRETSIZ_KARGO_LIMITI;

  return (
    <div>
      <label className="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-black/40">
        Kargo Bölgesi *
      </label>
      {ucretsiz ? (
        <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
          <span className="h-2 w-2 rounded-full bg-emerald-500" />
          Ücretsiz kargo (2.000 ₺ üzeri)
        </div>
      ) : (
        <>
          <select
            value={secilen}
            onChange={(e) => {
              const b = KARGO_BOLGELERI.find((x) => x.id === e.target.value);
              if (b) onSec(b.id, b.fiyat);
            }}
            className="h-12 w-full border border-black/15 bg-white px-3 text-sm focus:border-black focus:outline-none"
          >
            <option value="">Seçiniz</option>
            {KARGO_BOLGELERI.map((b) => (
              <option key={b.id} value={b.id}>
                {b.ad} — {b.fiyat} ₺
              </option>
            ))}
          </select>
          <p className="mt-1 text-[11px] text-black/30">
            2.000 ₺ üzeri siparişlerde kargo ücretsiz
          </p>
        </>
      )}
    </div>
  );
}

export { KARGO_BOLGELERI, UCRETSIZ_KARGO_LIMITI };
