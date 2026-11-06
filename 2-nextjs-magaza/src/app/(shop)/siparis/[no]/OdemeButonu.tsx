"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

export function OdemeButonu({ orderNo, erisim }: { orderNo: string; erisim: string | null }) {
  const router = useRouter();
  const [calisiyor, setCalisiyor] = useState(false);
  const [hata, setHata] = useState<string | null>(null);
  const sorgu = erisim ? `?k=${encodeURIComponent(erisim)}` : "";

  const ode = async () => {
    setCalisiyor(true);
    setHata(null);

    try {
      const yanit = await fetch(`/api/orders/${orderNo}/pay`, {
        method: "POST",
        headers: erisim ? { "x-siparis-anahtari": erisim } : {},
      });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.hata ?? "Ödeme işlenemedi.");
      router.refresh();
    } catch (e) {
      setHata(e instanceof Error ? e.message : "Beklenmeyen hata.");
    } finally {
      setCalisiyor(false);
    }
  };

  return (
    <div>
      <a href={`/siparis/${orderNo}/cspr${sorgu}`}
         className="flex h-12 w-full items-center justify-center rounded-full bg-black text-sm font-bold text-white transition hover:bg-black/80 active:scale-95">
        ⛓️ CSPR ile Öde — QR
      </a>
      <button type="button" onClick={ode} disabled={calisiyor}
              className="mt-2 h-9 w-full text-[11px] font-bold text-black/30 transition hover:text-black disabled:opacity-50">
        {calisiyor ? "…" : "veya manuel onayla (demo)"}
      </button>
      <p className="mt-2 text-[11px] text-black/40">Ödeme onayında faturanız otomatik kesilir.</p>
      {hata && <p className="mt-2 text-xs font-semibold text-red-600">{hata}</p>}
    </div>
  );
}
