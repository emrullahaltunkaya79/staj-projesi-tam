"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { Plus, Check, X } from "lucide-react";
import { cerezKategorisiAktif } from "@/lib/cerez";

export type KarsilastirOgesi = {
  id: number;
  ad: string;
  kategori: string | null;
  fiyat: number;
  marka: string | null;
  puan: number | null;
  stokta: boolean;
  gorsel: string | null;
};

const ANAHTAR = "hba-karsilastir";
const AZAMI = 4;

type Depo = Record<string, KarsilastirOgesi[]>; // kategori → ürünler

function oku(): Depo {
  try {
    return JSON.parse(localStorage.getItem(ANAHTAR) ?? "{}");
  } catch {
    return {};
  }
}

function yaz(depo: Depo) {
  // Karşılaştırma listesi fonksiyonel çerezdir — onay yoksa seçim yalnızca oturumluk kalır
  if (cerezKategorisiAktif('fonksiyonel')) localStorage.setItem(ANAHTAR, JSON.stringify(depo));
  window.dispatchEvent(new Event("hba-karsilastir"));
}

/** Ürün sayfası: "Karşılaştır" ekleme/çıkarma (kategori başına en fazla 4 ürün). */
export function KarsilastirEkle({ urun }: { urun: KarsilastirOgesi }) {
  const [icinde, setIcinde] = useState(false);
  const [dolu, setDolu] = useState(false);

  const tazele = () => {
    const liste = oku()[urun.kategori ?? "Diger"] ?? [];
    setIcinde(liste.some((u) => u.id === urun.id));
    setDolu(liste.length >= AZAMI);
  };

  useEffect(() => {
    tazele();
    const dinle = () => tazele();
    window.addEventListener("hba-karsilastir", dinle);
    return () => window.removeEventListener("hba-karsilastir", dinle);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [urun.id, urun.kategori]);

  const toggle = () => {
    const depo = oku();
    const anahtar = urun.kategori ?? "Diger";
    const liste = depo[anahtar] ?? [];
    if (liste.some((u) => u.id === urun.id)) {
      depo[anahtar] = liste.filter((u) => u.id !== urun.id);
    } else {
      if (liste.length >= AZAMI) return;
      depo[anahtar] = [...liste, urun];
    }
    yaz(depo);
    tazele();
  };

  return (
    <button
      type="button"
      onClick={toggle}
      disabled={!icinde && dolu}
      title={!icinde && dolu ? `Aynı kategoride en fazla ${AZAMI} ürün karşılaştırılabilir` : undefined}
      className={`mt-4 flex w-fit items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-[12px] font-bold transition ${
        icinde ? "border-[var(--marka)] bg-[var(--marka)] text-white" : "border-black/15 text-black/60 hover:border-black/40 disabled:opacity-40"
      }`}
    >
      {icinde ? <><Check className="h-3.5 w-3.5" /> Karşılaştırmada</> : <><Plus className="h-3.5 w-3.5" /> Karşılaştırmaya ekle</>}
    </button>
  );
}

/** Sol altta sabit çubuk: seçili ürün sayısı + karşılaştırma sayfasına git. */
export function KarsilastirCubugu() {
  const [sayi, setSayi] = useState(0);

  useEffect(() => {
    const dinle = () => {
      const depo = oku();
      setSayi(Object.values(depo).reduce((t, l) => t + l.length, 0));
    };
    dinle();
    window.addEventListener("hba-karsilastir", dinle);
    return () => window.removeEventListener("hba-karsilastir", dinle);
  }, []);

  if (sayi < 2) return null;

  return (
    <div className="fixed bottom-5 left-5 z-[45] flex items-center gap-3 rounded-full border border-black/10 bg-white py-2 pl-4 pr-2 shadow-2xl">
      <span className="text-[13px] font-bold text-black/70">
        <span className="mr-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-[var(--marka)] text-[11px] font-black text-white">{sayi}</span>
        karşılaştırmada
      </span>
      <Link href="/karsilastir" className="flex h-9 items-center rounded-full bg-black px-4 text-[12px] font-bold text-white transition hover:bg-black/80">
        Karşılaştır →
      </Link>
      <button type="button" onClick={() => { localStorage.removeItem(ANAHTAR); window.dispatchEvent(new Event("hba-karsilastir")); }}
              aria-label="Karşılaştırma listesini temizle" className="p-1 text-black/30 transition hover:text-red-600">
        <X className="h-4 w-4" />
      </button>
    </div>
  );
}
