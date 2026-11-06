"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

const NEDENLER: [string, string][] = [
  ["fikir_degisti", "Fikrimi değiştirdim"],
  ["hasarli", "Ürün hasarlı veya kusurlu geldi"],
  ["yanlis_urun", "Yanlış ürün gönderildi"],
  ["aciklamaya_uymuyor", "Ürün açıklamaya uymuyor"],
  ["diger", "Diğer"],
];

type Kalem = { product_name: string; quantity: number; unit_price: number };

/** Teslim edilmiş siparişte iade talebi: neden + ürün bazlı (kısmi) seçim. */
export function IadeFormu({ orderNo, erisim, kalemler }: { orderNo: string; erisim: string | null; kalemler: Kalem[] }) {
  const router = useRouter();
  const [acik, setAcik] = useState(false);
  const [neden, setNeden] = useState("");
  const [aciklama, setAciklama] = useState("");
  const [adetler, setAdetler] = useState<number[]>(() => kalemler.map((k) => Number(k.quantity)));
  const [calisiyor, setCalisiyor] = useState(false);
  const [hata, setHata] = useState<string | null>(null);

  const secilenler = adetler
    .map((adet, sira) => ({ sira, adet }))
    .filter((k) => k.adet > 0);

  const gonder = async (e: React.FormEvent) => {
    e.preventDefault();
    if (secilenler.length === 0) {
      setHata("İade etmek istediğiniz en az bir ürün seçin.");
      return;
    }

    setCalisiyor(true);
    setHata(null);
    try {
      const yanit = await fetch(`/api/orders/${orderNo}/return-request`, {
        method: "POST",
        headers: { "Content-Type": "application/json", ...(erisim ? { "x-siparis-anahtari": erisim } : {}) },
        body: JSON.stringify({ neden, aciklama, kalemler: secilenler }),
      });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.hata ?? "İade talebi oluşturulamadı.");
      router.refresh();
    } catch (err) {
      setHata(err instanceof Error ? err.message : "Beklenmeyen hata.");
    } finally {
      setCalisiyor(false);
    }
  };

  if (!acik) {
    return (
      <button type="button" onClick={() => setAcik(true)}
              className="h-10 w-full border border-black/15 text-xs font-bold text-black/50 transition hover:border-black hover:text-black">
        ↩️ İade Talebi Oluştur
      </button>
    );
  }

  return (
    <form onSubmit={gonder} className="space-y-4 rounded-xl border border-black/15 p-4">
      <p className="text-sm font-black text-black">İade talebi</p>

      <div>
        <p className="mb-1.5 text-[10px] font-bold uppercase tracking-widest text-black/40">İade edilecek ürünler</p>
        <ul className="space-y-2">
          {kalemler.map((k, sira) => (
            <li key={sira} className="flex items-center justify-between gap-3 text-[13px]">
              <label className="flex min-w-0 items-center gap-2.5">
                <input
                  type="checkbox"
                  checked={adetler[sira] > 0}
                  onChange={(e) => setAdetler((a) => a.map((v, i) => (i === sira ? (e.target.checked ? Number(k.quantity) : 0) : v)))}
                  className="h-4 w-4 shrink-0 accent-black"
                />
                <span className="truncate text-black/80">{k.product_name}</span>
              </label>
              {Number(k.quantity) > 1 && (
                <select
                  value={adetler[sira]}
                  onChange={(e) => setAdetler((a) => a.map((v, i) => (i === sira ? Number(e.target.value) : v)))}
                  aria-label={`${k.product_name} iade adedi`}
                  className="h-8 shrink-0 border border-black/15 bg-white px-2 text-xs"
                >
                  {Array.from({ length: Number(k.quantity) + 1 }, (_, n) => (
                    <option key={n} value={n}>{n} / {k.quantity}</option>
                  ))}
                </select>
              )}
            </li>
          ))}
        </ul>
      </div>

      <div>
        <label className="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-black/40">İade nedeni *</label>
        <select required value={neden} onChange={(e) => setNeden(e.target.value)}
                className="h-10 w-full border border-black/15 bg-white px-3 text-sm focus:border-black focus:outline-none">
          <option value="">Seçiniz</option>
          {NEDENLER.map(([deger, etiket]) => <option key={deger} value={deger}>{etiket}</option>)}
        </select>
      </div>

      <div>
        <label className="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-black/40">Açıklama (isteğe bağlı)</label>
        <textarea rows={2} maxLength={500} value={aciklama} onChange={(e) => setAciklama(e.target.value)}
                  placeholder="Örn. kutu ezik geldi, ekranda çizik var"
                  className="w-full border border-black/15 bg-white px-3 py-2 text-sm focus:border-black focus:outline-none" />
      </div>

      {hata && <p className="text-xs font-semibold text-red-600">{hata}</p>}

      <div className="flex gap-2">
        <button type="submit" disabled={calisiyor}
                className="h-10 flex-1 rounded-full bg-black text-xs font-bold text-white transition hover:bg-black/80 disabled:opacity-50">
          {calisiyor ? "Gönderiliyor…" : `İade talebini gönder (${secilenler.length} ürün)`}
        </button>
        <button type="button" onClick={() => setAcik(false)}
                className="h-10 shrink-0 px-4 text-xs font-bold text-black/40 transition hover:text-black">
          Vazgeç
        </button>
      </div>
    </form>
  );
}
