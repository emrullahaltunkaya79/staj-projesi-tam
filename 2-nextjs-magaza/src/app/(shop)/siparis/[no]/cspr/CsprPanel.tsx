"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";

type OdemeBilgisi = {
  address: string;
  memo: string;
  amount_cspr: number;
  amount_try: number;
  network: string;
  rate: number;
  expires_at: string;
  qr_data_uri: string;
};

export function CsprPanel({ orderNo }: { orderNo: string }) {
  const router = useRouter();
  const [bilgi, setBilgi] = useState<OdemeBilgisi | null>(null);
  const [hata, setHata] = useState<string | null>(null);
  const [kalan, setKalan] = useState("--:--");
  const [kopya, setKopya] = useState("");

  useEffect(() => {
    (async () => {
      try {
        const r = await fetch(`/api/orders/${orderNo}/cspr`, { method: "POST" });
        const s = await r.json();
        if (!r.ok) throw new Error(s.hata ?? "Talimat alınamadı.");
        setBilgi(s.data);
      } catch (e) {
        setHata(e instanceof Error ? e.message : "Beklenmeyen hata.");
      }
    })();
  }, [orderNo]);

  // geri sayım
  useEffect(() => {
    if (!bilgi) return;
    const tick = () => {
      const sn = Math.max(0, Math.floor((new Date(bilgi.expires_at).getTime() - Date.now()) / 1000));
      setKalan(`${Math.floor(sn / 60)}:${String(sn % 60).padStart(2, "0")}`);
    };
    const t = setInterval(tick, 1000);
    tick();
    return () => clearInterval(t);
  }, [bilgi]);

  // durum yoklaması
  useEffect(() => {
    if (!bilgi) return;
    const t = setInterval(async () => {
      try {
        const r = await fetch(`/api/orders/${orderNo}/cspr/status?memo=${encodeURIComponent(bilgi.memo)}`);
        const s = await r.json();
        if (s.paid) router.push(`/siparis/${orderNo}`);
      } catch {}
    }, 5000);
    return () => clearInterval(t);
  }, [bilgi, orderNo, router]);

  const kopyala = (metin: string, etiket: string) => {
    navigator.clipboard.writeText(metin).then(() => {
      setKopya(etiket);
      setTimeout(() => setKopya(""), 1500);
    });
  };

  if (hata) {
    return (
      <div className="mt-8 border-l-4 border-amber-500 bg-amber-50 px-5 py-4">
        <p className="text-sm font-bold text-amber-800">Ödeme talimatı alınamadı</p>
        <p className="mt-1 text-xs text-amber-700">{hata}</p>
        <a href={`/siparis/${orderNo}`} className="mt-3 inline-block text-xs font-bold underline underline-offset-4">← Siparişe dön</a>
      </div>
    );
  }

  if (!bilgi) {
    return <div className="mt-8 py-16 text-center text-sm text-black/40">Talimat hazırlanıyor…</div>;
  }

  const cspr = bilgi.amount_cspr < 0.01 ? bilgi.amount_cspr.toFixed(6) : bilgi.amount_cspr.toFixed(2);

  return (
    <div className="mt-8 space-y-6">
      <div className="flex items-start gap-6">
        <div className="shrink-0 border-2 border-black/10 p-2">
          {bilgi.qr_data_uri ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={bilgi.qr_data_uri} alt="Ödeme QR" className="h-40 w-40" />
          ) : (
            <div className="flex h-40 w-40 items-center justify-center text-4xl">⛓️</div>
          )}
        </div>
        <div className="flex-1 space-y-4">
          <div>
            <div className="text-2xl font-black tabular-nums">{cspr} CSPR</div>
            <div className="text-xs text-black/40">≈ {new Intl.NumberFormat("tr-TR").format(bilgi.amount_try)} ₺ · 1 CSPR = {bilgi.rate} ₺ · {bilgi.network}</div>
          </div>
          <div>
            <p className="text-[10px] font-bold uppercase tracking-widest text-black/40">Alıcı Adresi</p>
            <button type="button" onClick={() => kopyala(bilgi.address, "adres")}
                    className="mt-1 w-full border border-black/15 bg-neutral-50 px-3 py-2 text-left font-mono text-[11px] text-black/70 transition hover:border-black">
              {bilgi.address.slice(0, 24)}…{bilgi.address.slice(-8)} {kopya === "adres" && <span className="font-bold text-emerald-600">✓</span>}
            </button>
          </div>
          <div>
            <p className="text-[10px] font-bold uppercase tracking-widest text-black/40">MEMO (zorunlu)</p>
            <button type="button" onClick={() => kopyala(bilgi.memo, "memo")}
                    className="mt-1 w-full border border-black/15 bg-neutral-50 px-3 py-2 text-left font-mono text-[11px] text-black/70 transition hover:border-black">
              {bilgi.memo} {kopya === "memo" && <span className="font-bold text-emerald-600">✓</span>}
            </button>
          </div>
        </div>
      </div>

      <div className="flex items-center justify-between border-t border-black/15 pt-4">
        <div className="flex items-center gap-2 text-sm font-bold text-black/60">
          <span className="relative flex h-2.5 w-2.5"><span className="absolute h-full w-full animate-ping rounded-full bg-amber-400 opacity-75" /><span className="relative h-2.5 w-2.5 rounded-full bg-amber-500" /></span>
          Ödeme bekleniyor…
        </div>
        <span className="font-mono text-sm font-bold tabular-nums">{kalan}</span>
      </div>

      <p className="text-[11px] leading-relaxed text-black/40">
        Casper Wallet ile QR'ı okutun veya doğru tutarda ve <strong>aynen bu memo ile</strong> gönderin.
        Ödeme zincirde görüldüğünde siparişiniz otomatik onaylanır ve faturanız kesilir.
      </p>

      <a href={`/siparis/${orderNo}`} className="block text-center text-xs font-bold text-black/40 transition hover:text-black">← Siparişe dön</a>
    </div>
  );
}
