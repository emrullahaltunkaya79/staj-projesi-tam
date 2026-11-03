"use client";

import { useState } from "react";
import { Mail } from "lucide-react";

/** Footer e-bülten kaydı — arka ofiste newsletter_subscribers tablosuna düşer. */
export function BultenFormu() {
  const [eposta, setEposta] = useState("");
  const [durum, setDurum] = useState<"hazir" | "gonderiliyor" | "tamam" | "hata">("hazir");
  const [mesaj, setMesaj] = useState("");

  const gonder = async (e: React.FormEvent) => {
    e.preventDefault();
    setDurum("gonderiliyor");
    try {
      const yanit = await fetch("/api/newsletter", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email: eposta }),
      });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.hata ?? "Kayıt oluşturulamadı.");
      setMesaj(sonuc.message ?? "Abone oldunuz.");
      setDurum("tamam");
      setEposta("");
    } catch (err) {
      setMesaj(err instanceof Error ? err.message : "Kayıt oluşturulamadı.");
      setDurum("hata");
    }
  };

  if (durum === "tamam") {
    return <p className="text-xs font-medium text-emerald-600">{mesaj}</p>;
  }

  return (
    <form onSubmit={gonder}>
      <div className="flex items-center border border-black/15 bg-white">
        <Mail className="ml-3 h-4 w-4 shrink-0 text-black/30" />
        <input
          type="email"
          required
          value={eposta}
          onChange={(e) => setEposta(e.target.value)}
          placeholder="e-posta adresiniz"
          className="h-9 w-full min-w-0 bg-transparent px-2.5 text-xs focus:outline-none"
        />
        <button
          type="submit"
          disabled={durum === "gonderiliyor"}
          className="h-9 shrink-0 bg-[var(--marka)] px-3.5 text-xs font-bold text-white transition hover:opacity-90 disabled:opacity-50"
        >
          {durum === "gonderiliyor" ? "…" : "Katıl"}
        </button>
      </div>
      {durum === "hata" && <p className="mt-1.5 text-[11px] font-medium text-red-600">{mesaj}</p>}
    </form>
  );
}
