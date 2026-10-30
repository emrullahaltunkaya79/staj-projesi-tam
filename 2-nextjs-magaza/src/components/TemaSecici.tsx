"use client";

import { useEffect, useState } from "react";
import { Palette } from "lucide-react";

const ANAHTAR = "hba-tema";
const TEMALAR: { id: string; ad: string; renk: string }[] = [
  { id: "siyah", ad: "Siyah (Klasik)", renk: "#111111" },
  { id: "lacivert", ad: "Lacivert", renk: "#1e3a8a" },
  { id: "yesil", ad: "Yeşil", renk: "#065f46" },
  { id: "bordo", ad: "Bordo", renk: "#7f1d1d" },
  { id: "indigo", ad: "İndigo", renk: "#4f46e5" },
];

/** Marka rengi seçici — html'e tema sınıfı koyar, CSS değişkenleri devralır. */
export function TemaSecici() {
  const [aktif, setAktif] = useState("siyah");
  const [acik, setAcik] = useState(false);

  useEffect(() => {
    try {
      const kayitli = localStorage.getItem(ANAHTAR);
      if (kayitli) {
        setAktif(kayitli);
        document.documentElement.dataset.tema = kayitli;
      }
    } catch {}
  }, []);

  const sec = (id: string) => {
    setAktif(id);
    document.documentElement.dataset.tema = id;
    try { localStorage.setItem(ANAHTAR, id); } catch {}
    setAcik(false);
  };

  return (
    <div className="relative">
      <button
        type="button"
        onClick={() => setAcik(!acik)}
        aria-label="Tema rengi seç"
        title="Tema rengi seç"
        className="flex h-9 w-9 items-center justify-center rounded-full transition hover:bg-white/10"
      >
        <Palette className="h-4.5 w-4.5" />
      </button>
      {acik && (
        <div className="absolute right-0 top-11 z-50 w-40 rounded-2xl border border-black/10 bg-white p-3 shadow-2xl">
          <p className="mb-2 text-[10px] font-bold uppercase tracking-widest text-black/40">Marka Rengi</p>
          <div className="flex flex-col gap-1">
            {TEMALAR.map((t) => (
              <button
                key={t.id}
                type="button"
                onClick={() => sec(t.id)}
                className={`flex items-center gap-2.5 rounded-lg px-2 py-1.5 text-xs font-bold transition hover:bg-black/5 ${
                  aktif === t.id ? "text-black" : "text-black/60"
                }`}
              >
                <span className="h-4 w-4 rounded-full border border-black/10" style={{ background: t.renk }} />
                {t.ad}
                {aktif === t.id && <span className="ml-auto text-[10px]">✓</span>}
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
