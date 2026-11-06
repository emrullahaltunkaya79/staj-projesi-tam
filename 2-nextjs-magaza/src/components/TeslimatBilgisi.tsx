"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { Truck, RotateCcw } from "lucide-react";
import { tahminiTeslimat, IADE_SURESI_GUN, KARGO_KESIM_SAATI, UCRETSIZ_KARGO_LIMITI } from "@/lib/kargo";

const gunAy = new Intl.DateTimeFormat("tr-TR", { day: "numeric", month: "long" });
const gunAdi = new Intl.DateTimeFormat("tr-TR", { weekday: "long" });

/** Ürün sayfası: tahmini teslimat ve iade özeti. Saat istemcide hesaplanır (SSR ile saat farkı oluşmasın). */
export function TeslimatBilgisi({ fiyat, stokta }: { fiyat: number; stokta: boolean }) {
  const [tahmin, setTahmin] = useState<ReturnType<typeof tahminiTeslimat> | null>(null);

  useEffect(() => {
    setTahmin(tahminiTeslimat(new Date()));
  }, []);

  return (
    <div className="mt-5 max-w-md divide-y divide-black/10 rounded-xl border border-black/10 text-[13px]">
      {stokta && (
        <div className="flex gap-3 p-3.5">
          <Truck className="mt-0.5 h-4 w-4 shrink-0 text-black/45" />
          <div>
            <p className="font-bold text-black">
              {tahmin
                ? `Tahmini teslimat: ${gunAy.format(tahmin.enErken)} – ${gunAy.format(tahmin.enGec)}`
                : "Tahmini teslimat hesaplanıyor…"}
            </p>
            <p className="mt-0.5 text-[12px] text-black/50">
              {tahmin?.bugunKargoda
                ? `Saat ${KARGO_KESIM_SAATI}:00'a kadar sipariş verirseniz bugün kargoda.`
                : tahmin
                  ? `${gunAdi.format(tahmin.kargoGunu)} kargoya verilir.`
                  : ""}{" "}
              {fiyat >= UCRETSIZ_KARGO_LIMITI
                ? "Kargo ücretsiz."
                : `${UCRETSIZ_KARGO_LIMITI.toLocaleString("tr-TR")} ₺ üzeri kargo ücretsiz.`}
            </p>
          </div>
        </div>
      )}
      <div className="flex gap-3 p-3.5">
        <RotateCcw className="mt-0.5 h-4 w-4 shrink-0 text-black/45" />
        <div>
          <p className="font-bold text-black">{IADE_SURESI_GUN} gün içinde ücretsiz iade</p>
          <p className="mt-0.5 text-[12px] text-black/50">
            İade kargosu bizden, değişim kolay ·{" "}
            <Link href="/iade-degisim" className="font-bold text-black underline underline-offset-2">
              İade ve kargo koşulları
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
}
