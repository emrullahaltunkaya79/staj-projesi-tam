"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Truck } from "lucide-react";

/** Ana sayfa kargo takip kutusu — sipariş numarasıyla doğrudan sipariş sayfasına götürür. */
export function KargoTakip() {
  const router = useRouter();
  const [no, setNo] = useState("");

  const sorgula = (e: React.FormEvent) => {
    e.preventDefault();
    const temiz = no.trim().toUpperCase();
    if (!temiz) return;
    router.push(`/siparis/${encodeURIComponent(temiz)}`);
  };

  return (
    <div className="flex flex-wrap items-center gap-3 rounded-2xl border border-black/10 bg-white px-4 py-3 shadow-sm sm:gap-4">
      <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-black/[.04] text-black/70">
        <Truck className="h-4.5 w-4.5" />
      </span>
      <div className="min-w-0">
        <p className="text-[13px] font-black text-black">Kargo Takip</p>
        <p className="text-[11px] font-medium text-black/35">Sipariş numaranı gir, anında durumunu gör</p>
      </div>
      <form onSubmit={sorgula} className="ml-auto flex w-full items-center gap-2 sm:w-auto">
        <input
          value={no}
          onChange={(e) => setNo(e.target.value)}
          placeholder="ORD-000000"
          className="h-10 flex-1 border border-black/15 bg-white px-3 font-mono text-[12px] uppercase placeholder:text-black/25 focus:border-black focus:outline-none sm:w-44 sm:flex-none"
        />
        <button
          type="submit"
          className="h-10 shrink-0 rounded-full bg-[var(--marka)] px-5 text-[12px] font-bold text-white transition hover:opacity-90 active:scale-95"
        >
          Sorgula
        </button>
      </form>
    </div>
  );
}
