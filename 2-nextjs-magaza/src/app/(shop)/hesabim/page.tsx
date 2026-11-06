"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { auth } from "@/components/AuthProvider";
import { AuthModal } from "@/components/AuthModal";

type Adres = { id: number; baslik: string; ad_soyad: string; telefon: string; il: string; ilce: string; acik_adres: string };

const etiket = "mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-black/40";
const alan = "h-11 w-full border border-black/15 bg-white px-3.5 text-sm focus:border-black focus:outline-none";

export default function HesabimSayfasi() {
  const { kullanici, girisYapmis, cikis } = auth();
  const router = useRouter();
  const [girisAcik, setGirisAcik] = useState(false);

  const [ad, setAd] = useState("");
  const [telefon, setTelefon] = useState("");
  const [profilMesaj, setProfilMesaj] = useState<string | null>(null);
  const [profilHata, setProfilHata] = useState<string | null>(null);

  const [mevcutSifre, setMevcutSifre] = useState("");
  const [yeniSifre, setYeniSifre] = useState("");
  const [sifreTekrar, setSifreTekrar] = useState("");
  const [sifreMesaj, setSifreMesaj] = useState<string | null>(null);
  const [sifreHata, setSifreHata] = useState<string | null>(null);

  const [adresler, setAdresler] = useState<Adres[]>([]);
  const [yeniAdres, setYeniAdres] = useState({ baslik: "", ad_soyad: "", telefon: "", il: "", ilce: "", acik_adres: "" });
  const [adresFormAcik, setAdresFormAcik] = useState(false);

  useEffect(() => {
    if (girisYapmis && kullanici) {
      setAd(kullanici.name);
      setTelefon(kullanici.phone ?? "");
      fetch("/api/hesap/adresler")
        .then((r) => (r.ok ? r.json() : null))
        .then((d) => d && setAdresler(d.data ?? []))
        .catch(() => {});
    }
  }, [girisYapmis, kullanici?.id]); // eslint-disable-line react-hooks/exhaustive-deps

  if (!girisYapmis) {
    return (
      <div className="flex min-h-[50vh] flex-col items-center justify-center px-5 text-center">
        <p className="text-3xl">👤</p>
        <h1 className="mt-3 text-[15px] font-black uppercase tracking-wide text-black">Hesabım</h1>
        <p className="mt-2 max-w-sm text-sm text-black/55">Profiliniz, şifreniz ve kayıtlı adresleriniz için giriş yapın.</p>
        <button type="button" onClick={() => setGirisAcik(true)}
                className="mt-6 h-12 rounded-full bg-black px-8 text-sm font-bold text-white transition hover:bg-black/80">
          Giriş yap
        </button>
        <AuthModal acik={girisAcik} kapat={() => { setGirisAcik(false); if (auth().girisYapmis) router.refresh(); }} />
      </div>
    );
  }

  const profilKaydet = async (e: React.FormEvent) => {
    e.preventDefault();
    setProfilMesaj(null);
    setProfilHata(null);
    try {
      const yanit = await fetch("/api/hesap/profil", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name: ad, phone: telefon || null }),
      });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.message ?? "Kaydedilemedi.");
      setProfilMesaj(sonuc.message);
      router.refresh();
    } catch (err) {
      setProfilHata(err instanceof Error ? err.message : "Beklenmeyen hata.");
    }
  };

  const sifreKaydet = async (e: React.FormEvent) => {
    e.preventDefault();
    setSifreMesaj(null);
    setSifreHata(null);
    try {
      const yanit = await fetch("/api/hesap/sifre", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ mevcut: mevcutSifre, yeni: yeniSifre, yeni_confirmation: sifreTekrar }),
      });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.message ?? (sonuc.errors ? Object.values(sonuc.errors)[0] : "Şifre değiştirilemedi."));
      setSifreMesaj(sonuc.message);
      setMevcutSifre("");
      setYeniSifre("");
      setSifreTekrar("");
    } catch (err) {
      setSifreHata(err instanceof Error ? err.message : "Beklenmeyen hata.");
    }
  };

  const adresEkle = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const yanit = await fetch("/api/hesap/adresler", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(yeniAdres),
      });
      const sonuc = await yanit.json();
      if (!yanit.ok) throw new Error(sonuc.message ?? (sonuc.errors ? Object.values(sonuc.errors)[0] : "Adres kaydedilemedi."));
      setAdresler((l) => [...l, sonuc.data]);
      setYeniAdres({ baslik: "", ad_soyad: "", telefon: "", il: "", ilce: "", acik_adres: "" });
      setAdresFormAcik(false);
    } catch (err) {
      setSifreHata(err instanceof Error ? err.message : "Beklenmeyen hata.");
    }
  };

  const adresSil = async (id: number) => {
    if (!window.confirm("Bu adresi silmek istediğinize emin misiniz?")) return;
    await fetch(`/api/hesap/adresler/${id}`, { method: "DELETE" });
    setAdresler((l) => l.filter((a) => a.id !== id));
  };

  const kart = "rounded-2xl border border-black/10 bg-white p-6";

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-3xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[15px] font-black uppercase tracking-wide text-black sm:text-[17px]">Hesabım</h1>
          <p className="mt-2 text-sm text-black/50">{kullanici?.email}{kullanici?.email_verified === false && " (e-posta doğrulanmadı)"}</p>
        </div>

        <div className="mt-8 space-y-6">
          {/* Profil */}
          <form onSubmit={profilKaydet} className={kart}>
            <h2 className="text-[14px] font-black text-black">Profil bilgileri</h2>
            <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label className={etiket}>Ad Soyad</label>
                <input required minLength={3} value={ad} onChange={(e) => setAd(e.target.value)} className={alan} />
              </div>
              <div>
                <label className={etiket}>Telefon</label>
                <input type="tel" value={telefon} onChange={(e) => setTelefon(e.target.value)} placeholder="0532 123 45 67" className={alan} />
              </div>
            </div>
            {profilMesaj && <p className="mt-3 text-xs font-semibold text-emerald-600">{profilMesaj}</p>}
            {profilHata && <p className="mt-3 text-xs font-semibold text-red-600">{profilHata}</p>}
            <button type="submit" className="mt-4 h-10 rounded-full bg-black px-6 text-xs font-bold text-white transition hover:bg-black/80">Kaydet</button>
          </form>

          {/* Şifre */}
          <form onSubmit={sifreKaydet} className={kart}>
            <h2 className="text-[14px] font-black text-black">Şifre değiştir</h2>
            <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
              <input required type="password" value={mevcutSifre} onChange={(e) => setMevcutSifre(e.target.value)} placeholder="Mevcut şifre" className={alan} />
              <input required type="password" minLength={8} value={yeniSifre} onChange={(e) => setYeniSifre(e.target.value)} placeholder="Yeni şifre (en az 8)" className={alan} />
              <input required type="password" minLength={8} value={sifreTekrar} onChange={(e) => setSifreTekrar(e.target.value)} placeholder="Yeni şifre (tekrar)" className={alan} />
            </div>
            {sifreMesaj && <p className="mt-3 text-xs font-semibold text-emerald-600">{sifreMesaj}</p>}
            {sifreHata && <p className="mt-3 text-xs font-semibold text-red-600">{sifreHata}</p>}
            <button type="submit" className="mt-4 h-10 rounded-full bg-black px-6 text-xs font-bold text-white transition hover:bg-black/80">Şifreyi güncelle</button>
          </form>

          {/* Kayıtlı adresler */}
          <div className={kart}>
            <div className="flex items-center justify-between">
              <h2 className="text-[14px] font-black text-black">Kayıtlı adresler</h2>
              <button type="button" onClick={() => setAdresFormAcik((a) => !a)}
                      className="rounded-full border border-black/15 px-4 py-1.5 text-[12px] font-bold text-black/70 transition hover:border-black hover:text-black">
                {adresFormAcik ? "Vazgeç" : "+ Yeni adres"}
              </button>
            </div>

            {adresFormAcik && (
              <form onSubmit={adresEkle} className="mt-4 grid grid-cols-1 gap-3 rounded-xl border border-black/10 p-4 sm:grid-cols-2">
                <input required value={yeniAdres.baslik} onChange={(e) => setYeniAdres((a) => ({ ...a, baslik: e.target.value }))} placeholder="Adres adı (Ev, Ofis)" className={alan} />
                <input required value={yeniAdres.ad_soyad} onChange={(e) => setYeniAdres((a) => ({ ...a, ad_soyad: e.target.value }))} placeholder="Alıcı ad soyad" className={alan} />
                <input required value={yeniAdres.telefon} onChange={(e) => setYeniAdres((a) => ({ ...a, telefon: e.target.value }))} placeholder="Telefon" className={alan} />
                <div className="grid grid-cols-2 gap-3">
                  <input required value={yeniAdres.il} onChange={(e) => setYeniAdres((a) => ({ ...a, il: e.target.value }))} placeholder="İl" className={alan} />
                  <input required value={yeniAdres.ilce} onChange={(e) => setYeniAdres((a) => ({ ...a, ilce: e.target.value }))} placeholder="İlçe" className={alan} />
                </div>
                <textarea required minLength={10} rows={2} value={yeniAdres.acik_adres} onChange={(e) => setYeniAdres((a) => ({ ...a, acik_adres: e.target.value }))}
                          placeholder="Açık adres" className="w-full border border-black/15 bg-white px-3.5 py-2.5 text-sm focus:border-black focus:outline-none sm:col-span-2" />
                <button type="submit" className="h-10 rounded-full bg-black px-6 text-xs font-bold text-white transition hover:bg-black/80 sm:col-span-2">Adresi kaydet</button>
              </form>
            )}

            <div className="mt-4 space-y-3">
              {adresler.length === 0 && <p className="text-[13px] text-black/45">Kayıtlı adresiniz yok. Ödeme sırasında girdiğiniz adresi "hesabıma kaydet" ile buraya ekleyebilirsiniz.</p>}
              {adresler.map((a) => (
                <div key={a.id} className="flex items-start justify-between gap-4 rounded-xl border border-black/10 p-4 text-[13px]">
                  <div>
                    <p className="font-bold text-black">📍 {a.baslik} <span className="font-medium text-black/50">· {a.ad_soyad}</span></p>
                    <p className="mt-0.5 text-black/60">{a.telefon}</p>
                    <p className="mt-0.5 text-black/60">{a.acik_adres}, {a.ilce} / {a.il}</p>
                  </div>
                  <button type="button" onClick={() => adresSil(a.id)} className="shrink-0 text-[11px] font-bold text-black/30 transition hover:text-red-600">Sil</button>
                </div>
              ))}
            </div>
          </div>

          <button type="button" onClick={cikis}
                  className="flex h-11 items-center gap-2 rounded-full border border-red-200 px-6 text-sm font-bold text-red-600 transition hover:bg-red-50">
            Çıkış yap
          </button>
        </div>
      </div>
    </div>
  );
}
