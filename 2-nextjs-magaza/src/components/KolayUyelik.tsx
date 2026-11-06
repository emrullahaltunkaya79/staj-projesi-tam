"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { UserPlus, Loader2 } from "lucide-react";
import { auth } from "./AuthProvider";

type MisafirSiparis = { orderNo: string; name: string; email: string };

const ANAHTAR = "hba-misafir-siparis";

/**
 * Misafir siparişinden sonra tek adımda üyelik: ad ve e-posta siparişten gelir,
 * yalnızca şifre istenir. Aynı e-postalı müşteri kaydı yeniden kullanıldığı için
 * bu sipariş otomatik olarak yeni hesaba bağlanır.
 */
export function KolayUyelik({ orderNo }: { orderNo: string }) {
  const { kullanici, kayit, emailDogrula } = auth();
  const [misafir, setMisafir] = useState<MisafirSiparis | null>(null);
  const [adim, setAdim] = useState<"form" | "dogrula" | "tamam">("form");
  const [sifre, setSifre] = useState("");
  const [kod, setKod] = useState("");
  const [hata, setHata] = useState<string | null>(null);
  const [yukleniyor, setYukleniyor] = useState(false);

  useEffect(() => {
    try {
      const kayitli = JSON.parse(sessionStorage.getItem(ANAHTAR) ?? "null") as MisafirSiparis | null;
      if (kayitli?.orderNo === orderNo) setMisafir(kayitli);
    } catch {}
  }, [orderNo]);

  if (!misafir || (kullanici && adim === "form")) return null;

  const bitir = () => {
    try { sessionStorage.removeItem(ANAHTAR); } catch {}
    setAdim("tamam");
  };

  const hesapOlustur = async (e: React.FormEvent) => {
    e.preventDefault();
    setHata(null);
    setYukleniyor(true);
    try {
      const sonuc = await kayit(misafir.name, misafir.email, sifre);
      setKod(sonuc.dogrulamaKodu);
      setAdim("dogrula");
    } catch (err) {
      setHata(err instanceof Error ? err.message : "Hesap oluşturulamadı.");
    } finally {
      setYukleniyor(false);
    }
  };

  const dogrula = async (e: React.FormEvent) => {
    e.preventDefault();
    setHata(null);
    setYukleniyor(true);
    try {
      await emailDogrula(misafir.email, kod);
      bitir();
    } catch (err) {
      setHata(err instanceof Error ? err.message : "Doğrulama başarısız.");
    } finally {
      setYukleniyor(false);
    }
  };

  if (adim === "tamam") {
    return (
      <div className="mb-8 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4">
        <p className="text-sm font-bold text-emerald-800">✓ Hesabınız hazır — bu sipariş hesabınıza bağlandı.</p>
        <Link href="/siparislerim" className="mt-1 inline-block text-xs font-bold text-emerald-700 underline underline-offset-4">
          Siparişlerim sayfasına git →
        </Link>
      </div>
    );
  }

  return (
    <div className="mb-8 rounded-xl border border-black/10 bg-white p-5 shadow-sm">
      <div className="flex items-start gap-3">
        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-black text-white">
          <UserPlus className="h-4 w-4" />
        </span>
        <div className="min-w-0 flex-1">
          <p className="text-sm font-black text-black">
            {adim === "form" ? "Siparişinizi tek adımda hesabınıza kaydedin" : "E-postanızı doğrulayın"}
          </p>
          <p className="mt-0.5 text-xs leading-relaxed text-black/50">
            {adim === "form"
              ? <>Bilgileriniz siparişten alındı (<b className="text-black/70">{misafir.email}</b>) — sadece bir şifre belirleyin. Bu ve sonraki siparişleriniz hesabınızda listelenir.</>
              : "Demo ortamında kod e-posta yerine log'a yazılır — kolaylık için doldurduk."}
          </p>

          {adim === "form" ? (
            <form onSubmit={hesapOlustur} className="mt-3 flex flex-wrap gap-2">
              <input required type="password" minLength={8} value={sifre} onChange={(e) => setSifre(e.target.value)}
                     placeholder="Şifre (en az 8 karakter)" aria-label="Şifre"
                     className="h-10 min-w-0 flex-1 border border-black/15 bg-white px-3 text-sm focus:border-black focus:outline-none" />
              <button type="submit" disabled={yukleniyor}
                      className="h-10 shrink-0 rounded-full bg-black px-5 text-xs font-bold text-white transition hover:bg-black/80 disabled:opacity-50">
                {yukleniyor ? <Loader2 className="h-4 w-4 animate-spin" /> : "Hesap Oluştur"}
              </button>
            </form>
          ) : (
            <form onSubmit={dogrula} className="mt-3 flex flex-wrap items-center gap-2">
              <input required inputMode="numeric" pattern="[0-9]{6}" maxLength={6} value={kod}
                     onChange={(e) => setKod(e.target.value.replace(/\D/g, ""))} aria-label="6 haneli kod"
                     className="h-10 w-32 border border-black/15 bg-white px-3 text-center font-mono text-sm tracking-[0.3em] focus:border-black focus:outline-none" />
              <button type="submit" disabled={yukleniyor}
                      className="h-10 shrink-0 rounded-full bg-black px-5 text-xs font-bold text-white transition hover:bg-black/80 disabled:opacity-50">
                {yukleniyor ? <Loader2 className="h-4 w-4 animate-spin" /> : "Doğrula"}
              </button>
              <button type="button" onClick={bitir}
                      className="text-xs font-bold text-black/40 underline underline-offset-4 transition hover:text-black">
                Sonra doğrulayacağım
              </button>
            </form>
          )}

          {hata && <p className="mt-2 text-xs font-semibold text-red-600">{hata}</p>}
        </div>
      </div>
    </div>
  );
}
