"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { ArrowUp, ShoppingCart } from "lucide-react";
import { sepet } from "./CartProvider";

/** Scroll-to-top butonu + mobilde alt kısımda sabit "Sepete Git" çubuğu. */
export function KaydirmaYardimcisi() {
  const { toplamAdet, genelToplam } = sepet();
  const [goster, setGoster] = useState(false);

  useEffect(() => {
    const dinle = () => setGoster(window.scrollY > 600);
    dinle();
    window.addEventListener("scroll", dinle, { passive: true });
    return () => window.removeEventListener("scroll", dinle);
  }, []);

  return (
    <>
      {goster && (
        <button
          type="button"
          onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
          aria-label="Yukarı çık"
          className="fixed bottom-20 right-4 z-40 flex h-11 w-11 items-center justify-center rounded-full border border-black/10 bg-white text-black shadow-xl transition hover:bg-black hover:text-white active:scale-95 sm:bottom-6"
        >
          <ArrowUp className="h-5 w-5" />
        </button>
      )}

      {toplamAdet > 0 && (
        <Link
          href="/sepet"
          className="fixed inset-x-3 bottom-3 z-40 flex items-center justify-between rounded-2xl bg-[var(--marka)] px-5 py-3.5 text-white shadow-2xl transition active:scale-[.98] sm:hidden"
        >
          <span className="flex items-center gap-2 text-sm font-bold">
            <ShoppingCart className="h-4.5 w-4.5" />
            {toplamAdet} ürün
          </span>
          <span className="text-sm font-black tabular-nums">
            {new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(genelToplam)} ₺ · Sepete Git →
          </span>
        </Link>
      )}
    </>
  );
}
