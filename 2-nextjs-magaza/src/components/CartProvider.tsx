"use client";

import { createContext, useContext, useEffect, useMemo, useState } from "react";

export type SepetOgesi = {
  id: number;
  ad: string;
  fiyat: number;
  adet: number;
  gorsel?: string | null;
  birim?: string;
  secenek?: string;   // varyasyon etiketi (örn. "Siyah")
  varyantId?: number; // DB'deki product_variants.id
};

type SepetBaglami = {
  ogeler: SepetOgesi[];
  toplamAdet: number;
  araToplam: number;
  kdv: number;
  genelToplam: number;
      ekle: (yeniOge: Omit<SepetOgesi, "adet">, adet?: number) => void;
  adetGuncelle: (id: number, adet: number, varyantId?: number) => void;
  cikar: (id: number, varyantId?: number) => void;
  temizle: () => void;
};

const Baglam = createContext<SepetBaglami | null>(null);
const ANAHTAR = "hba-sepet";
const KDV_ORANI = 20;

export function CartProvider({ children }: { children: React.ReactNode }) {
  const [ogeler, setOgeler] = useState<SepetOgesi[]>([]);
  const [hazir, setHazir] = useState(false);
  const [bildirim, setBildirim] = useState<{ ad: string; adet: number; anahtar: number } | null>(null);

  // Toast 2.4 sn sonra kaybolur
  useEffect(() => {
    if (!bildirim) return;
    const z = setTimeout(() => setBildirim(null), 2400);
    return () => clearTimeout(z);
  }, [bildirim]);

  useEffect(() => {
    try {
      const kayitli = localStorage.getItem(ANAHTAR);
      if (kayitli) setOgeler(JSON.parse(kayitli));
    } catch {}
    setHazir(true);
  }, []);

  useEffect(() => {
    if (!hazir) return; // okuma tamamlanmadan boş dizi yazma
    try {
      localStorage.setItem(ANAHTAR, JSON.stringify(ogeler));
    } catch {}
  }, [ogeler, hazir]);

  const deger = useMemo<SepetBaglami>(() => {
    const araToplam = Math.round(ogeler.reduce((t, o) => t + o.fiyat * o.adet, 0) * 100) / 100;
    const kdv = Math.round(araToplam * (KDV_ORANI / 100) * 100) / 100;

    return {
      ogeler,
      toplamAdet: ogeler.reduce((t, o) => t + o.adet, 0),
      araToplam,
      kdv,
      genelToplam: Math.round((araToplam + kdv) * 100) / 100,
      ekle: (yeniOge, adet = 1) => {
        setBildirim({ ad: yeniOge.ad, adet, anahtar: Date.now() });
        setOgeler((mevcut) => {
          const varOlan = mevcut.find((o) => o.id === yeniOge.id && o.varyantId === yeniOge.varyantId);
          if (varOlan) {
            return mevcut.map((o) => (o.id === yeniOge.id && o.varyantId === yeniOge.varyantId ? { ...o, adet: o.adet + adet } : o));
          }
          return [...mevcut, { ...yeniOge, adet }];
        });
      },
      adetGuncelle: (id, adet, varyantId) =>
        setOgeler((mevcut) =>
          adet <= 0
            ? mevcut.filter((o) => !(o.id === id && o.varyantId === varyantId))
            : mevcut.map((o) => (o.id === id && o.varyantId === varyantId ? { ...o, adet } : o)),
        ),
      cikar: (id, varyantId) => setOgeler((mevcut) => mevcut.filter((o) => !(o.id === id && o.varyantId === varyantId))),
      temizle: () => setOgeler([]),
    };
  }, [ogeler]);

  return (
    <Baglam.Provider value={deger}>
      {children}
      {bildirim && (
        <div key={bildirim.anahtar}
             className="fixed bottom-20 right-4 z-[90] flex items-center gap-3 rounded-2xl border border-black/10 bg-white px-4 py-3 shadow-2xl sm:bottom-6"
             style={{ animation: "toast-giris .25s ease-out" }}>
          <span className="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--marka)] text-sm font-black text-white">✓</span>
          <div>
            <p className="text-xs font-black">{bildirim.ad}</p>
            <p className="text-[11px] text-black/50">sepete eklendi · {bildirim.adet} adet</p>
          </div>
        </div>
      )}
    </Baglam.Provider>
  );
}

export function sepet() {
  const baglam = useContext(Baglam);
  if (!baglam) throw new Error("sepet() ancak CartProvider içinde kullanılabilir");
  return baglam;
}
