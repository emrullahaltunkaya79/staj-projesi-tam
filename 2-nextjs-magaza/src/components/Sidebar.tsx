"use client";

import { useState, useTransition } from "react";
import Link from "next/link";
import { ChevronDown, X, SlidersHorizontal } from "lucide-react";
import type { Urun } from "@/lib/backoffice";
import { indirimliFiyat } from "@/lib/backoffice";
import { kdvDahil } from "@/lib/fiyat";
import { urunRenkleri, urunBoyutlari, facetSecenekleri, RENKLER, BEDEN_SIRASI } from "@/lib/facetler";
import { useMemo } from "react";

export type Filtreler = {
  marka: string | null;
  minFiyat: string;
  maxFiyat: string;
  sadeceStok: boolean;
  kdvDahil: boolean;
  renkler: string[];
  boyutlar: string[];
  bedenler: string[];
};

export const bosFiltreler: Filtreler = { marka: null, minFiyat: "", maxFiyat: "", sadeceStok: false, kdvDahil: false, renkler: [], boyutlar: [], bedenler: [] };

export function filtrele(urunler: Urun[], f: Filtreler): Urun[] {
  let sonuc = urunler;
  if (f.marka) sonuc = sonuc.filter((u) => u.brand === f.marka);
  // Müşteri KDV dahil fiyat görür ve girer; karşılaştırma da KDV dahil yapılır
  if (f.minFiyat) sonuc = sonuc.filter((u) => kdvDahil(indirimliFiyat(u)) >= Number(f.minFiyat));
  if (f.maxFiyat) sonuc = sonuc.filter((u) => kdvDahil(indirimliFiyat(u)) <= Number(f.maxFiyat));
  if (f.sadeceStok) sonuc = sonuc.filter((u) => u.in_stock);
  if (f.renkler.length > 0)
    sonuc = sonuc.filter((u) => {
      const ur = urunRenkleri(u.name, u.varyant_adlari);
      return f.renkler.some((r) => ur.includes(r));
    });
  if (f.boyutlar.length > 0)
    sonuc = sonuc.filter((u) => {
      const ub = urunBoyutlari(u.name);
      return f.boyutlar.some((b) => ub.includes(b));
    });
  if (f.bedenler.length > 0)
    sonuc = sonuc.filter((u) =>
      f.bedenler.some((b) =>
        (u.varyantlar ?? []).some((v) => (v.tur === "beden" || v.tur === "kombi") && v.beden === b && v.stokta),
      ),
    );
  return sonuc;
}

export function urunFiltrele(
  tumUrunler: Urun[],
  kategori: string | null,
  f: Filtreler,
  sirala?: string,
): Urun[] {
  let sonuc = kategori ? tumUrunler.filter((u) => u.category === kategori) : tumUrunler;
  sonuc = filtrele(sonuc, f);
  switch (sirala) {
    case "fiyat-artan": return [...sonuc].sort((a, b) => indirimliFiyat(a) - indirimliFiyat(b));
    case "fiyat-azalan": return [...sonuc].sort((a, b) => indirimliFiyat(b) - indirimliFiyat(a));
    case "yeni": return [...sonuc].sort((a, b) => b.id - a.id);
    default: return sonuc;
  }
}

/** Açılır-kapanır filtre grubu — smooth açılış efektli */
function Grup({ baslik, children, varsayilanAcik = true }: { baslik: string; children: React.ReactNode; varsayilanAcik?: boolean }) {
  const [acik, setAcik] = useState(varsayilanAcik);

  return (
    <div className="border-b border-black/10 pb-4">
      <button
        type="button"
        onClick={() => setAcik(!acik)}
        className="flex w-full items-center justify-between py-2 text-[13px] font-bold text-black transition hover:text-black/60"
      >
        {baslik}
        <ChevronDown
          className={`h-3.5 w-3.5 text-black/40 transition-transform duration-300 ${acik ? "rotate-180" : ""}`}
        />
      </button>
      <div
        className={`grid transition-[grid-template-rows,opacity] duration-300 ease-out ${
          acik ? "grid-rows-[1fr] opacity-100" : "grid-rows-[0fr] opacity-0"
        }`}
      >
        <div className="overflow-hidden">{children}</div>
      </div>
    </div>
  );
}

