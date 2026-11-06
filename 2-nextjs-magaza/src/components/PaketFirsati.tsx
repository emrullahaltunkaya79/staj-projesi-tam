"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { Package, Check, Truck } from "lucide-react";
import { sepet } from "./CartProvider";
import { UCRETSIZ_KARGO_LIMITI } from "@/lib/kargo";
import { kdvDahil, tl } from "@/lib/fiyat";

type Oneri = {
  id: number;
  ad: string;
  marka: string | null;
  fiyat: number; // KDV hariç
  gorsel: string | null;
  birim: string;
  musait: number;
  kargoyuKapatir: boolean;
};

/**
 * Ödeme sayfası son teklif: sepettekilerle birlikte sıkça alınan ürünlerden paket.
 * Tek tıkla sepete eklenir; ücretsiz kargo eşiğini kapatan ürün rozetle işaretlidir.
 */
export function PaketFirsati() {
  const { ogeler, genelToplam, ekle } = sepet();
  const [oneriler, setOneriler] = useState<Oneri[]>([]);
  const [secili, setSecili] = useState<Set<number>>(new Set());
  const [eklendi, setEklendi] = useState(false);

  const idler = [...new Set(ogeler.map((o) => o.id))].sort((a, b) => a - b).join(",");
  const kdvCarpani = genelToplam > 0 ? genelToplam / (genelToplam / 1.2) : 1;
  const acik = Math.max(0, Math.ceil((UCRETSIZ_KARGO_LIMITI - genelToplam) / kdvCarpani));

  useEffect(() => {
    if (!idler) return;
    const kontrol = new AbortController();
    fetch(`/api/sepet-onerileri?ids=${idler}&acik=${acik}`, { signal: kontrol.signal })
      .then((r) => r.json())
      .then((d) => {
        const liste: Oneri[] = (d.data ?? []).filter((o: Oneri) => !ogeler.some((s) => s.id === o.id));
        setOneriler(liste.slice(0, 3));
        setSecili(new Set(liste.slice(0, 3).map((o) => o.id)));
      })
      .catch(() => {});
    return () => kontrol.abort();
  }, [idler]); // eslint-disable-line react-hooks/exhaustive-deps

  const paket = useMemo(() => oneriler.filter((o) => secili.has(o.id)), [oneriler, secili]);
  const paketToplami = paket.reduce((t, o) => t + kdvDahil(o.fiyat), 0);
  const kargoKapaniyor = paket.some((o) => o.kargoyuKapatir) && genelToplam + paketToplami >= UCRETSIZ_KARGO_LIMITI;

  if (paket.length === 0) return null;

  const sepeteEkle = () => {
    for (const o of paket) {
      ekle({ id: o.id, ad: o.ad, fiyat: o.fiyat, gorsel: o.gorsel, birim: o.birim }, 1, o.musait);
    }
    setEklendi(true);
  };

  return (
    <section className="rounded-xl border-2 border-dashed border-[var(--marka)]/40 bg-[var(--marka)]/[.03] p-4">
      <p className="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-[var(--marka)]">
        <Package className="h-4 w-4" /> Paket fırsatı — siparişinden önce son bakış
      </p>
      <p className="mt-1 text-[12px] text-black/50">Sepetindeki ürünlerle birlikte sıkça alınıyor:</p>

      <ul className="mt-3 space-y-2">
        {oneriler.map((o) => {
          const isaretli = secili.has(o.id);
          return (
            <li key={o.id}>
              <label className="flex cursor-pointer items-center gap-2.5 text-[13px]">
                <input type="checkbox" checked={isaretli} onChange={() => {
                  setEklendi(false);
                  setSecili((s) => {
                    const yeni = new Set(s);
                    if (yeni.has(o.id)) yeni.delete(o.id);
                    else yeni.add(o.id);
                    return yeni;
                  });
                }} className="h-4 w-4 shrink-0 accent-black" />
                {o.kargoyuKapatir && <Truck className="h-3.5 w-3.5 shrink-0 text-emerald-600" />}
                <Link href={`/urun/${o.id}`} className="min-w-0 flex-1 truncate text-black/75 hover:text-black hover:underline">
                  {o.ad}
                </Link>
                <span className="shrink-0 font-bold tabular-nums text-black">{tl(kdvDahil(o.fiyat))} ₺</span>
              </label>
            </li>
          );
        })}
      </ul>

      <div className="mt-3.5 flex flex-wrap items-center justify-between gap-3 border-t border-black/10 pt-3.5">
        <div className="text-[12px] text-black/55">
          <span className="font-bold tabular-nums text-black">{tl(paketToplami)} ₺</span>
          {" "}· {kargoKapaniyor ? <b className="text-emerald-700">bu paketle kargonuz ücretsiz olur</b> : "fiyatlar KDV dahil"}
        </div>
        {eklendi ? (
          <span className="flex items-center gap-1.5 rounded-full bg-emerald-600 px-4 py-2 text-[12px] font-bold text-white">
            <Check className="h-4 w-4" /> Paket sepete eklendi
          </span>
        ) : (
          <button type="button" onClick={sepeteEkle}
                  className="rounded-full bg-[var(--marka)] px-5 py-2 text-[12px] font-bold text-white transition hover:opacity-90 active:scale-95">
            {paket.length === 1 ? "Ürünü siparişe ekle" : `${paket.length} ürünü paket olarak ekle`}
          </button>
        )}
      </div>
    </section>
  );
}
