"use client";

import { useState } from "react";
import { Ruler, X } from "lucide-react";

/** Kombili/bedenli ürünlerde açılır beden tablosu — cm ölçüleriyle. */
export function BedenRehberi({ kategoriAd }: { kategoriAd?: string | null }) {
  const [acik, setAcik] = useState(false);

  const giyim = [
    { beden: "S", gogus: "86–90", bel: "74–78", boy: "170" },
    { beden: "M", gogus: "90–96", bel: "80–84", boy: "174" },
    { beden: "L", gogus: "96–102", bel: "86–90", boy: "178" },
    { beden: "XL", gogus: "102–108", bel: "92–96", boy: "182" },
    { beden: "XXL", gogus: "108–114", bel: "98–102", boy: "186" },
  ];

  return (
    <>
      <button type="button" onClick={() => setAcik(true)}
              className="flex items-center gap-1.5 text-[12px] font-bold text-black/45 underline underline-offset-2 transition hover:text-black">
        <Ruler className="h-3.5 w-3.5" /> Beden rehberi
      </button>

      {acik && (
        <div className="fixed inset-0 z-[80] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Beden rehberi">
          <button type="button" aria-label="Kapat" onClick={() => setAcik(false)}
                  className="absolute inset-0 cursor-default bg-black/50 backdrop-blur-[2px]" />
          <div className="relative max-h-[88vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
            <button type="button" onClick={() => setAcik(false)} aria-label="Kapat"
                    className="absolute right-4 top-4 text-black/30 transition hover:text-black">
              <X className="h-5 w-5" />
            </button>
            <div className="flex items-center gap-3">
              <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-black text-white">
                <Ruler className="h-5 w-5" />
              </span>
              <div>
                <p className="text-base font-black text-black">Beden Rehberi</p>
                <p className="text-xs text-black/45">Ölçüler santimetredir · vücut ölçüsüne göredir</p>
              </div>
            </div>

            <table className="mt-5 w-full border-collapse text-left text-[13px]">
              <thead>
                <tr className="border-b border-black/15 text-[11px] font-bold uppercase tracking-wide text-black/50">
                  <th className="py-2 pr-2">Beden</th>
                  <th className="py-2 pr-2">Göğüs</th>
                  <th className="py-2 pr-2">Bel</th>
                  <th className="py-2">Boy</th>
                </tr>
              </thead>
              <tbody>
                {giyim.map((s) => (
                  <tr key={s.beden} className="border-b border-black/5 last:border-0">
                    <td className="py-2 font-bold text-black">{s.beden}</td>
                    <td className="py-2 text-black/70">{s.gogus} cm</td>
                    <td className="py-2 text-black/70">{s.bel} cm</td>
                    <td className="py-2 text-black/70">{s.boy} cm</td>
                  </tr>
                ))}
              </tbody>
            </table>

            <div className="mt-4 rounded-xl bg-[#f8f8f8] p-3 text-[12px] leading-relaxed text-black/60">
              <b>Ölçüm ipucu:</b> Göğsünüzü kalın giysiyle değil, normal bir tişörtle, belinizin en dar
              noktasından ölçün. İki beden arasında kalıyorsanız daha rahat bir kullanım için büyük bedeni seçin.
              {(kategoriAd ?? "").length > 0 && <span className="block mt-1">Kategori: {kategoriAd}</span>}
            </div>

            <button type="button" onClick={() => setAcik(false)}
                    className="mt-4 h-11 w-full rounded-full bg-black text-sm font-bold text-white transition hover:bg-black/80">
              Anladım
            </button>
          </div>
        </div>
      )}
    </>
  );
}
