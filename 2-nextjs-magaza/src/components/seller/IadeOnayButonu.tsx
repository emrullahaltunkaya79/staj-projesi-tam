"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

/** Satıcı: iade talebini onaylar — stok geri alınır, para iadesi kaydedilir. */
export function IadeOnayButonu({ orderNo }: { orderNo: string }) {
  const router = useRouter();
  const [calisiyor, setCalisiyor] = useState(false);
  const [hata, setHata] = useState<string | null>(null);

  const onayla = async () => {
    if (!confirm("İadeyi onaylıyor musunuz? Stok geri alınır ve para iadesi kaydedilir.")) return;

    setCalisiyor(true);
    setHata(null);

    try {
      const yanit = await fetch(`/api/satici/orders/${orderNo}/return-approve`, { method: "POST" });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.hata ?? sonuc.message ?? "Onaylanamadı.");
      router.refresh();
    } catch (e) {
      setHata(e instanceof Error ? e.message : "Beklenmeyen hata.");
    } finally {
      setCalisiyor(false);
    }
  };

  return (
    <div className="flex flex-col items-end gap-1">
      <button type="button" onClick={onayla} disabled={calisiyor}
              className="h-8 rounded-full bg-amber-500 px-4 text-xs font-bold text-white transition hover:bg-amber-600 active:scale-95 disabled:opacity-50">
        {calisiyor ? "…" : "İadeyi Onayla"}
      </button>
      {hata && <p className="text-[10px] font-bold text-red-500">{hata}</p>}
    </div>
  );
}
