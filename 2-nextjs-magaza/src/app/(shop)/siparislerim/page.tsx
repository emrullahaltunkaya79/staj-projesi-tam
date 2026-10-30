"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { auth } from "@/components/AuthProvider";

type SiparisOzeti = {
  order_no: string;
  status: string;
  status_label?: string;
  total: number;
  invoice_no?: string | null;
  created_at: string;
  tracking_url?: string | null;
};

const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);
const DURUM: Record<string, string> = {
  pending: "Bekliyor",
  paid: "Ödendi",
  preparing: "Hazırlanıyor",
  shipped: "Kargoda",
  delivered: "Teslim",
  cancelled: "İptal",
};

export default function Siparislerim() {
  const { kullanici, girisYapmis } = auth();
  const [email, setEmail] = useState("");
  const [siparisler, setSiparisler] = useState<SiparisOzeti[] | null>(null);
  const [yukleniyor, setYukleniyor] = useState(false);
  const [hata, setHata] = useState<string | null>(null);

  const siparisleriGetir = async (eposta: string) => {
    setYukleniyor(true);
    setHata(null);
    setSiparisler(null);
    try {
      const yanit = await fetch(`/api/orders/list?email=${encodeURIComponent(eposta)}`);
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.hata ?? "Siparişler alınamadı.");
      setSiparisler(sonuc.data);
    } catch (err) {
      setHata(err instanceof Error ? err.message : "Beklenmeyen hata.");
    } finally {
      setYukleniyor(false);
    }
  };

  // Girişli kullanıcı: kendi e-postasıyla siparişlerini otomatik getir
  useEffect(() => {
    if (girisYapmis && kullanici?.email) {
      setSiparisler(null);
      siparisleriGetir(kullanici.email);
    } else {
      setSiparisler(null);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [girisYapmis, kullanici?.email]);

  const getir = (e: React.FormEvent) => {
    e.preventDefault();
    siparisleriGetir(email);
  };

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-3xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[56px] font-black leading-none tracking-[-0.03em] sm:text-[84px]">Siparişlerim</h1>
          {girisYapmis && kullanici && (
            <p className="mt-3 text-sm font-medium text-black/60">
              Hoş geldin <span className="font-bold text-black">{kullanici.name}</span> — siparişleriniz otomatik listeleniyor.
            </p>
          )}
        </div>

        {!girisYapmis && (
          <form onSubmit={getir} className="mt-8 flex gap-3">
            <input
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="Sipariş verirken kullandığınız e-posta"
              className="h-12 flex-1 border border-black/20 bg-white px-4 text-sm focus:border-black focus:outline-none"
            />
            <button
              type="submit"
              disabled={yukleniyor}
              className="h-12 shrink-0 rounded-full bg-black px-7 text-sm font-bold text-white transition hover:bg-black/80 active:scale-95 disabled:opacity-50"
            >
              {yukleniyor ? "…" : "Göster"}
            </button>
          </form>
        )}

        {girisYapmis && yukleniyor && (
          <p className="mt-8 text-sm font-medium text-black/40">Siparişleriniz yükleniyor…</p>
        )}

        {hata && <p className="mt-5 text-sm font-medium text-red-600">{hata}</p>}

        {siparisler && siparisler.length === 0 && (
          <div className="mt-12 border-t border-black/15 pt-10 text-center">
            <p className="text-sm text-black/50">
              {girisYapmis ? "Hesabınızla ilişkili sipariş bulunamadı." : "Bu e-postayla sipariş bulunamadı."}
            </p>
            <Link href="/" className="mt-4 inline-block text-sm font-bold underline underline-offset-4">
              Alışverişe başla →
            </Link>
          </div>
        )}

        {siparisler && siparisler.length > 0 && (
          <div className="mt-10 divide-y divide-black/10 border-y border-black/15">
            {siparisler.map((s) => (
              <Link key={s.order_no} href={`/siparis/${s.order_no}`} className="group flex items-center gap-5 py-5">
                <div className="min-w-0 flex-1">
                  <div className="flex flex-wrap items-center gap-2.5">
                    <span className="font-mono text-sm font-bold">{s.order_no}</span>
                    <span className="rounded-full bg-black/5 px-2.5 py-0.5 text-[11px] font-bold text-black/70">
                      {s.status_label ?? DURUM[s.status] ?? s.status}
                    </span>
                    {s.invoice_no && <span className="text-[11px] text-black/40">Fatura: {s.invoice_no}</span>}
                  </div>
                  <p className="mt-1 text-xs text-black/40">
                    {new Date(s.created_at).toLocaleString("tr-TR", { dateStyle: "medium", timeStyle: "short" })}
                  </p>
                </div>
                <span className="text-base font-black tabular-nums">{fmt(s.total)} ₺</span>
                <span className="text-black/25 transition group-hover:translate-x-1 group-hover:text-black">→</span>
              </Link>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
