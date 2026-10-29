"use client";

import { createContext, useContext, useEffect, useMemo, useState } from "react";

export type SepetOgesi = {
  id: number;
  ad: string;
  fiyat: number;
  adet: number;
  gorsel?: string | null;
  birim?: string;
};

type SepetBaglami = {
  ogeler: SepetOgesi[];
  toplamAdet: number;
  araToplam: number;
  kdv: number;
  genelToplam: number;
      ekle: (yeniOge: Omit<SepetOgesi, "adet">, adet?: number) => void;
  adetGuncelle: (id: number, adet: number) => void;
  cikar: (id: number) => void;
  temizle: () => void;
};

const Baglam = createContext<SepetBaglami | null>(null);
const ANAHTAR = "hba-sepet";
const KDV_ORANI = 20;

export function CartProvider({ children }: { children: React.ReactNode }) {
  const [ogeler, setOgeler] = useState<SepetOgesi[]>([]);
  const [hazir, setHazir] = useState(false);

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
      ekle: (yeni, adet = 1) =>
        setOgeler((mevcut) => {
          const varOlan = mevcut.find((o) => o.id === yeni.id);
          if (varOlan) {
            return mevcut.map((o) => (o.id === yeni.id ? { ...o, adet: o.adet + adet } : o));
          }
          return [...mevcut, { ...yeni, adet }];
        }),
      adetGuncelle: (id, adet) =>
        setOgeler((mevcut) =>
          adet <= 0 ? mevcut.filter((o) => o.id !== id) : mevcut.map((o) => (o.id === id ? { ...o, adet } : o)),
        ),
      cikar: (id) => setOgeler((mevcut) => mevcut.filter((o) => o.id !== id)),
      temizle: () => setOgeler([]),
    };
  }, [ogeler]);

  return <Baglam.Provider value={deger}>{children}</Baglam.Provider>;
}

export function sepet() {
  const baglam = useContext(Baglam);
  if (!baglam) throw new Error("sepet() ancak CartProvider içinde kullanılabilir");
  return baglam;
}
