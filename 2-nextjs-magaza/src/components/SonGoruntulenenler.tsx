"use client";

import { useEffect, useState } from "react";
import Link from "next/link";

const ANAHTAR = "hba-son-goruntulenen";

type Kayit = { id: number; ad: string; fiyat: number; gorsel: string | null };

/** Ürün detayı altında "Yakında İnceledikleriniz" şeridi; ziyaretleri localStorage'a yazar. */
export function SonGoruntulenenler({ suAnki }: { suAnki: Kayit }) {
  const [liste, setListe] = useState<Kayit[]>([]);

  useEffect(() => {
    try {
      const kayitli: Kayit[] = JSON.parse(localStorage.getItem(ANAHTAR) ?? "[]");
      const guncel = [suAnki, ...kayitli.filter((k) => k.id !== suAnki.id)].slice(0, 8);
      localStorage.setItem(ANAHTAR, JSON.stringify(guncel));
      setListe(guncel.slice(1)); // mevcut ürünü gösterme
    } catch {}
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [suAnki.id]);

  if (liste.length === 0) return null;

  return (
    <section className="mx-auto max-w-7xl px-5 pb-20 pt-4 sm:px-10">
      <div className="border-t border-black/10 pt-8">
        <h2 className="text-lg font-black tracking-tight">Yakında İnceledikleriniz</h2>
        <div className="mt-5 flex gap-4 overflow-x-auto pb-2 [scrollbar-width:thin]">
          {liste.map((k) => (
            <Link
              key={k.id}
              href={`/urun/${k.id}`}
              className="group flex w-36 shrink-0 flex-col gap-2 rounded-2xl border border-black/10 bg-white p-3 transition hover:-translate-y-0.5 hover:shadow-lg"
            >
              <div className="flex aspect-square items-center justify-center overflow-hidden rounded-xl bg-[#f4f4f4]">
                {k.gorsel ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={k.gorsel} alt={k.ad} loading="lazy" className="h-full w-full object-contain p-3" />
                ) : (
                  <span className="text-3xl font-black text-black/10">{k.ad.charAt(0)}</span>
                )}
              </div>
              <p className="line-clamp-2 min-h-[32px] text-xs font-semibold leading-snug text-black/70 group-hover:text-black">{k.ad}</p>
              <p className="text-sm font-black tabular-nums">
                {new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(k.fiyat)} ₺
              </p>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
}
