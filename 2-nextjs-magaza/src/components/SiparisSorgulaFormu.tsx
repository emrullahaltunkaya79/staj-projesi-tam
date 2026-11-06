"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Search } from "lucide-react";

/** Misafir sipariş erişimi: sipariş no + siparişte kullanılan e-posta birlikte doğrulanır. */
export function SiparisSorgulaFormu({ varsayilanNo = "" }: { varsayilanNo?: string }) {
  const router = useRouter();
  const [no, setNo] = useState(varsayilanNo);
  const [email, setEmail] = useState("");
  const [hata, setHata] = useState<string | null>(null);
  const [calisiyor, setCalisiyor] = useState(false);

  const sorgula = async (e: React.FormEvent) => {
    e.preventDefault();
    setHata(null);
    setCalisiyor(true);
    try {
      const yanit = await fetch("/api/siparis-sorgula", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ no, email }),
      });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.hata ?? "Sipariş bulunamadı.");
      router.push(sonuc.bag);
    } catch (err) {
      setHata(err instanceof Error ? err.message : "Beklenmeyen hata.");
      setCalisiyor(false);
    }
  };

  const alan = "h-12 w-full border border-black/20 bg-white px-4 text-sm focus:border-black focus:outline-none";

  return (
    <form onSubmit={sorgula} className="space-y-3">
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <input required value={no} onChange={(e) => setNo(e.target.value.toUpperCase())}
               placeholder="Sipariş no (ORD-000123)" aria-label="Sipariş numarası" className={`${alan} font-mono`} />
        <input required type="email" value={email} onChange={(e) => setEmail(e.target.value)}
               placeholder="Siparişte kullandığınız e-posta" aria-label="E-posta" className={alan} />
      </div>
      {hata && <p className="text-sm font-medium text-red-600">{hata}</p>}
      <button type="submit" disabled={calisiyor}
              className="flex h-12 w-full items-center justify-center gap-2 rounded-full bg-black text-sm font-bold text-white transition hover:bg-black/80 active:scale-95 disabled:opacity-50">
        <Search className="h-4 w-4" /> {calisiyor ? "Sorgulanıyor…" : "Siparişimi göster"}
      </button>
    </form>
  );
}
