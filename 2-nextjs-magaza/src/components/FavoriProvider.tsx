"use client";

import { createContext, useContext, useEffect, useState } from "react";
import { oturumlaSenkron } from "@/lib/hesapSenkron";
import { cerezKategorisiAktif } from "@/lib/cerez";

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
  const [giris, setGiris] = useState<{ girisYapmis: boolean; dogrulanmis: boolean | null }>({ girisYapmis: false, dogrulanmis: null });

  useEffect(() => {
    try {
      const kayitli = localStorage.getItem(ANAHTAR);
      if (kayitli) setFavoriler(JSON.parse(kayitli));
    } catch {}
    setHazir(true);
  }, []);

  // Oturum bilgisi: AuthProvider bağlamından gelir (bu sağlayıcı onun içinde ağaçlanır)
  useEffect(() => {
    const dinle = () => {
      const kullaniciRaw = localStorage.getItem("hba-user");
      setGiris({ girisYapmis: !!kullaniciRaw, dogrulanmis: kullaniciRaw ? JSON.parse(kullaniciRaw).email_verified ?? null : null });
    };
    dinle();
    window.addEventListener("hba-oturum", dinle);
    return () => window.removeEventListener("hba-oturum", dinle);
  }, []);

  // Girişte: tarayıcıdaki liste ile hesaptaki liste birleştirilir (cihazlar arası)
  useEffect(() => {
    if (!hazir || !giris.girisYapmis) return;
    oturumlaSenkron("favoriler", favoriler, setFavoriler, null, giris.dogrulanmis);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [hazir, giris.girisYapmis]);

  useEffect(() => {
    if (!hazir) return;
    try {
      // Favoriler fonksiyonel çerezdir — onay yoksa cihazda saklanmaz (girişli hesapta sunucuda yaşar)
      if (cerezKategorisiAktif('fonksiyonel')) localStorage.setItem(ANAHTAR, JSON.stringify(favoriler));
    } catch {}
    if (hazir && giris.girisYapmis) sunucuyaYaz("favoriler", favoriler);
  }, [favoriler, hazir, giris.girisYapmis]);

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

let yazimZamanlayici: ReturnType<typeof setTimeout> | null = null;

/** Değişiklikleri 800 ms sonra sunucuya yazar; giriş yoksa sessizce atlar. */
function sunucuyaYaz(alan: "favoriler" | "sepet", veri: unknown) {
  const token = localStorage.getItem("hba-token");
  if (!token) return;
  if (yazimZamanlayici) clearTimeout(yazimZamanlayici);
  yazimZamanlayici = setTimeout(() => {
    fetch("/api/hesap/durum", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ [alan]: veri }),
    }).catch(() => {});
  }, 800);
}

export function favori() {
  const baglam = useContext(Baglam);
  if (!baglam) throw new Error("favori() ancak FavoriProvider içinde kullanılabilir");
  return baglam;
}
