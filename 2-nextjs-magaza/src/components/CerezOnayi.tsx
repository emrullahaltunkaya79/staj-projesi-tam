"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { Cookie, X } from "lucide-react";
import { cerezKaydet, cerezSecimi, type CerezKategorisi } from "@/lib/cerez";

const GA_ID = process.env.NEXT_PUBLIC_GA_ID;

declare global {
  interface Window {
    dataLayer?: unknown[];
    gtag?: (...args: unknown[]) => void;
    /** Alt bilgi / politika sayfası bu olayı yayınlayarak tercih penceresini açar */
    hbaCerezAyarlariAc?: () => void;
  }
}

/** Onay verilmişse GA4 betiğini yükler (env'de ID yoksa hiçbir şey yapmaz). */
function analitikYukle() {
  if (!GA_ID || document.getElementById("ga-script")) return;
  const s = document.createElement("script");
  s.id = "ga-script";
  s.async = true;
  s.src = `https://www.googletagmanager.com/gtag/js?id=${GA_ID}`;
  document.head.appendChild(s);
  window.dataLayer = window.dataLayer ?? [];
  window.gtag = function gtag(...args: unknown[]) {
    window.dataLayer?.push(args);
  };
  window.gtag("js", new Date());
  window.gtag("config", GA_ID, { anonymize_ip: true });
}

/** Analitik rızası varsa ölçümü başlatır; rıza çekilirse betiği devre dışı bırakır. */
function analitikUygula(acik: boolean) {
  if (acik) {
    analitikYukle();
    window.gtag?.("consent", "update", { analytics_storage: "granted" });
  } else {
    window.gtag?.("consent", "update", { analytics_storage: "denied" });
    document.getElementById("ga-script")?.remove();
  }
}

const KATEGORILER: { id: CerezKategorisi; ad: string; aciklama: string; ornekler: string }[] = [
  {
    id: "fonksiyonel",
    ad: "Fonksiyonel Çerezler",
    aciklama:
      "Alışveriş deneyiminizi kişiselleştirir: favori listeniz, açık/koyu tema tercihiniz, son incelediğiniz ürünler ve arama geçmişiniz bunlarla saklanır.",
    ornekler: "hba-favoriler · hba-dark · hba-son-goruntulenen · hba-son-aramalar",
  },
  {
    id: "analitik",
    ad: "Analitik / Performans Çerezleri",
    aciklama:
      "Siteyi nasıl kullandığınızı anonim olarak ölçer: hangi sayfaların gezildiği, hangi adımda terk edildiği. Veriler IP anonimleştirilerek toplanır, kimliğinizle eşleştirilmez.",
    ornekler: "Google Analytics 4 (_ga, _ga_*)",
  },
  {
    id: "pazarlama",
    ad: "Hedefleme / Reklam Çerezleri",
    aciklama:
      "İlgi alanınıza uygun ürün reklamları göstermek ve kampanyaların etkisini ölçmek için reklam ortaklarımız tarafından kullanılır. Şu an aktif bir reklam ağı kullanmıyoruz; onayınız ileride eklenirsen geçerli olacak.",
    ornekler: "Meta Pixel · Google Ads (gelecekte eklenebilir)",
  },
];

/** Alt bilgi ve politika sayfasından tercih penceresini açmak için kullanılan düğme. */
export function CerezAyarlariDugmesi({ sinif }: { sinif?: string }) {
  return (
    <button
      type="button"
      onClick={() => window.dispatchEvent(new Event("hba-cerez-ayarlari-ac"))}
      className={sinif ?? "hover:text-white transition"}
    >
      Çerez Ayarları
    </button>
  );
}

/**
 * KVKK uyumlu çerez yönetimi — Boyner düzeni:
 * ilk ziyarette rıza bandı; bantta hızlı karar (Tümünü Kabul / Yalnızca Zorunlu),
 * ayrıntılı tercihte kategori anahtarları (zorunlu kilitli). Tercihler kalıcıdır ve
 * alt bilgideki "Çerez Ayarları" ile her an değiştirilebilir.
 */
