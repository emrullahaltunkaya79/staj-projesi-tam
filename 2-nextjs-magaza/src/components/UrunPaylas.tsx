"use client";

import { useState } from "react";
import { Check, Link2, Share2 } from "lucide-react";

/** Ürün paylaşma satırı: cihaz destekliyorsa yerel paylaşım, yoksa WhatsApp / X / bağlantı kopyala. */
export function UrunPaylas({ urunAd }: { urunAd: string }) {
  const [kopyalandi, setKopyalandi] = useState(false);

  const url = typeof window !== "undefined" ? window.location.href : "";
  const metin = `${urunAd} — HepsiBirarada'da gördüm, göz at:`;

  const paylas = async () => {
    if (typeof navigator !== "undefined" && navigator.share) {
      try {
        await navigator.share({ title: urunAd, text: metin, url });
        return;
      } catch {
        /* kullanıcı vazgeçti — satırı göstermeye devam */
      }
    }
  };

  const kopyala = async () => {
    try {
      await navigator.clipboard.writeText(url);
      setKopyalandi(true);
      setTimeout(() => setKopyalandi(false), 2000);
    } catch {
      /* pano izni yoksa sessiz geç */
    }
  };

  return (
    <div className="flex flex-wrap items-center gap-3 border-t border-black/10 pt-4 text-[12px] font-bold text-black/50">
      <span className="flex items-center gap-1.5 text-black/40">
        <Share2 className="h-3.5 w-3.5" /> Paylaş:
      </span>
      <button type="button" onClick={paylas}
              className="transition hover:text-black">
        WhatsApp / X / …
      </button>
      <a href={`https://wa.me/?text=${encodeURIComponent(`${metin} ${url}`)}`} target="_blank" rel="noopener"
         className="transition hover:text-emerald-600">
        WhatsApp
      </a>
      <button type="button" onClick={kopyala}
              className="flex items-center gap-1 transition hover:text-black">
        {kopyalandi ? <Check className="h-3.5 w-3.5 text-emerald-600" /> : <Link2 className="h-3.5 w-3.5" />}
        {kopyalandi ? "Kopyalandı" : "Bağlantıyı kopyala"}
      </button>
    </div>
  );
}
