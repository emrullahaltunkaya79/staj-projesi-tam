"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Truck } from "lucide-react";

const KARGO_FIRMALARI = ["Yurtiçi Kargo", "Aras Kargo", "MNG Kargo", "PTT Kargo", "Sürat Kargo"];

/** Satıcı: ödenen/hazırlanan siparişi kargola — takip no girilince müşteri link görür ve order.shipped webhook'u ateşlenir. */
export function KargoFormu({ orderNo }: { orderNo: string }) {
  const router = useRouter();
  const [acik, setAcik] = useState(false);
  const [carrier, setCarrier] = useState("");
  const [takip, setTakip] = useState("");
  const [gonderiliyor, setGonderiliyor] = useState(false);
  const [hata, setHata] = useState<string | null>(null);

  const gonder = async (e: React.FormEvent) => {
    e.preventDefault();
    setGonderiliyor(true);
    setHata(null);
    try {
      const yanit = await fetch(`/api/satici/orders/${orderNo}/shipping`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ carrier, tracking_no: takip.trim() }),
      });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.hata ?? "Kargo bilgisi kaydedilemedi.");
      router.refresh();
    } catch (err) {
      setHata(err instanceof Error ? err.message : "Beklenmeyen hata.");
      setGonderiliyor(false);
    }
  };

  if (!acik) {
    return (
      <button type="button" onClick={() => setAcik(true)}
              className="flex items-center gap-1.5 rounded-full border border-slate-300 px-3 py-1.5 text-[11px] font-bold text-slate-600 transition hover:border-slate-900 hover:text-slate-900">
        <Truck className="h-3.5 w-3.5" /> Kargola
      </button>
    );
  }

  return (
    <form onSubmit={gonder} className="flex w-64 flex-col gap-2 text-left">
      <select required value={carrier} onChange={(e) => setCarrier(e.target.value)}
              className="h-8 rounded-lg border border-slate-200 px-2 text-xs focus:border-slate-900 focus:outline-none">
        <option value="">Kargo firması</option>
        {KARGO_FIRMALARI.map((f) => <option key={f} value={f}>{f}</option>)}
      </select>
      <input required value={takip} onChange={(e) => setTakip(e.target.value)} placeholder="Takip numarası"
             className="h-8 rounded-lg border border-slate-200 px-2 text-xs focus:border-slate-900 focus:outline-none" />
      {hata && <p className="text-[11px] font-semibold text-red-600">{hata}</p>}
      <div className="flex gap-1.5">
        <button type="submit" disabled={gonderiliyor}
                className="h-8 flex-1 rounded-lg bg-slate-900 text-[11px] font-bold text-white transition hover:bg-slate-700 disabled:opacity-50">
          {gonderiliyor ? "…" : "Kargola"}
        </button>
        <button type="button" onClick={() => setAcik(false)}
                className="h-8 rounded-lg border border-slate-200 px-2 text-[11px] font-bold text-slate-400 transition hover:text-slate-900">
          ✕
        </button>
      </div>
    </form>
  );
}
