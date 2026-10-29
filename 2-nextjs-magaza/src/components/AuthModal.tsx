"use client";

import { useState } from "react";
import { X, LogIn, UserPlus, Loader2 } from "lucide-react";
import { auth } from "./AuthProvider";

export function AuthModal({ acik, kapat }: { acik: boolean; kapat: () => void }) {
  const { giris, kayit } = auth();
  const [mod, setMod] = useState<"giris" | "kayit">("giris");
  const [ad, setAd] = useState("");
  const [email, setEmail] = useState("");
  const [sifre, setSifre] = useState("");
  const [hata, setHata] = useState<string | null>(null);
  const [yukleniyor, setYukleniyor] = useState(false);

  if (!acik) return null;

  const gonder = async (e: React.FormEvent) => {
    e.preventDefault();
    setHata(null);
    setYukleniyor(true);
    try {
      if (mod === "giris") {
        await giris(email, sifre);
      } else {
        await kayit(ad, email, sifre);
      }
      kapat();
    } catch (err) {
      setHata(err instanceof Error ? err.message : "Beklenmeyen hata.");
    } finally {
      setYukleniyor(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[60] flex items-center justify-center p-4">
      <div onClick={kapat} className="absolute inset-0 bg-black/50 backdrop-blur-sm" />
      <div className="relative w-full max-w-sm rounded-2xl bg-white p-7 shadow-2xl">
        <button type="button" onClick={kapat} aria-label="Kapat"
                className="absolute right-4 top-4 text-black/30 transition hover:text-black">
          <X className="h-5 w-5" />
        </button>

        <div className="mb-6 flex items-center gap-3">
          <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-black text-white">
            {mod === "giris" ? <LogIn className="h-5 w-5" /> : <UserPlus className="h-5 w-5" />}
          </span>
          <div>
            <h2 className="text-lg font-black text-black">
              {mod === "giris" ? "Giriş Yap" : "Üye Ol"}
            </h2>
            <p className="text-xs text-black/40">
              {mod === "giris" ? "Hesabınıza erişin" : "Hesap oluşturun, siparişlerinizi takip edin"}
            </p>
          </div>
        </div>

        <form onSubmit={gonder} className="space-y-4">
          {mod === "kayit" && (
            <div>
              <label className="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-black/40">Ad Soyad *</label>
              <input required minLength={3} value={ad} onChange={(e) => setAd(e.target.value)} placeholder="Ayşe Yılmaz"
                     className="h-11 w-full border border-black/15 bg-white px-3.5 text-sm focus:border-black focus:outline-none" />
            </div>
          )}
          <div>
            <label className="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-black/40">E-posta *</label>
            <input required type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="ornek@eposta.com"
                   className="h-11 w-full border border-black/15 bg-white px-3.5 text-sm focus:border-black focus:outline-none" />
          </div>
          <div>
            <label className="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-black/40">Şifre *</label>
            <input required type="password" minLength={8} value={sifre} onChange={(e) => setSifre(e.target.value)} placeholder="En az 8 karakter"
                   className="h-11 w-full border border-black/15 bg-white px-3.5 text-sm focus:border-black focus:outline-none" />
          </div>

          {hata && <p className="text-xs font-semibold text-red-600">{hata}</p>}

          <button type="submit" disabled={yukleniyor}
                  className="h-11 w-full rounded-full bg-black text-sm font-bold text-white transition hover:bg-black/80 active:scale-95 disabled:opacity-50">
            {yukleniyor ? <Loader2 className="mx-auto h-4 w-4 animate-spin" /> : mod === "giris" ? "Giriş Yap" : "Hesap Oluştur"}
          </button>
        </form>

        <button type="button" onClick={() => { setMod(mod === "giris" ? "kayit" : "giris"); setHata(null); }}
                className="mt-5 w-full text-center text-xs font-bold text-black/50 underline underline-offset-4 transition hover:text-black">
          {mod === "giris" ? "Hesabınız yok mu? Üye olun" : "Zaten üye misiniz? Giriş yapın"}
        </button>
      </div>
    </div>
  );
}
