"use client";

import { useState, useMemo } from "react";
import Link from "next/link";
import { usePathname, useSearchParams } from "next/navigation";
import { Sidebar, bosFiltreler, urunFiltrele, type Filtreler } from "@/components/Sidebar";
import { UrunKarti } from "@/components/Catalog";
import type { Urun } from "@/lib/backoffice";

export function KatalogIcerik({
  tumUrunler,
  kategori,
  baslik,
}: {
  tumUrunler: Urun[];
  kategori: string | null;
  baslik: string;
}) {
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const [filtreler, setFiltreler] = useState<Filtreler>(bosFiltreler);

  const sirala = searchParams.get("sirala") ?? "";

  const kategoriler = useMemo(() => {
    const m = new Map<string, number>();
    for (const u of tumUrunler) {
      const ad = u.category ?? "Diğer";
      m.set(ad, (m.get(ad) ?? 0) + 1);
    }
    return [...m.entries()].map(([ad, sayi]) => ({ ad, sayi }));
  }, [tumUrunler]);

  const sonuc = useMemo(
    () => urunFiltrele(tumUrunler, kategori, filtreler, sirala),
    [tumUrunler, kategori, filtreler, sirala],
  );

  const secenekler: [string, string][] = [
    ["", "Önerilen"],
    ["fiyat-artan", "₺ ↑"],
    ["fiyat-azalan", "₺ ↓"],
    ["yeni", "Yeni"],
  ];

  const hrefyle = (s: string) => {
    const p = new URLSearchParams();
    if (s) p.set("sirala", s);
    return `${pathname}${p.size > 0 ? `?${p}` : ""}`;
  };

  return (
    <div className="px-5 pb-24 pt-2 sm:px-10">
      <div className="mx-auto max-w-7xl">
        {/* Başlık */}
        <div className="border-b border-black/20 pb-4">
          <div className="flex flex-wrap items-end justify-between gap-4">
            <div className="flex items-baseline gap-3">
              <h1 className="text-[22px] font-black leading-none tracking-[-0.02em] sm:text-[28px]">{baslik}</h1>
              <span className="text-[13px] font-medium text-black/30">{sonuc.length} ürün</span>
            </div>
            <div className="flex flex-wrap items-center gap-2">
              {secenekler.map(([deger, etiket]) => (
                <Link key={deger || "varsayilan"} href={hrefyle(deger)}
                      className={`rounded-full px-3 py-1 text-[12px] font-bold transition ${sirala === deger ? "bg-black text-white" : "text-black/50 hover:text-black"}`}>
                  {etiket}
                </Link>
              ))}
            </div>
          </div>
        </div>

        {/* Sidebar + grid */}
        <div className="mt-6 flex flex-wrap gap-4 lg:flex-nowrap lg:gap-8">
          <Sidebar
            kategoriler={kategoriler}
            aktifKategori={kategori}
            filtreler={filtreler}
            onFiltreDegistir={setFiltreler}
            onTemizle={() => setFiltreler(bosFiltreler)}
            toplamUrun={sonuc.length}
          />

          <div className="min-w-0 flex-1">

            {sonuc.length === 0 ? (
              <div className="py-20 text-center">
                <p className="text-sm text-black/50">Bu filtrelerle ürün bulunamadı.</p>
                <button type="button" onClick={() => setFiltreler(bosFiltreler)}
                        className="mt-3 text-sm font-bold underline underline-offset-4">Filtreleri temizle</button>
              </div>
            ) : (
              <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5">
                {sonuc.map((u) => (
                  <UrunKarti key={u.id} urun={u} />
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
