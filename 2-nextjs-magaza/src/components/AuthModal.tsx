"use client";

import { useState } from "react";
import { X, LogIn, UserPlus, Loader2, KeyRound, MailCheck } from "lucide-react";
import { auth } from "./AuthProvider";

type Mod = "giris" | "kayit" | "sifirla" | "sifirla-kod" | "dogrula";

const MOD_BASLIK: Record<Mod, { ad: string; alt: string; ikon: typeof LogIn }> = {
  giris: { ad: "Giriş Yap", alt: "Hesabınıza erişin", ikon: LogIn },
  kayit: { ad: "Üye Ol", alt: "Hesap oluşturun, siparişlerinizi takip edin", ikon: UserPlus },
  sifirla: { ad: "Şifremi Unuttum", alt: "E-postanıza kod gönderelim", ikon: KeyRound },
  "sifirla-kod": { ad: "Yeni Şifre Belirle", alt: "Gönderdiğimiz 6 haneli kodu girin", ikon: KeyRound },
  dogrula: { ad: "E-postanı Doğrula", alt: "Gönderdiğimiz 6 haneli kodu girin", ikon: MailCheck },
};

export function AuthModal({ acik, kapat }: { acik: boolean; kapat: () => void }) {
  const { giris, kayit, emailDogrula } = auth();
  const [mod, setMod] = useState<Mod>("giris");
  const [ad, setAd] = useState("");
  const [email, setEmail] = useState("");
  const [sifre, setSifre] = useState("");
  const [sifreTekrar, setSifreTekrar] = useState("");
  const [kod, setKod] = useState("");
  const [hata, setHata] = useState<string | null>(null);
  const [bilgi, setBilgi] = useState<string | null>(null);
  const [yukleniyor, setYukleniyor] = useState(false);

  if (!acik) return null;

  const gonder = async (e: React.FormEvent) => {
    e.preventDefault();
    setHata(null);
    setBilgi(null);
    setYukleniyor(true);
    try {
      if (mod === "giris") {
        await giris(email, sifre);
        kapat();
      } else if (mod === "kayit") {
        const sonuc = await kayit(ad, email, sifre);
        setKod(sonuc.dogrulamaKodu);
        setMod("dogrula");
      } else if (mod === "sifirla") {
        const r = await fetch("/api/auth/forgot-password", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ email }),
        });
        const d = await r.json();
        if (!r.ok) throw new Error(d.message || d.errors?.email?.[0] || "Kod gönderilemedi.");
        setKod(d.data?.dogrulama_kodu ?? "");
        setBilgi("Kod gönderildi. Demo ortamında kod e-posta yerine log'a yazılır — kolaylık için doğrudan gösteriyoruz.");
        setMod("sifirla-kod");
      } else if (mod === "sifirla-kod") {
        if (sifre !== sifreTekrar) throw new Error("Şifreler eşleşmiyor.");
        const r = await fetch("/api/auth/reset-password", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ email, code: kod, password: sifre, password_confirmation: sifreTekrar }),
        });
        const d = await r.json();
        if (!r.ok) throw new Error(d.message || d.errors?.code?.[0] || d.errors?.password?.[0] || "Şifre güncellenemedi.");
        setSifre("");
        setSifreTekrar("");
        setKod("");
        setBilgi("Şifreniz güncellendi. Yeni şifrenizle giriş yapabilirsiniz.");
        setMod("giris");
      } else if (mod === "dogrula") {
        await emailDogrula(email, kod);
        setBilgi("E-postanız doğrulandı.");
        kapat();
      }
    } catch (err) {
      setHata(err instanceof Error ? err.message : "Beklenmeyen hata.");
    } finally {
      setYukleniyor(false);
    }
  };

  const baslik = MOD_BASLIK[mod];
  const Ikon = baslik.ikon;

  const etiket = "mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-black/40";
  const girisAlani = "h-11 w-full border border-black/15 bg-white px-3.5 text-sm focus:border-black focus:outline-none";

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
            <Ikon className="h-5 w-5" />
          </span>
          <div>
            <h2 className="text-lg font-black text-black">{baslik.ad}</h2>
            <p className="text-xs text-black/40">{baslik.alt}</p>
          </div>
        </div>

        <form onSubmit={gonder} className="space-y-4">
          {mod === "kayit" && (
            <div>
              <label className={etiket}>Ad Soyad *</label>
              <input required minLength={3} value={ad} onChange={(e) => setAd(e.target.value)} placeholder="Ayşe Yılmaz" className={girisAlani} />
            </div>
          )}

          {mod !== "dogrula" && (
            <div>
              <label className={etiket}>E-posta *</label>
              <input required type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="ornek@eposta.com" className={girisAlani} />
            </div>
          )}

          {(mod === "sifirla-kod" || mod === "dogrula") && (
            <div>
              <label className={etiket}>6 Haneli Kod *</label>
              <input required inputMode="numeric" pattern="[0-9]{6}" maxLength={6} value={kod}
                     onChange={(e) => setKod(e.target.value.replace(/\D/g, ""))} placeholder="000000"
                     className={`${girisAlani} text-center font-mono text-lg tracking-[0.4em]`} />
            </div>
          )}

          {(mod === "giris" || mod === "kayit" || mod === "sifirla-kod") && (
            <div>
              <label className={etiket}>
                {mod === "sifirla-kod" ? "Yeni Şifre *" : "Şifre *"}
              </label>
              <input required type="password" minLength={8} value={sifre} onChange={(e) => setSifre(e.target.value)}
                     placeholder="En az 8 karakter" className={girisAlani} />
            </div>
          )}

          {mod === "sifirla-kod" && (
            <div>
              <label className={etiket}>Yeni Şifre (Tekrar) *</label>
              <input required type="password" minLength={8} value={sifreTekrar} onChange={(e) => setSifreTekrar(e.target.value)}
                     placeholder="Şifreyi onaylayın" className={girisAlani} />
            </div>
          )}

          {hata && <p className="text-xs font-semibold text-red-600">{hata}</p>}
          {bilgi && <p className="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700">{bilgi}</p>}

          <button type="submit" disabled={yukleniyor}
                  className="h-11 w-full rounded-full bg-black text-sm font-bold text-white transition hover:bg-black/80 active:scale-95 disabled:opacity-50">
            {yukleniyor ? (
              <Loader2 className="mx-auto h-4 w-4 animate-spin" />
            ) : mod === "giris" ? "Giriş Yap" :
              mod === "kayit" ? "Hesap Oluştur" :
              mod === "sifirla" ? "Kod Gönder" :
              mod === "sifirla-kod" ? "Şifreyi Güncelle" :
              "Doğrula"}
          </button>

          {mod === "dogrula" && (
            <button type="button" onClick={kapat}
                    className="w-full text-center text-xs font-bold text-black/40 underline underline-offset-4 transition hover:text-black">
              Sonra doğrulayacağım
            </button>
          )}
        </form>

        {mod === "giris" && (
          <>
            <button type="button" onClick={() => { setMod("sifirla"); setHata(null); setBilgi(null); setKod(""); }}
                    className="mt-3 w-full text-left text-xs font-bold text-black/50 underline underline-offset-4 transition hover:text-black">
              Şifremi unuttum
            </button>
            <button type="button" onClick={() => { setMod("kayit"); setHata(null); setBilgi(null); }}
                    className="mt-3 w-full text-center text-xs font-bold text-black/50 underline underline-offset-4 transition hover:text-black">
              Hesabınız yok mu? Üye olun
            </button>
          </>
        )}

        {(mod === "kayit" || mod === "sifirla" || mod === "sifirla-kod") && (
          <button type="button" onClick={() => { setMod("giris"); setHata(null); setBilgi(null); }}
                  className="mt-5 w-full text-center text-xs font-bold text-black/50 underline underline-offset-4 transition hover:text-black">
            Zaten üye misiniz? Giriş yapın
          </button>
        )}
      </div>
    </div>
  );
}
