"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { auth } from "@/components/AuthProvider";
import { AuthModal } from "@/components/AuthModal";
import { SiparisSorgulaFormu } from "@/components/SiparisSorgulaFormu";

type SiparisOzeti = {
  order_no: string;
  status: string;
  status_label?: string;
  total: number;
  invoice_no?: string | null;
  created_at: string;
};

const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

export default function Siparislerim() {
  const { kullanici, girisYapmis, emailDogrula } = auth();
  const [siparisler, setSiparisler] = useState<SiparisOzeti[] | null>(null);
  const [yukleniyor, setYukleniyor] = useState(false);
  const [hata, setHata] = useState<string | null>(null);
  const [dogrulamaGerekli, setDogrulamaGerekli] = useState(false);
  const [kod, setKod] = useState("");
  const [kodGonderildi, setKodGonderildi] = useState(false);
  const [girisAcik, setGirisAcik] = useState(false);
  const [iptalEdilen, setIptalEdilen] = useState<string | null>(null);
  const [mesaj, setMesaj] = useState<string | null>(null);

  const getir = async () => {
    setYukleniyor(true);
    setHata(null);
    try {
      const yanit = await fetch("/api/orders/list", { cache: "no-store" });
      const sonuc = await yanit.json();
      if (yanit.status === 403 && sonuc.dogrulamaGerekli) {
        setDogrulamaGerekli(true);
        setSiparisler(null);
        return;
      }
      if (!yanit.ok) throw new Error(sonuc.hata ?? "Siparişler alınamadı.");
      setDogrulamaGerekli(false);
      setSiparisler(sonuc.data);
    } catch (err) {
      setHata(err instanceof Error ? err.message : "Beklenmeyen hata.");
    } finally {
      setYukleniyor(false);
    }
  };

  useEffect(() => {
    if (girisYapmis) getir();
    else setSiparisler(null);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [girisYapmis, kullanici?.email_verified]);

  const kodGonder = async () => {
    if (!kullanici) return;
    const yanit = await fetch("/api/auth/resend-verification", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email: kullanici.email }),
    });
    const sonuc = await yanit.json().catch(() => ({}));
    // Demo ortamında kod e-posta yerine log'a gider; kolaylık için doldurulur
    setKod(sonuc.data?.dogrulama_kodu ?? "");
    setKodGonderildi(true);
  };

  const dogrula = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!kullanici) return;
    try {
      await emailDogrula(kullanici.email, kod);
      await getir();
    } catch (err) {
      setHata(err instanceof Error ? err.message : "Doğrulama başarısız.");
    }
  };

  const iptalEt = async (no: string) => {
    if (!window.confirm(`${no} numaralı siparişi iptal etmek istediğinize emin misiniz?`)) return;
    setIptalEdilen(no);
    setMesaj(null);
    try {
      const yanit = await fetch(`/api/orders/${no}/cancel`, { method: "POST" });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.hata ?? "Sipariş iptal edilemedi.");
      setMesaj(`${no} iptal edildi — rezerve stoklar serbest bırakıldı.`);
      await getir();
    } catch (err) {
      setMesaj(err instanceof Error ? err.message : "Sipariş iptal edilemedi.");
    } finally {
      setIptalEdilen(null);
    }
  };

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-3xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[15px] font-black uppercase tracking-wide text-black sm:text-[17px]">Siparişlerim</h1>
          {girisYapmis && kullanici && (
            <p className="mt-3 text-sm font-medium text-black/60">
              Hoş geldin <span className="font-bold text-black">{kullanici.name}</span> — hesabınızdaki siparişler.
            </p>
          )}
        </div>

        {!girisYapmis && (
          <div className="mt-8 grid grid-cols-1 gap-8 md:grid-cols-[1fr_auto_1fr]">
            <div>
              <p className="text-sm font-black text-black">Üyeyim</p>
              <p className="mt-1 text-xs leading-relaxed text-black/50">Tüm siparişlerinizi görmek için giriş yapın.</p>
              <button type="button" onClick={() => setGirisAcik(true)}
                      className="mt-4 h-12 w-full rounded-full bg-black text-sm font-bold text-white transition hover:bg-black/80">
                Giriş yap
              </button>
            </div>
            <div className="hidden w-px bg-black/10 md:block" />
            <div>
              <p className="text-sm font-black text-black">Misafir siparişi</p>
              <p className="mt-1 text-xs leading-relaxed text-black/50">Sipariş numarası + e-posta ile görüntüleyin.</p>
              <div className="mt-4"><SiparisSorgulaFormu /></div>
            </div>
          </div>
        )}

        {girisYapmis && dogrulamaGerekli && (
          <div className="mt-8 rounded-xl border border-amber-200 bg-amber-50 p-5">
            <p className="text-sm font-bold text-amber-900">E-postanızı doğrulayın</p>
            <p className="mt-1 text-xs leading-relaxed text-amber-800/80">
              Siparişler e-posta adresine göre eşleştirildiği için, listelemeden önce {kullanici?.email} adresinin size ait olduğunu doğrulamamız gerekiyor.
            </p>
            {kodGonderildi ? (
              <form onSubmit={dogrula} className="mt-3 flex gap-2">
                <input required inputMode="numeric" pattern="[0-9]{6}" maxLength={6} value={kod}
                       onChange={(e) => setKod(e.target.value.replace(/\D/g, ""))} aria-label="6 haneli doğrulama kodu"
                       className="h-10 w-36 border border-amber-300 bg-white px-3 text-center font-mono tracking-[0.3em]" />
                <button type="submit" className="h-10 rounded-full bg-black px-5 text-xs font-bold text-white">Doğrula</button>
              </form>
            ) : (
              <button type="button" onClick={kodGonder} className="mt-3 h-10 rounded-full bg-black px-5 text-xs font-bold text-white">
                Doğrulama kodu gönder
              </button>
            )}
          </div>
        )}

        {girisYapmis && yukleniyor && <p className="mt-8 text-sm font-medium text-black/40">Siparişleriniz yükleniyor…</p>}
        {hata && <p className="mt-5 text-sm font-medium text-red-600">{hata}</p>}
        {mesaj && (
          <p className={`mt-5 text-sm font-bold ${mesaj.includes("iptal edildi") ? "text-emerald-600" : "text-red-600"}`}>{mesaj}</p>
        )}

        {siparisler && siparisler.length === 0 && (
          <div className="mt-12 border-t border-black/15 pt-10 text-center">
            <p className="text-sm text-black/50">Hesabınızla ilişkili sipariş bulunamadı.</p>
            <Link href="/" className="mt-4 inline-block text-sm font-bold underline underline-offset-4">Alışverişe başla →</Link>
          </div>
        )}

        {siparisler && siparisler.length > 0 && (
          <div className="mt-10 divide-y divide-black/10 border-y border-black/15">
            {siparisler.map((s) => (
              <div key={s.order_no} className="group flex items-center gap-5 py-5">
                <Link href={`/siparis/${s.order_no}`} className="group/link flex min-w-0 flex-1 items-center gap-5">
                  <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2.5">
                      <span className="font-mono text-sm font-bold">{s.order_no}</span>
                      <span className="rounded-full bg-black/5 px-2.5 py-0.5 text-[11px] font-bold text-black/70">{s.status_label ?? s.status}</span>
                      {s.invoice_no && <span className="text-[11px] text-black/40">Fatura: {s.invoice_no}</span>}
                    </div>
                    <p className="mt-1 text-xs text-black/40">
                      {new Date(s.created_at).toLocaleString("tr-TR", { dateStyle: "medium", timeStyle: "short" })}
                    </p>
                  </div>
                  <span className="text-base font-black tabular-nums">{fmt(s.total)} ₺</span>
                  <span className="text-black/25 transition group-hover/link:translate-x-1 group-hover/link:text-black">→</span>
                </Link>
                {["pending", "paid"].includes(s.status) && (
                  <button type="button" onClick={() => iptalEt(s.order_no)} disabled={iptalEdilen === s.order_no}
                          className="shrink-0 rounded-full border border-red-200 px-3.5 py-1.5 text-[11px] font-bold text-red-600 transition hover:border-red-600 hover:bg-red-50 disabled:opacity-50">
                    {iptalEdilen === s.order_no ? "İptal ediliyor…" : "İptal Et"}
                  </button>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
      <AuthModal acik={girisAcik} kapat={() => setGirisAcik(false)} />
    </div>
  );
}
