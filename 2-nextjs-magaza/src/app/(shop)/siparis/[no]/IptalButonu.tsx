"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

export function IptalButonu({ orderNo }: { orderNo: string }) {
  const router = useRouter();
  const [calisiyor, setCalisiyor] = useState(false);
  const [hata, setHata] = useState<string | null>(null);

  const iptal = async () => {
    if (!confirm("Siparişi iptal etmek istediğinize emin misiniz? Rezerve stok serbest bırakılır.")) return;

    setCalisiyor(true);
    setHata(null);

    try {
      const yanit = await fetch(`/api/orders/${orderNo}/cancel`, { method: "POST" });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.hata ?? "İptal edilemedi.");
      router.refresh();
    } catch (e) {
      setHata(e instanceof Error ? e.message : "Beklenmeyen hata.");
    } finally {
      setCalisiyor(false);
    }
  };

  return (
    <div>
      <button type="button" onClick={iptal} disabled={calisiyor}
              className="h-10 w-full border border-black/15 text-xs font-bold text-black/50 transition hover:border-red-600 hover:text-red-600 disabled:opacity-50">
        {calisiyor ? "…" : "Siparişi İptal Et"}
      </button>
      {hata && <p className="mt-2 text-xs font-semibold text-red-600">{hata}</p>}
    </div>
  );
}
