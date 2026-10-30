"use client";

import { useEffect, useState } from "react";

/** Gece yarısına kalan süreyi canlı sayar — kampanya şeridi ve ürün sayaçları için. */
export function GeriSayim({ kompakt = false }: { kompakt?: boolean }) {
  const [kalan, setKalan] = useState<string>("--:--:--");

  useEffect(() => {
    const hesapla = () => {
      const simdi = new Date();
      const gece = new Date(simdi);
      gece.setHours(24, 0, 0, 0);
      const fark = gece.getTime() - simdi.getTime();
      const sa = Math.floor(fark / 3_600_000);
      const dk = Math.floor((fark % 3_600_000) / 60_000);
      const sn = Math.floor((fark % 60_000) / 1000);
      setKalan(`${String(sa).padStart(2, "0")}:${String(dk).padStart(2, "0")}:${String(sn).padStart(2, "0")}`);
    };
    hesapla();
    const z = setInterval(hesapla, 1000);
    return () => clearInterval(z);
  }, []);

  return (
    <span className={`font-mono font-bold tabular-nums ${kompakt ? "text-[11px]" : ""}`}>{kalan}</span>
  );
}
