"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { Cookie } from "lucide-react";

const ANAHTAR = "hba-cerez-onayi";
const GA_ID = process.env.NEXT_PUBLIC_GA_ID;

declare global {
  interface Window {
    dataLayer?: unknown[];
    gtag?: (...args: unknown[]) => void;
  }
}

/** Onay verilmişse GA4 betiğini yükler (env'de ID yoksa hiçbir şey yapmaz). */
function analitikYukle() {
  if (!GA_ID || document.getElementById("ga-script")) return;
  const s = document.createElement("script");
  s.id = "ga-script";
  s.async = true;
  s.src = `https://www.googletagmanager.com/gtag/js?id=${GA_ID}`;
  document.head.appendChild(s);
  window.dataLayer = window.dataLayer ?? [];
  window.gtag = function gtag(...args: unknown[]) {
    window.dataLayer?.push(args);
  };
  window.gtag("js", new Date());
  window.gtag("config", GA_ID, { anonymize_ip: true });
}

/**
 * KVKK çerez onay banner'ı — ilk ziyarette bir kez gösterilir.
 * "Kabul" → analitik dahil; "Yalnızca Zorunlu" → analitik yüklenmez.
 */
export function CerezOnayi() {
  const [acik, setAcik] = useState(false);

  useEffect(() => {
    try {
      const kayitli = localStorage.getItem(ANAHTAR);
      if (!kayitli) {
        // Sayfa içeriği görünsün, banner kısa gecikmeyle gelsin
        const z = setTimeout(() => setAcik(true), 800);
        return () => clearTimeout(z);
      }
      if (kayitli === "tam") analitikYukle();
    } catch {}
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const yanitla = (tam: boolean) => {
    try {
      localStorage.setItem(ANAHTAR, tam ? "tam" : "zorunlu");
    } catch {}
    if (tam) analitikYukle();
    setAcik(false);
  };

  if (!acik) return null;

  return (
    <div className="fixed bottom-4 left-4 right-4 z-[70] sm:left-auto sm:max-w-md" role="dialog" aria-label="Çerez onayı">
      <div className="rounded-2xl border border-black/10 bg-white p-5 shadow-2xl">
        <div className="flex items-start gap-3">
          <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-black text-white">
            <Cookie className="h-4.5 w-4.5" />
          </span>
          <div>
            <p className="text-sm font-black text-black">Çerez Tercihiniz</p>
            <p className="mt-1.5 text-xs leading-relaxed text-black/60">
              Sepet ve giriş için zorunlu çerezlerin yanı sıra anonim ziyaret istatistikleri için analitik
              çerez kullanmak istiyoruz. Detaylar:{" "}
              <Link href="/cerez-politikasi" className="font-bold text-black underline underline-offset-2">
                Çerez Politikası
              </Link>{" "}
              ·{" "}
              <Link href="/gizlilik" className="font-bold text-black underline underline-offset-2">
                KVKK
              </Link>
            </p>
          </div>
        </div>
        <div className="mt-4 flex gap-2">
          <button
            type="button"
            onClick={() => yanitla(true)}
            className="h-9 flex-1 rounded-full bg-black text-xs font-bold text-white transition hover:bg-black/80 active:scale-95"
          >
            Tümünü Kabul Et
          </button>
          <button
            type="button"
            onClick={() => yanitla(false)}
            className="h-9 flex-1 rounded-full border border-black/15 text-xs font-bold text-black/60 transition hover:border-black hover:text-black active:scale-95"
          >
            Yalnızca Zorunlu
          </button>
        </div>
      </div>
    </div>
  );
}
