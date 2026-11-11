"use client";

import { useEffect, useState, useMemo } from "react";
import Link from "next/link";
import { X } from "lucide-react";
import { usePathname, useSearchParams } from "next/navigation";
import { Sidebar, bosFiltreler, urunFiltrele, type Filtreler } from "@/components/Sidebar";
import { UrunKarti } from "@/components/Catalog";
import type { Urun } from "@/lib/backoffice";

export function KatalogIcerik({
  tumUrunler,
  kategori,
  baslik,
  marka = null,
  sidebar = true,
  baslikGizle = false,
  aramaTerimi = null,
}: {
  tumUrunler: Urun[];
  kategori: string | null;
  baslik: string;
  marka?: string | null;
  sidebar?: boolean;
  baslikGizle?: boolean;
  /** /ara sayfası: kategori sayfaya gitmek yerine sonuçlar içinde filtrelenir */
  aramaTerimi?: string | null;
}) {
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const [filtreler, setFiltreler] = useState<Filtreler>(bosFiltreler);
  const [gosterilen, setGosterilen] = useState(24);
  const [aramaKategori, setAramaKategori] = useState<string | null>(() => searchParams.get("kategori"));
  // Kategori içi alt kategori (Bilgisayar → Klavye/Mouse/Monitör…) — çip satırından seçilir
  const [altKategori, setAltKategori] = useState<string | null>(null);

  const sirala = searchParams.get("sirala") ?? "";
  const etkinKategori = aramaTerimi ? aramaKategori : kategori;

  // Filtre/kategori/sıralama değişince sayfalama başa döner
  useEffect(() => setGosterilen(24), [etkinKategori, marka, filtreler, sirala, altKategori]);

  const kategoriler = useMemo(() => {
    const m = new Map<string, number>();
    for (const u of tumUrunler) {
      const ad = u.category ?? "Diğer";
      m.set(ad, (m.get(ad) ?? 0) + 1);
    }
    return [...m.entries()].map(([ad, sayi]) => ({ ad, sayi }));
  }, [tumUrunler]);

  const sonuc = useMemo(() => {
    const liste = marka ? tumUrunler.filter((u) => u.brand === marka) : tumUrunler;
    const kategoriyeGore = urunFiltrele(liste, etkinKategori, filtreler, sirala);
    return altKategori ? kategoriyeGore.filter((u) => u.alt_kategori === altKategori) : kategoriyeGore;
  }, [tumUrunler, etkinKategori, marka, filtreler, sirala, altKategori]);

  // Alt kategori çipleri: etkin kategorinin (veya tümünün) alt kategori dağılımı
  const altListe = useMemo(() => {
    const taban = etkinKategori ? tumUrunler.filter((u) => u.category === etkinKategori) : tumUrunler;
    const sayac = new Map<string, number>();
    for (const u of taban) {
      if (u.alt_kategori) sayac.set(u.alt_kategori, (sayac.get(u.alt_kategori) ?? 0) + 1);
    }
    return [...sayac.entries()].sort((a, b) => b[1] - a[1] || a[0].localeCompare(b[0], "tr"));
  }, [tumUrunler, etkinKategori]);

  const tumunuTemizle = () => {
    setFiltreler(bosFiltreler);
    if (aramaTerimi) setAramaKategori(null);
  };

  const secenekler: [string, string][] = [
    ["", "Önerilen"],
    ["fiyat-artan", "₺ ↑"],
    ["fiyat-azalan", "₺ ↓"],
    ["yeni", "Yeni"],
  ];

  const hrefyle = (s: string) => {
    const p = new URLSearchParams();
    if (aramaTerimi) {
      p.set("q", aramaTerimi);
      if (aramaKategori) p.set("kategori", aramaKategori);
    }
    if (s) p.set("sirala", s);
    return `${pathname}${p.size > 0 ? `?${p}` : ""}`;
  };

  // Seçili filtreler tek tıkla kaldırılabilir çipler olarak grid'in üstünde gösterilir
  const cipler: { etiket: string; kaldir: () => void }[] = [
    ...(aramaTerimi && aramaKategori ? [{ etiket: aramaKategori, kaldir: () => setAramaKategori(null) }] : []),
    ...(filtreler.marka ? [{ etiket: `Marka: ${filtreler.marka}`, kaldir: () => setFiltreler({ ...filtreler, marka: null }) }] : []),
    ...(filtreler.model ? [{ etiket: `Model: ${filtreler.model}`, kaldir: () => setFiltreler({ ...filtreler, model: null }) }] : []),
    ...(filtreler.altKategori ? [{ etiket: filtreler.altKategori, kaldir: () => setFiltreler({ ...filtreler, altKategori: null }) }] : []),
    ...(filtreler.minFiyat || filtreler.maxFiyat
      ? [{
          etiket: `${filtreler.minFiyat || "0"} – ${filtreler.maxFiyat || "∞"} ₺`,
          kaldir: () => setFiltreler({ ...filtreler, minFiyat: "", maxFiyat: "" }),
        }]
      : []),
    ...filtreler.renkler.map((r) => ({ etiket: r, kaldir: () => setFiltreler({ ...filtreler, renkler: filtreler.renkler.filter((x) => x !== r) }) })),
    ...filtreler.bedenler.map((b) => ({ etiket: `Beden ${b}`, kaldir: () => setFiltreler({ ...filtreler, bedenler: filtreler.bedenler.filter((x) => x !== b) }) })),
    ...filtreler.boyutlar.map((b) => ({ etiket: b, kaldir: () => setFiltreler({ ...filtreler, boyutlar: filtreler.boyutlar.filter((x) => x !== b) }) })),
    ...(filtreler.sadeceStok ? [{ etiket: "Stokta", kaldir: () => setFiltreler({ ...filtreler, sadeceStok: false }) }] : []),
  ];

  const sonucYok = (
    <div className="py-20 text-center">
      <p className="text-sm text-black/50">Bu filtrelerle ürün bulunamadı.</p>
      <button type="button" onClick={tumunuTemizle}
              className="mt-3 text-sm font-bold underline underline-offset-4">Filtreleri temizle</button>
    </div>
  );

  return (
    <div className="px-5 pb-24 pt-2 sm:px-10">
      <div className="mx-auto max-w-7xl">
        {/* Başlık */}
        {!baslikGizle && (
        <div className="border-b border-black/20 pb-4">
          <div className="flex flex-wrap items-end justify-between gap-4">
            <div className="flex items-baseline gap-3">
              <h1 className="text-[15px] font-black uppercase tracking-wide sm:text-[17px]">{baslik}</h1>
              <span className="text-[13px] font-medium text-black/30">
                {aramaTerimi ? `"${aramaTerimi}" için ${sonuc.length} sonuç` : `${sonuc.length} ürün`}
              </span>
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
        )}

        {/* Grid — sidebar'lı (katalog sayfaları) veya tam genişlik (ana sayfa) */}
        {sidebar ? (
          <div className="mt-6 flex flex-wrap gap-4 lg:flex-nowrap lg:gap-8">
            <Sidebar
              kategoriler={kategoriler}
              aktifKategori={etkinKategori}
              filtreler={filtreler}
              onFiltreDegistir={setFiltreler}
              onTemizle={tumunuTemizle}
              toplamUrun={sonuc.length}
              urunler={etkinKategori ? tumUrunler.filter((u) => u.category === etkinKategori) : tumUrunler}
              onKategoriSec={aramaTerimi ? (ad) => setAramaKategori(ad === aramaKategori ? null : ad) : undefined}
            />

            <div className="min-w-0 flex-1">
              {sidebar && altListe.length > 0 && (
                <div className="mb-4 flex flex-wrap items-center gap-1.5">
                  <span className="text-[11px] font-bold uppercase tracking-wide text-black/35">Alt kategoriler:</span>
                  {altListe.map(([ad, sayi]) => {
                    const secili = altKategori === ad;
                    return (
                      <button key={ad} type="button" onClick={() => setAltKategori(secili ? null : ad)}
                              className={`rounded-full border px-3 py-1.5 text-[12px] font-bold transition ${
                                secili ? "border-black bg-black text-white" : "border-black/15 text-black/60 hover:border-black/40 hover:text-black"
                              }`}>
                        {ad} <span className={secili ? "text-white/60" : "text-black/30"}>{sayi}</span>
                      </button>
                    );
                  })}
                  {altKategori && (
                    <button type="button" onClick={() => setAltKategori(null)}
                            className="text-[11px] font-bold text-black/40 underline underline-offset-2 transition hover:text-black">
                      temizle
                    </button>
                  )}
                </div>
              )}
              {cipler.length > 0 && (
                <div className="mb-4 flex flex-wrap items-center gap-1.5">
                  {cipler.map((c) => (
                    <button key={c.etiket} type="button" onClick={c.kaldir}
                            className="flex items-center gap-1 rounded-full bg-black/5 px-3 py-1 text-[11px] font-bold text-black/70 transition hover:bg-black/10">
                      {c.etiket}
                      <X className="h-3 w-3 text-black/40" />
                    </button>
                  ))}
                  <button type="button" onClick={tumunuTemizle}
                          className="px-2 text-[11px] font-bold text-black/40 underline underline-offset-2 transition hover:text-black">
                    Tümünü temizle
                  </button>
                </div>
              )}

              {sonuc.length === 0 ? sonucYok : (
                <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-5 2xl:grid-cols-6">
                  {sonuc.slice(0, gosterilen).map((u) => (
                    <UrunKarti key={u.id} urun={u} />
                  ))}
                </div>
              )}
            </div>
          </div>
        ) : (
          <div className="mt-6">
            {sonuc.length === 0 ? sonucYok : (
              <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6 2xl:grid-cols-7">
                {sonuc.slice(0, gosterilen).map((u) => (
                  <UrunKarti key={u.id} urun={u} />
                ))}
              </div>
            )}
          </div>
        )}

        {/* Sayfalama: daha fazla göster */}
        {sonuc.length > gosterilen && (
          <div className="mt-10 text-center">
            <button
              type="button"
              onClick={() => setGosterilen((g) => g + 24)}
              className="rounded-full border border-black/15 px-8 py-3 text-[13px] font-bold text-black/70 transition hover:border-black hover:text-black"
            >
              Daha fazla göster ({sonuc.length - gosterilen} ürün kaldı)
            </button>
            <p className="mt-2 text-[11px] font-medium tabular-nums text-black/35">
              {Math.min(gosterilen, sonuc.length)} / {sonuc.length} ürün
            </p>
          </div>
        )}
      </div>
    </div>
  );
}
