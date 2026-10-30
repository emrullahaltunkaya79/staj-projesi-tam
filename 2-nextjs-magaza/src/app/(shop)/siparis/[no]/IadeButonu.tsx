"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

/** Teslim edilmiş siparişte müşteri iade talebi oluşturur (14 gün cayma hakkı). */
export function IadeButonu({ orderNo }: { orderNo: string }) {
  const router = useRouter();
  const [calisiyor, setCalisiyor] = useState(false);
  const [hata, setHata] = useState<string | null>(null);

  const iadeTalebi = async () => {
    if (!confirm("İade talebi oluşturmak istediğinize emin misiniz? Talebiniz satıcı onayından sonra para iadesine dönüşür.")) return;

    setCalisiyor(true);
    setHata(null);

    try {
      const yanit = await fetch(`/api/orders/${orderNo}/return-request`, { method: "POST" });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.hata ?? "İade talebi oluşturulamadı.");
      router.refresh();
    } catch (e) {
      setHata(e instanceof Error ? e.message : "Beklenmeyen hata.");
    } finally {
      setCalisiyor(false);
    }
  };

  return (
    <div>
      <button type="button" onClick={iadeTalebi} disabled={calisiyor}
              className="h-10 w-full border border-black/15 text-xs font-bold text-black/50 transition hover:border-black hover:text-black disabled:opacity-50">
        {calisiyor ? "…" : "↩️ İade Talebi Oluştur (14 Gün)"}
      </button>
      {hata && <p className="mt-2 text-xs font-semibold text-red-600">{hata}</p>}
    </div>
  );
}
