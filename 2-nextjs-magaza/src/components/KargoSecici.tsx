"use client";

import { UCRETSIZ_KARGO_LIMITI, KARGO_BOLGELERI, teslimSuresi } from "@/lib/kargo";

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
                {b.ad} — {b.fiyat} ₺ · {teslimSuresi(b.gun)}
              </option>
            ))}
          </select>
          <p className="mt-1 text-[11px] text-black/30">
            2.000 ₺ üzeri siparişlerde kargo ücretsiz ·{" "}
            <a href="/iade-degisim#kargo" target="_blank" className="underline underline-offset-2 hover:text-black">Kargo koşulları</a>
          </p>
        </>
      )}
    </div>
  );
}

export { UCRETSIZ_KARGO_LIMITI };
