"use client";

import { createContext, useContext, useEffect, useState } from "react";

export type FavoriUrun = {
  id: number;
  ad: string;
  fiyat: number;
  gorsel: string | null;
  kategori: string | null;
};

type FavoriBaglami = {
  favoriler: FavoriUrun[];
  sayi: number;
  ekle: (urun: FavoriUrun) => void;
  cikar: (id: number) => void;
  varMi: (id: number) => boolean;
  degistir: (urun: FavoriUrun) => void;
};

const Baglam = createContext<FavoriBaglami | null>(null);
const ANAHTAR = "hba-favoriler";

export function FavoriProvider({ children }: { children: React.ReactNode }) {
  const [favoriler, setFavoriler] = useState<FavoriUrun[]>([]);
  const [hazir, setHazir] = useState(false);

  useEffect(() => {
    try {
      const kayitli = localStorage.getItem(ANAHTAR);
      if (kayitli) setFavoriler(JSON.parse(kayitli));
    } catch {}
    setHazir(true);
  }, []);

  useEffect(() => {
    if (!hazir) return;
    try {
      localStorage.setItem(ANAHTAR, JSON.stringify(favoriler));
    } catch {}
  }, [favoriler, hazir]);

  const deger: FavoriBaglami = {
    favoriler,
    sayi: favoriler.length,
    ekle: (urun) => setFavoriler((p) => (p.some((f) => f.id === urun.id) ? p : [...p, urun])),
    cikar: (id) => setFavoriler((p) => p.filter((f) => f.id !== id)),
    varMi: (id) => favoriler.some((f) => f.id === id),
    degistir: (urun) =>
      setFavoriler((p) => (p.some((f) => f.id === urun.id) ? p.filter((f) => f.id !== urun.id) : [...p, urun])),
  };

  return <Baglam.Provider value={deger}>{children}</Baglam.Provider>;
}

export function favori() {
  const baglam = useContext(Baglam);
  if (!baglam) throw new Error("favori() ancak FavoriProvider içinde kullanılabilir");
  return baglam;
}
