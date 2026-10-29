"use client";

import { useEffect, useState } from "react";

/**
 * Stok azaldığında aciliyet bildirimi gösterir.
 * "Son X adet!" + kaç kişinin sepete eklediği hissi verir.
 */
export function StokAciliyet({ musaitAdet, urunId }: { musaitAdet: number; urunId: number }) {
  const [goster, setGoster] = useState(false);

  useEffect(() => {
    if (musaitAdet > 0 && musaitAdet <= 5) {
      const t = setTimeout(() => setGoster(true), 1500);
      return () => clearTimeout(t);
    }
  }, [musaitAdet]);

  if (musaitAdet <= 0 || musaitAdet > 5) return null;

  // Ürün ID'sinden kararlı "kaç kişi sepete ekledi" (demo)
  const sepetten = 3 + ((urunId * 7) % 15);

  return (
    <>
      {/* Toast */}
      <div
        className={`fixed bottom-6 left-1/2 z-[60] -translate-x-1/2 transition-all duration-300 ${
          goster ? "translate-y-0 opacity-100" : "translate-y-[150%] opacity-0"
        }`}
      >
        <div className="flex items-center gap-3 rounded-2xl border border-red-200 bg-red-50 px-5 py-3 shadow-xl">
          <span className="relative flex h-3 w-3">
            <span className="absolute h-full w-full animate-ping rounded-full bg-red-400 opacity-75" />
            <span className="relative h-3 w-3 rounded-full bg-red-500" />
          </span>
          <div>
            <p className="text-sm font-bold text-red-700">Son {musaitAdet} adet!</p>
            <p className="text-xs text-red-500">Şu anda {sepetten} kişinin sepetinde</p>
          </div>
          <button type="button" onClick={() => setGoster(false)}
                  className="ml-2 text-red-300 transition hover:text-red-600">✕</button>
        </div>
      </div>

      {/* Kalıcı rozet */}
      <div className="mt-2 flex items-center gap-2 text-xs">
        <span className="rounded-full bg-red-50 px-3 py-1.5 font-bold text-red-600">
          ⚡ Son {musaitAdet} adet — acele edin!
        </span>
        <span className="text-black/30">{sepetten} kişi sepetinde</span>
      </div>
    </>
  );
}