export function CerezOnayi() {
  const [bant, setBant] = useState(false);
  const [pencere, setPencere] = useState(false);
  const [secim, setSecim] = useState({ fonksiyonel: true, analitik: true, pazarlama: true });

  useEffect(() => {
    const kayit = cerezSecimi();
    if (!kayit) {
      const z = setTimeout(() => setBant(true), 700);
      return () => clearTimeout(z);
    }
    setSecim({ fonksiyonel: kayit.fonksiyonel, analitik: kayit.analitik, pazarlama: kayit.pazarlama });
    analitikUygula(kayit.analitik);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    const ac = () => {
      setBant(false);
      setPencere(true);
    };
    window.addEventListener("hba-cerez-ayarlari-ac", ac);
    window.hbaCerezAyarlariAc = ac;
    return () => window.removeEventListener("hba-cerez-ayarlari-ac", ac);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const kaydet = (secimler: { fonksiyonel: boolean; analitik: boolean; pazarlama: boolean }) => {
    const kayit = cerezKaydet(secimler);
    analitikUygula(kayit.analitik);
    setSecim(secimler);
    setBant(false);
    setPencere(false);
  };

  const anahtarDegistir = (id: CerezKategorisi) => setSecim((s) => ({ ...s, [id]: !s[id] }));

  return (
    <>
      {/* Rıza bandı — ilk ziyaret */}
      {bant && (
        <div className="fixed bottom-4 left-4 right-4 z-[70] sm:left-auto sm:max-w-md" role="dialog" aria-label="Çerez onayı">
          <div className="rounded-2xl border border-black/10 bg-white p-5 shadow-2xl">
            <div className="flex items-start gap-3">
              <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-black text-white">
                <Cookie className="h-4.5 w-4.5" />
              </span>
              <div>
                <p className="text-sm font-black text-black">Çerez Tercihleriniz</p>
                <p className="mt-1.5 text-xs leading-relaxed text-black/60">
                  Sepet ve giriş için zorunlu çerezleri kullanıyoruz. Deneyiminizi kişiselleştiren fonksiyonel,
                  anonim ölçüm yapan analitik ve ilgi alanınıza uygun reklam çerezleri için onayınızı istiyoruz.
                </p>
                <p className="mt-1.5 text-xs text-black/40">
                  Ayrıntılar:{" "}
                  <Link href="/cerez-politikasi" className="font-bold text-black underline underline-offset-2">
                    Çerez Politikası
                  </Link>{" "}
                  ·{" "}
                  <Link href="/gizlilik" className="font-bold text-black underline underline-offset-2">
                    KVKK Aydınlatma Metni
                  </Link>
                </p>
              </div>
            </div>
            <div className="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-[1fr_auto_auto]">
              <button
                type="button"
                onClick={() => setPencere(true)}
                className="h-9 rounded-full border border-black/15 px-4 text-xs font-bold text-black/60 transition hover:border-black hover:text-black"
              >
                Çerez Ayarları
              </button>
              <button
                type="button"
                onClick={() => kaydet({ fonksiyonel: false, analitik: false, pazarlama: false })}
                className="h-9 rounded-full border border-black/15 px-4 text-xs font-bold text-black/60 transition hover:border-black hover:text-black"
              >
                Yalnızca Zorunlu
              </button>
              <button
                type="button"
                onClick={() => kaydet({ fonksiyonel: true, analitik: true, pazarlama: true })}
                className="h-9 rounded-full bg-black px-5 text-xs font-bold text-white transition hover:bg-black/80 active:scale-95"
              >
                Tümünü Kabul Et
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Ayrıntılı tercih penceresi */}
      {pencere && (
        <div className="fixed inset-0 z-[80] flex items-end justify-center p-0 sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-label="Çerez ayarları">
          <button type="button" aria-label="Kapat" onClick={() => setPencere(false)} className="absolute inset-0 cursor-default bg-black/50 backdrop-blur-[2px]" />
          <div className="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-t-3xl bg-white p-6 shadow-2xl sm:rounded-3xl">
            <div className="flex items-start justify-between gap-3">
              <div className="flex items-center gap-3">
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-black text-white">
                  <Cookie className="h-5 w-5" />
                </span>
                <div>
                  <p className="text-base font-black text-black">Çerez Ayarları</p>
                  <p className="text-xs text-black/45">Hangi kategorilere izin verdiğinizi buradan her an değiştirebilirsiniz.</p>
                </div>
              </div>
              <button type="button" onClick={() => setPencere(false)} aria-label="Kapat" className="text-black/40 transition hover:text-black">
                <X className="h-5 w-5" />
              </button>
            </div>

            {/* Zorunlu — kilitli */}
            <div className="mt-5 rounded-2xl border border-black/10 bg-[#f8f8f8] p-4">
              <div className="flex items-center justify-between gap-3">
                <p className="text-sm font-black text-black">Zorunlu Çerezler</p>
                <span className="rounded-full bg-black px-3 py-1 text-[10px] font-bold text-white">Her zaman açık</span>
              </div>
              <p className="mt-1.5 text-xs leading-relaxed text-black/55">
                Sepetiniz (hba-sepet), oturumunuz (hba-token, hba-user) ve güvenlik önlemleri için gerekli. Bu
                çerezler kapatılamaz — kapatıldığında alışveriş ve giriş çalışmaz.
              </p>
            </div>

            {/* Kategoriler */}
            {KATEGORILER.map((k) => {
              const acik = secim[k.id];
              return (
                <div key={k.id} className="mt-3 rounded-2xl border border-black/10 p-4">
                  <div className="flex items-center justify-between gap-3">
                    <p className="text-sm font-black text-black">{k.ad}</p>
                    <button
                      type="button"
                      role="switch"
                      aria-checked={acik}
                      onClick={() => anahtarDegistir(k.id)}
                      className={`relative h-6 w-11 shrink-0 rounded-full transition ${acik ? "bg-emerald-500" : "bg-black/20"}`}
                    >
                      <span className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all ${acik ? "left-[22px]" : "left-0.5"}`} />
                    </button>
                  </div>
                  <p className="mt-1.5 text-xs leading-relaxed text-black/55">{k.aciklama}</p>
                  <p className="mt-1 font-mono text-[10px] text-black/30">{k.ornekler}</p>
                </div>
              );
            })}

            <div className="mt-5 flex flex-col gap-2 sm:flex-row">
              <button
                type="button"
                onClick={() => kaydet(secim)}
                className="h-11 flex-1 rounded-full bg-black text-sm font-bold text-white transition hover:bg-black/80 active:scale-95"
              >
                Tercihlerimi Kaydet
              </button>
              <button
                type="button"
                onClick={() => kaydet({ fonksiyonel: true, analitik: true, pazarlama: true })}
                className="h-11 rounded-full border border-black/15 px-6 text-sm font-bold text-black/60 transition hover:border-black hover:text-black"
              >
                Tümünü Kabul Et
              </button>
            </div>
            <p className="mt-3 text-center text-[11px] text-black/35">
              Tercihiniz cihazınızda saklanır ve{" "}
              <Link href="/cerez-politikasi" className="font-bold underline underline-offset-2">
                Çerez Politikası
              </Link>
              &apos;na göre işlenir. Onayı reddetmek yalnızca zorunlu çerezler dışında kileri kapatır; alışverişe devam edebilirsiniz.
            </p>
          </div>
        </div>
      )}
    </>
  );
}
