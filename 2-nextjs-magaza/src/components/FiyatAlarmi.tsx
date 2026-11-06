"use client";

import { useState } from "react";
import { TrendingDown, Check } from "lucide-react";
import { auth } from "./AuthProvider";

/** "Fiyat düşünce haber ver" — mevcut KDV dahil fiyat takip fiyatı olarak kaydedilir. */
export function FiyatAlarmi({ urunId }: { urunId: number }) {
  const { kullanici } = auth();
  const [acik, setAcik] = useState(false);
  const [eposta, setEposta] = useState("");
  const [durum, setDurum] = useState<"hazir" | "gonderiliyor" | "tamam" | "hata">("hazir");
  const [mesaj, setMesaj] = useState("");

  const kur = async (adres: string) => {
    setDurum("gonderiliyor");
    try {
      const yanit = await fetch("/api/fiyat-alarmi", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email: adres, product_id: urunId }),
      });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.hata ?? "Alarm kurulamadı.");
      setMesaj(sonuc.message);
      setDurum("tamam");
    } catch (err) {
      setMesaj(err instanceof Error ? err.message : "Alarm kurulamadı.");
      setDurum("hata");
    }
  };

  if (durum === "tamam") {
    return (
      <p className="mt-4 flex items-center gap-2 text-[12px] font-semibold text-emerald-700">
        <Check className="h-4 w-4" /> {mesaj}
      </p>
    );
  }

  if (!acik) {
    return (
      <button
        type="button"
        onClick={() => (kullanici ? kur(kullanici.email) : setAcik(true))}
        disabled={durum === "gonderiliyor"}
        className="mt-4 flex items-center gap-1.5 text-[12px] font-bold text-black/50 underline-offset-4 transition hover:text-black hover:underline disabled:opacity-50"
      >
        <TrendingDown className="h-4 w-4" /> {durum === "gonderiliyor" ? "Kuruluyor…" : "Fiyat düşünce haber ver"}
      </button>
    );
  }

  return (
    <form onSubmit={(e) => { e.preventDefault(); kur(eposta); }} className="mt-4 max-w-sm">
      <p className="flex items-center gap-1.5 text-[12px] font-bold text-black">
        <TrendingDown className="h-4 w-4" /> Fiyat düşünce e-posta ile haber verelim
      </p>
      <div className="mt-2 flex gap-2">
        <input type="email" required value={eposta} onChange={(e) => setEposta(e.target.value)} placeholder="e-posta adresiniz"
               aria-label="Fiyat alarmı e-postası"
               className="h-10 flex-1 border border-black/15 bg-white px-3 text-[13px] focus:border-black focus:outline-none" />
        <button type="submit" disabled={durum === "gonderiliyor"}
                className="h-10 shrink-0 rounded-full bg-black px-4 text-[12px] font-bold text-white transition hover:bg-black/80 disabled:opacity-50">
          {durum === "gonderiliyor" ? "…" : "Alarm kur"}
        </button>
      </div>
      {durum === "hata" && <p className="mt-1.5 text-[12px] font-medium text-red-600">{mesaj}</p>}
    </form>
  );
}
