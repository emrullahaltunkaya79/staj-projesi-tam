"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Lock } from "lucide-react";

/** Kart (3D Secure) siparişinde: PSP ödeme formunu başlatır ve müşteriyi yönlendirir. */
export function KartOdemeButonu({ orderNo, erisim }: { orderNo: string; erisim: string | null }) {
  const router = useRouter();
  const [calisiyor, setCalisiyor] = useState(false);
  const [hata, setHata] = useState<string | null>(null);
  const sorgu = erisim ? `?k=${encodeURIComponent(erisim)}` : "";

  const baslat = async () => {
    setCalisiyor(true);
    setHata(null);
    try {
      const yanit = await fetch(`/api/orders/${orderNo}/odeme-baslat`, {
        method: "POST",
        headers: erisim ? { "x-siparis-anahtari": erisim } : {},
      });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.hata ?? "3D Secure ödemesi başlatılamadı.");
      if (!sonuc.data?.odeme_url) throw new Error("Ödeme formu adresi alınamadı.");

      // PSP (iyzico) ya da sandbox 3DS formuna yönlendir — dönüşte bu sayfaya gelinir
      window.location.href = sonuc.data.odeme_url;
    } catch (e) {
      setHata(e instanceof Error ? e.message : "Beklenmeyen hata.");
      setCalisiyor(false);
    }
  };

  return (
    <div>
      <button
        type="button"
        onClick={baslat}
        disabled={calisiyor}
        className="flex h-12 w-full items-center justify-center gap-2 rounded-full bg-black text-sm font-bold text-white transition hover:bg-black/80 active:scale-95 disabled:opacity-50"
      >
        <Lock className="h-4 w-4" />
        {calisiyor ? "3D Secure formuna yönlendiriliyorsun…" : `💳 3D Secure ile Öde — ${orderNo}`}
      </button>
      <p className="mt-2 text-[11px] leading-relaxed text-black/40">
        Bankanın 3D Secure ekranında kart bilgilerini ve SMS onay kodunu girdikten sonra ödeme tamamlanır,
        faturan otomatik kesilir. Kart bilgilerin mağazamızda saklanmaz.
      </p>
      {hata && <p className="mt-2 text-xs font-semibold text-red-600">{hata}</p>}
      <a href={`/siparis/${orderNo}${sorgu}`} className="mt-1 inline-block text-[11px] font-bold text-black/30 underline underline-offset-2 hover:text-black">
        Ödemeyi sonra tamamla
      </a>
    </div>
  );
}
