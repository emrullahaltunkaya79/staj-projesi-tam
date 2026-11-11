"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { BadgeCheck } from "lucide-react";

/** Satıcı: havale/kapıda ödemesini elden aldığında siparişi ödenmiş işaretler — fatura kesilir. */
export function OdemeOnayButonu({ orderNo, yontem }: { orderNo: string; yontem?: string | null }) {
  const router = useRouter();
  const [calisiyor, setCalisiyor] = useState(false);
  const [hata, setHata] = useState<string | null>(null);

  const onayla = async () => {
    const etiket = yontem === "kapida" ? "kapıda ödemesini aldığınızı" : "havaleden ödemeyi aldığınızı";
    if (!confirm(`${orderNo} siparişi için ${etiket} onaylıyor musunuz? Sipariş faturalanır ve hazırlanabilir.`)) return;

    setCalisiyor(true);
    setHata(null);
    try {
      const yanit = await fetch(`/api/orders/${orderNo}/pay`, { method: "POST" });
      const sonuc = await yanit.json().catch(() => ({}));
      if (!yanit.ok) throw new Error(sonuc.hata ?? "Ödeme onaylanamadı.");
      router.refresh();
    } catch (e) {
      setHata(e instanceof Error ? e.message : "Beklenmeyen hata.");
      setCalisiyor(false);
    }
  };

  return (
    <span className="inline-flex flex-col items-end gap-1">
      <button type="button" onClick={onayla} disabled={calisiyor}
              className="flex items-center gap-1.5 rounded-full border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-[11px] font-bold text-emerald-700 transition hover:border-emerald-600 disabled:opacity-50">
        <BadgeCheck className="h-3.5 w-3.5" />
        {calisiyor ? "Onaylanıyor…" : "Ödemeyi Onayla"}
      </button>
      {hata && <span className="text-[10px] font-semibold text-red-500">{hata}</span>}
    </span>
  );
}