/**
 * Mağaza sol sidebar'ı: kategoriler + fiyat aralığı + stok durumu.
 * Masaüstünde sabit sol kolon, mobilde kayar overlay.
 */
export function Sidebar({
  kategoriler,
  aktifKategori,
  filtreler,
  onFiltreDegistir,
  onTemizle,
  toplamUrun,
  urunler = [],
  onKategoriSec,
}: {
  kategoriler: { ad: string; sayi: number }[];
  aktifKategori: string | null;
  filtreler: Filtreler;
  onFiltreDegistir: (f: Filtreler) => void;
  onTemizle: () => void;
  toplamUrun: number;
  urunler?: Urun[];
  /** Verilirse kategori satırı sayfaya gitmez, bu geri çağrıyla yerinde filtreler (arama sonuçları) */
  onKategoriSec?: (ad: string) => void;
}) {
  const [mobilAcik, setMobilAcik] = useState(false);
  const [masaustuAcik, setMasaustuAcik] = useState(true);
  const [, startTransition] = useTransition();

  const degistir = (kismi: Partial<Filtreler>) => {
    startTransition(() => onFiltreDegistir({ ...filtreler, ...kismi }));
  };

  const renkSecenekleri = useMemo(
    () => facetSecenekleri(urunler.map((u) => ({ degerler: urunRenkleri(u.name, u.varyant_adlari) })), 12),
    [urunler],
  );
  const boyutSecenekleri = useMemo(
    () => facetSecenekleri(urunler.map((u) => ({ degerler: urunBoyutlari(u.name) })), 14),
    [urunler],
  );
  const bedenSecenekleri = useMemo(() => {
    const sayac = new Map<string, number>();
    for (const u of urunler) {
      for (const v of u.varyantlar ?? []) {
        // düz bedenler + kombilerın beden boyutu
        if ((v.tur === "beden" || v.tur === "kombi") && v.stokta && v.beden) sayac.set(v.beden, (sayac.get(v.beden) ?? 0) + 1);
      }
    }
    return [...sayac.entries()]
      .sort((a, b) => (BEDEN_SIRASI.indexOf(b[0]) - BEDEN_SIRASI.indexOf(a[0])) || b[1] - a[1])
      .map(([deger, sayi]) => ({ deger, sayi }));
  }, [urunler]);

  // Kategori -> marka listesi (accordion alt satırları için)
  const kategoriMarkalari = useMemo(() => {
    const harita = new Map<string, Map<string, number>>();
    for (const u of urunler) {
      const kategori = u.category ?? "Diğer";
      if (!u.brand) continue;
      if (!harita.has(kategori)) harita.set(kategori, new Map());
      const ic = harita.get(kategori)!;
      ic.set(u.brand, (ic.get(u.brand) ?? 0) + 1);
    }
    const sonuc = new Map<string, { ad: string; sayi: number }[]>();
    for (const [k, ic] of harita) {
      sonuc.set(
        k,
        [...ic.entries()]
          .map(([ad, sayi]) => ({ ad, sayi }))
          .sort((a, b) => b.sayi - a.sayi || a.ad.localeCompare(b.ad, "tr")),
      );
    }
    return sonuc;
  }, [urunler]);

  // Kart式 accordion durumu: belirtilmemişse yalnızca aktif kategori açıktır
  const [ozelAcik, setOzelAcik] = useState<Record<string, boolean>>({});
  const kategoriAcikMi = (ad: string) => ozelAcik[ad] ?? aktifKategori === ad;
  const kategoriToggle = (ad: string) =>
    setOzelAcik((o) => ({ ...o, [ad]: !(o[ad] ?? aktifKategori === ad) }));

  const aktifFiltreSayisi =
    (filtreler.marka ? 1 : 0) +
    (filtreler.minFiyat ? 1 : 0) + (filtreler.maxFiyat ? 1 : 0) + (filtreler.sadeceStok ? 1 : 0) +
    filtreler.renkler.length + filtreler.boyutlar.length + filtreler.bedenler.length;

  const icerik = (
    <div className="flex h-full flex-col rounded-2xl border border-black/10 bg-white p-4 shadow-sm">
      <div className="flex items-center justify-between border-b border-black/15 pb-3">
        <span className="flex items-center gap-2 text-[15px] font-black text-black">
          <SlidersHorizontal className="h-4 w-4" /> Filtreler
          {aktifFiltreSayisi > 0 && (
            <span className="rounded-full bg-[var(--marka)] px-2 py-0.5 text-[10px] font-bold text-white">{aktifFiltreSayisi}</span>
          )}
        </span>
        {aktifFiltreSayisi > 0 && (
          <button type="button" onClick={onTemizle} className="text-[11px] font-bold text-black/40 underline underline-offset-2 transition hover:text-black">
            Temizle
          </button>
        )}
      </div>

      <Grup baslik="Kategoriler">
        {filtreler.marka && (
          <button
            type="button"
            onClick={() => degistir({ marka: null })}
            className="mb-2 flex w-full items-center justify-between rounded-lg bg-[var(--marka)] px-2.5 py-1.5 text-[11px] font-bold text-white transition hover:opacity-90"
          >
            <span className="truncate">Marka: {filtreler.marka}</span>
            <X className="h-3.5 w-3.5 shrink-0" />
          </button>
        )}
        <div className="space-y-1 pt-1">
          {kategoriler.map((k) => {
            const markalar = kategoriMarkalari.get(k.ad) ?? [];
            const acik = kategoriAcikMi(k.ad);
            return (
              <div key={k.ad}>
                <div
                  className={`flex items-center rounded-lg transition ${
                    aktifKategori === k.ad
                      ? "bg-[var(--marka)] text-white shadow-sm"
                      : "text-black/60 hover:bg-black/5 hover:text-black"
                  }`}
                >
                  {onKategoriSec ? (
                    <button
                      type="button"
                      onClick={() => onKategoriSec(k.ad)}
                      className="min-w-0 flex-1 truncate px-3 py-2 text-left text-[13px] font-medium"
                    >
                      {k.ad}
                    </button>
                  ) : (
                    <Link
                      href={aktifKategori === k.ad ? "/" : `/kategori/${encodeURIComponent(k.ad)}`}
                      className="min-w-0 flex-1 truncate px-3 py-2 text-[13px] font-medium"
                    >
                      {k.ad}
                    </Link>
                  )}
                  {markalar.length > 0 && (
                    <button
                      type="button"
                      onClick={() => kategoriToggle(k.ad)}
                      aria-label={`${k.ad} markaları`}
                      title={`${k.ad} markaları (${markalar.length})`}
                      className={`flex h-8 w-7 shrink-0 items-center justify-center transition ${
                        aktifKategori === k.ad ? "text-white/60 hover:text-white" : "text-black/30 hover:text-black"
                      }`}
                    >
                      <ChevronDown className={`h-3.5 w-3.5 transition-transform duration-300 ${acik ? "rotate-180" : ""}`} />
                    </button>
                  )}
                  <span
                    className={`pr-3 text-[11px] tabular-nums ${aktifKategori === k.ad ? "text-white/60" : "text-black/30"}`}
                  >
                    {k.sayi}
                  </span>
                </div>
                {acik && markalar.length > 0 && (
                  <div className="ml-3 space-y-0.5 border-l border-black/10 pb-1 pl-2 pt-0.5">
                    {markalar.map((m) => {
                      const secili = filtreler.marka === m.ad;
                      return (
                        <button
                          key={m.ad}
                          type="button"
                          onClick={() => degistir({ marka: secili ? null : m.ad })}
                          className={`flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-[12px] font-medium transition ${
                            secili
                              ? "bg-[var(--marka)] text-white"
                              : "text-black/55 hover:bg-black/5 hover:text-black"
                          }`}
                        >
                          <span className="truncate">{m.ad}</span>
                          <span className={`shrink-0 text-[10.5px] tabular-nums ${secili ? "text-white/60" : "text-black/30"}`}>
                            {m.sayi}
                          </span>
                        </button>
                      );
                    })}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </Grup>

      <Grup baslik="Fiyat Aralığı (₺)">
        <div className="flex items-center gap-2 pt-1">
          <input
            type="number"
            placeholder="min"
            value={filtreler.minFiyat}
            onChange={(e) => degistir({ minFiyat: e.target.value })}
            className="h-9 w-full border border-black/15 px-2.5 text-[12px] tabular-nums focus:border-black focus:outline-none"
          />
          <span className="text-black/30">—</span>
          <input
            type="number"
            placeholder="max"
            value={filtreler.maxFiyat}
            onChange={(e) => degistir({ maxFiyat: e.target.value })}
            className="h-9 w-full border border-black/15 px-2.5 text-[12px] tabular-nums focus:border-black focus:outline-none"
          />
        </div>
      </Grup>

      {renkSecenekleri.length > 0 && (
        <Grup baslik="Renk">
          <div className="flex flex-wrap gap-1.5 pt-1">
            {renkSecenekleri.map(({ deger, sayi }) => {
              const secili = filtreler.renkler.includes(deger);
              const hex = RENKLER.find((r) => r.ad === deger)?.hex ?? "#888";
              return (
                <button
                  key={deger}
                  type="button"
                  onClick={() =>
                    degistir({
                      renkler: secili
                        ? filtreler.renkler.filter((r) => r !== deger)
                        : [...filtreler.renkler, deger],
                    })
                  }
                  title={`${deger} (${sayi})`}
                  className={`flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-bold transition ${
                    secili ? "border-[var(--marka)] bg-[var(--marka)] text-white" : "border-black/15 text-black/60 hover:border-black/40"
                  }`}
                >
                  <span
                    className="h-3 w-3 rounded-full border border-black/20"
                    style={{ background: hex }}
                  />
                  {deger}
                  <span className={secili ? "text-white/50" : "text-black/30"}>{sayi}</span>
                </button>
              );
            })}
          </div>
        </Grup>
      )}

      {boyutSecenekleri.length > 0 && (
        <Grup baslik="Boyut / Ölçü">
          <div className="flex flex-wrap gap-1.5 pt-1">
            {boyutSecenekleri.map(({ deger, sayi }) => {
              const secili = filtreler.boyutlar.includes(deger);
              return (
                <button
                  key={deger}
                  type="button"
                  onClick={() =>
                    degistir({
                      boyutlar: secili
                        ? filtreler.boyutlar.filter((b) => b !== deger)
                        : [...filtreler.boyutlar, deger],
                    })
                  }
                  className={`rounded-full border px-2.5 py-1 text-[11px] font-bold tabular-nums transition ${
                    secili ? "border-[var(--marka)] bg-[var(--marka)] text-white" : "border-black/15 text-black/60 hover:border-black/40"
                  }`}
                >
                  {deger}
                  <span className={`ml-1 ${secili ? "text-white/50" : "text-black/30"}`}>{sayi}</span>
                </button>
              );
            })}
          </div>
        </Grup>
      )}

      {bedenSecenekleri.length > 0 && (
        <Grup baslik="Beden">
          <div className="flex flex-wrap gap-1.5 pt-1">
            {bedenSecenekleri.map(({ deger, sayi }) => {
              const secili = filtreler.bedenler.includes(deger);
              return (
                <button
                  key={deger}
                  type="button"
                  onClick={() =>
                    degistir({
                      bedenler: secili
                        ? filtreler.bedenler.filter((b) => b !== deger)
                        : [...filtreler.bedenler, deger],
                    })
                  }
                  title={`${deger} (${sayi})`}
                  className={`rounded-full border px-2.5 py-1 text-[11px] font-bold tabular-nums transition ${
                    secili ? "border-[var(--marka)] bg-[var(--marka)] text-white" : "border-black/15 text-black/60 hover:border-black/40"
                  }`}
                >
                  {deger}
                  <span className={`ml-1 ${secili ? "text-white/50" : "text-black/30"}`}>{sayi}</span>
                </button>
              );
            })}
          </div>
        </Grup>
      )}

      <Grup baslik="Stok Durumu">
        <label className="flex cursor-pointer items-center gap-3 pt-1 text-[13px] text-black/70 transition hover:text-black">
          <input
            type="checkbox"
            checked={filtreler.sadeceStok}
            onChange={(e) => degistir({ sadeceStok: e.target.checked })}
            className="h-4 w-4 rounded border-black/20 accent-black"
          />
          Sadece stokta olanlar
        </label>
      </Grup>

      <div className="mt-auto pt-4">
        <p className="text-[11px] text-black/30">{toplamUrun} ürün listeleniyor</p>
      </div>
    </div>
  );

  return (
    <>
      {/* Masaüstü: sticky filtre kolonu — Trendyol/Hepsiburada düzeni. Ürün listesi boyunca
          başlığın altında sabit kalır; liste bitince sayfayla birlikte akar, hiçbir şeye binmez.
          Panel yüksekliği kompakt tutulur (iç scroll) ki kolonda her zaman yapışma payı kalsın. */}
      <div
        className={`hidden h-fit shrink-0 overflow-hidden transition-[width,opacity,margin] duration-300 ease-in-out lg:sticky lg:top-36 lg:block lg:self-start ${
          masaustuAcik ? "w-56 opacity-100" : "w-0 -mr-8 opacity-0"
        }`}
      >
        <div className="max-h-[min(480px,calc(100vh-12rem))] w-56 overflow-y-auto [scrollbar-width:thin]">{icerik}</div>
      </div>

      {/* Masaüstü: sidebar aç/kapa butonu */}
      <button
        type="button"
        onClick={() => setMasaustuAcik(!masaustuAcik)}
        title={masaustuAcik ? "Filtreleri gizle" : "Filtreleri göster"}
        className={`hidden h-9 w-9 shrink-0 items-center justify-center rounded-full border border-black/15 text-black/40 transition-all duration-300 hover:border-black hover:text-black lg:sticky lg:top-36 lg:flex lg:self-start ${
          masaustuAcik ? "" : "rotate-180"
        }`}
      >
        <ChevronDown className="h-4 w-4 -rotate-90" />
      </button>

      {/* Mobil: açma butonu (tam genişlik, üstte) */}
      <button
        type="button"
        onClick={() => setMobilAcik(true)}
        className="flex w-full items-center justify-center gap-2 rounded-full border border-black/15 px-4 py-2.5 text-[13px] font-bold text-black/70 transition hover:border-black hover:text-black lg:hidden"
      >
        <SlidersHorizontal className="h-3.5 w-3.5" />
        Filtreler {aktifFiltreSayisi > 0 && <span className="rounded-full bg-black px-1.5 text-[10px] text-white">{aktifFiltreSayisi}</span>}
      </button>

      {/* Overlay */}
      <div
        className={`fixed inset-0 z-50 lg:hidden ${mobilAcik ? "" : "pointer-events-none"}`}
        aria-hidden={!mobilAcik}
      >
        <div
          onClick={() => setMobilAcik(false)}
          className={`absolute inset-0 bg-black/40 backdrop-blur-[2px] transition-opacity duration-300 ${mobilAcik ? "opacity-100" : "opacity-0"}`}
        />
        <div
          className={`absolute inset-y-0 left-0 w-72 overflow-y-auto bg-white p-5 shadow-2xl transition-transform duration-300 ease-out [scrollbar-width:thin] ${
            mobilAcik ? "translate-x-0" : "-translate-x-full"
          }`}
        >
          <button
            type="button"
            onClick={() => setMobilAcik(false)}
            aria-label="Kapat"
            className="absolute right-3 top-3 text-black/40 transition hover:text-black"
          >
            <X className="h-5 w-5" />
          </button>
          {icerik}
        </div>
      </div>
    </>
  );
}
