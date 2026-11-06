"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { Plus, Check, Truck } from "lucide-react";
import { sepet } from "./CartProvider";
import { UCRETSIZ_KARGO_LIMITI } from "@/lib/kargo";
import { kdvDahil } from "@/lib/fiyat";

type Oneri = {
  id: number;
  ad: string;
  marka: string | null;
  fiyat: number;
  gorsel: string | null;
  birim: string;
  musait: number;
  kargoyuKapatir: boolean;
};

const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

/** Sepet sayfası: sepettekilerle birlikte alınanlar; ücretsiz kargoyu açan ürün öne çıkar. */
export function SepetOnerileri() {
  const { ogeler, araToplam, genelToplam, ekle } = sepet();
  const [oneriler, setOneriler] = useState<Oneri[]>([]);
  const [eklenenler, setEklenenler] = useState<Set<number>>(new Set());

  const idler = [...new Set(ogeler.map((o) => o.id))].sort((a, b) => a - b).join(",");
  // Eşik KDV dahil toplama uygulanır; önerilerin fiyatı KDV hariç olduğu için açık aynı oranla çevrilir
  const kdvCarpani = araToplam > 0 ? genelToplam / araToplam : 1;
  const acik = Math.max(0, Math.ceil((UCRETSIZ_KARGO_LIMITI - genelToplam) / kdvCarpani));

  useEffect(() => {
    if (!idler) return;
    const kontrol = new AbortController();
    fetch(`/api/sepet-onerileri?ids=${idler}&acik=${acik}`, { signal: kontrol.signal })
      .then((r) => r.json())
      .then((d) => setOneriler(d.data ?? []))
      .catch(() => {});
    return () => kontrol.abort();
  }, [idler, acik]);

  const gorunen = oneriler.filter((o) => !ogeler.some((s) => s.id === o.id) || eklenenler.has(o.id));
  if (gorunen.length === 0) return null;

  return (
    <section className="border-t border-black/15 pt-6">
      <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
        <h2 className="text-[14px] font-black uppercase tracking-wide text-black">Sepetini Tamamla</h2>
        <span className="text-[12px] font-medium text-black/35">Sepetindekilerle birlikte sıkça alınanlar</span>
      </div>
      <div className="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
        {gorunen.map((o) => {
          const eklendi = eklenenler.has(o.id);
          return (
            <div key={o.id} className="flex flex-col rounded-xl border border-black/10 p-3">
              <Link href={`/urun/${o.id}`} className="flex aspect-square items-center justify-center overflow-hidden bg-[#f4f4f4]">
                {o.gorsel ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={o.gorsel} alt={o.ad} loading="lazy" className="h-full w-full object-contain p-2" />
                ) : (
                  <span className="text-2xl font-black text-black/10">{o.ad.charAt(0)}</span>
                )}
              </Link>
              {o.kargoyuKapatir && (
                <span className="mt-2 flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700">
                  <Truck className="h-3 w-3" /> Kargoyu ücretsiz yapar
                </span>
              )}
              {o.marka && <span className="mt-2 text-[10px] font-black uppercase tracking-widest text-black/40">{o.marka}</span>}
              <Link href={`/urun/${o.id}`} className="mt-0.5 line-clamp-2 text-[12.5px] font-semibold leading-snug text-black hover:underline">
                {o.ad}
              </Link>
              <div className="mt-auto flex items-center justify-between gap-2 pt-2">
                <span className="text-[13px] font-bold tabular-nums text-black">{fmt(kdvDahil(o.fiyat))} ₺</span>
                <button
                  type="button"
                  disabled={eklendi}
                  onClick={() => {
                    ekle({ id: o.id, ad: o.ad, fiyat: o.fiyat, gorsel: o.gorsel, birim: o.birim }, 1, o.musait);
                    setEklenenler((s) => new Set(s).add(o.id));
                  }}
                  aria-label={`${o.ad} sepete ekle`}
                  className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition ${
                    eklendi ? "bg-emerald-600 text-white" : "bg-black text-white hover:bg-black/80"
                  }`}
                >
                  {eklendi ? <Check className="h-4 w-4" /> : <Plus className="h-4 w-4" />}
                </button>
              </div>
            </div>
          );
        })}
      </div>
    </section>
  );
}

/** Sepet özetinde ücretsiz kargoya kalan tutar çubuğu. */
export function UcretsizKargoCubugu() {
  const { genelToplam } = sepet();
  const kalan = Math.max(0, UCRETSIZ_KARGO_LIMITI - genelToplam);
  const oran = Math.min(100, (genelToplam / UCRETSIZ_KARGO_LIMITI) * 100);

  return (
    <div className="mb-6 rounded-xl border border-black/10 p-4">
      <p className="flex items-center gap-2 text-[12.5px] font-bold text-black">
        <Truck className="h-4 w-4 text-black/50" />
        {kalan > 0 ? (
          <span>Ücretsiz kargoya <span className="tabular-nums">{fmt(kalan)} ₺</span> kaldı</span>
        ) : (
          <span className="text-emerald-700">Kargonuz ücretsiz</span>
        )}
      </p>
      <div className="mt-2.5 h-1.5 overflow-hidden rounded-full bg-black/10">
        <div
          className={`h-full rounded-full transition-[width] duration-500 ${kalan > 0 ? "bg-[var(--marka)]" : "bg-emerald-500"}`}
          style={{ width: `${oran}%` }}
        />
      </div>
    </div>
  );
}
