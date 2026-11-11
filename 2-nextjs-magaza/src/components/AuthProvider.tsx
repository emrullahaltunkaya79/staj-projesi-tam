"use client";

import { createContext, useContext, useEffect, useState } from "react";

export type AuthUser = {
  id: number;
  name: string;
  email: string;
  role?: string;
  phone?: string | null;
  contact_id: number;
  email_verified?: boolean;
};

export type KayitSonuc = {
  kullanici: AuthUser;
  /** Demo: mail log'a gittiği için kayıt yanıtı kodu taşır */
  dogrulamaKodu: string;
};

type AuthBaglami = {
  kullanici: AuthUser | null;
  girisYapmis: boolean;
  giris: (email: string, password: string) => Promise<AuthUser>;
  kayit: (name: string, email: string, password: string) => Promise<KayitSonuc>;
  cikis: () => Promise<void>;
  emailDogrula: (email: string, kod: string) => Promise<void>;
};

const Baglam = createContext<AuthBaglami | null>(null);
const TOKEN_KEY = "hba-token";
const USER_KEY = "hba-user";

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [kullanici, setKullanici] = useState<AuthUser | null>(null);
  const [googleUyari, setGoogleUyari] = useState<string | null>(null);

  useEffect(() => {
    // Google girişi hata kodları — kullanıcıya anlaşılır mesajla bildirilir
    const googleHata = new URLSearchParams(window.location.search).get("googleHata");
    if (googleHata) {
      const mesajlar: Record<string, string> = {
        yapilandirma: "Google ile giriş henüz yapılandırılmadı — panel → Ayarlar → Google ile Giriş.",
        guvenlik: "Güvenlik doğrulaması başarısız oldu, lütfen tekrar deneyin.",
        iptal: "Google girişi iptal edildi.",
        token: "Google oturumu alınamadı, lütfen tekrar deneyin.",
        dogrulama: "Google e-postanız doğrulanmamış.",
        satici: "Google girişi yalnızca müşteri hesapları içindir.",
        kod: "Google yetkilendirmesi tamamlanamadı.",
      };
      setGoogleUyari(mesajlar[googleHata] ?? "Google girişi tamamlanamadı.");
      setTimeout(() => setGoogleUyari(null), 8000);
      window.history.replaceState({}, "", window.location.pathname);
    }
  }, []);

  useEffect(() => {
    try {
      const kayitliKullanici = localStorage.getItem(USER_KEY);
      const token = localStorage.getItem(TOKEN_KEY);
      if (kayitliKullanici && token) {
        setKullanici(JSON.parse(kayitliKullanici));
        // Token geçerli mi kontrol et
        fetch("/api/auth/me", { headers: { Authorization: `Bearer ${token}` } })
          .then((r) => (r.ok ? r.json() : Promise.reject()))
          .then((d) => {
            setKullanici(d.data);
            localStorage.setItem(USER_KEY, JSON.stringify(d.data));
          })
          .catch(() => {
            localStorage.removeItem(TOKEN_KEY);
            localStorage.removeItem(USER_KEY);
            setKullanici(null);
          })
          .finally(() => window.dispatchEvent(new Event("hba-oturum")));
      }

      // Google girişi dönüşü: tek kullanımlık kodu token ile değiştir
      const googleKod = new URLSearchParams(window.location.search).get("giris");
      if (googleKod) {
        fetch("/api/auth/google-exchange", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ code: googleKod }),
        })
          .then(async (r) => {
            const d = await r.json();
            if (!r.ok) throw new Error(d.message ?? "Giriş tamamlanamadı.");
            localStorage.setItem(TOKEN_KEY, d.data.token);
            localStorage.setItem(USER_KEY, JSON.stringify(d.data.user));
            setKullanici(d.data.user);
          })
          .catch(() => {})
          .finally(() => {
            window.history.replaceState({}, "", window.location.pathname);
            window.dispatchEvent(new Event("hba-oturum"));
          });
      }
    } catch {}
  }, []);

  const giris = async (email: string, password: string): Promise<AuthUser> => {
    const r = await fetch("/api/auth/login", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, password }),
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || d.errors?.email?.[0] || "Giriş başarısız.");
    localStorage.setItem(TOKEN_KEY, d.data.token);
    localStorage.setItem(USER_KEY, JSON.stringify(d.data.user));
    setKullanici(d.data.user);
    window.dispatchEvent(new Event("hba-oturum"));
    return d.data.user;
  };

  const kayit = async (name: string, email: string, password: string): Promise<KayitSonuc> => {
    const r = await fetch("/api/auth/register", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name, email, password }),
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || d.errors?.email?.[0] || "Kayıt başarısız.");
    localStorage.setItem(TOKEN_KEY, d.data.token);
    localStorage.setItem(USER_KEY, JSON.stringify(d.data.user));
    setKullanici(d.data.user);
    window.dispatchEvent(new Event("hba-oturum"));
    return { kullanici: d.data.user, dogrulamaKodu: d.data.dogrulama_kodu ?? "" };
  };

  const emailDogrula = async (email: string, kod: string): Promise<void> => {
    const r = await fetch("/api/auth/verify-email", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, code: kod }),
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || d.errors?.code?.[0] || "Doğrulama başarısız.");
    // Yerel kullanıcıyı doğrulanmış işaretle
    const guncel = kullanici ? { ...kullanici, email_verified: true } : null;
    if (guncel) {
      setKullanici(guncel);
      localStorage.setItem(USER_KEY, JSON.stringify(guncel));
      window.dispatchEvent(new Event("hba-oturum"));
    }
  };

  const cikis = async () => {
    const token = localStorage.getItem(TOKEN_KEY);
    if (token) {
      fetch("/api/auth/logout", { method: "POST", headers: { Authorization: `Bearer ${token}` } }).catch(() => {});
    }
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    setKullanici(null);
    window.dispatchEvent(new Event("hba-oturum"));
  };

  return (
    <Baglam.Provider value={{ kullanici, girisYapmis: !!kullanici, giris, kayit, cikis, emailDogrula }}>
      {children}
      {googleUyari && (
        <div className="fixed bottom-6 left-1/2 z-[90] -translate-x-1/2 rounded-full border border-amber-200 bg-amber-50 px-5 py-2.5 text-xs font-bold text-amber-800 shadow-lg" role="alert">
          ⚠️ {googleUyari}
        </div>
      )}
    </Baglam.Provider>
  );
}

export function auth() {
  const baglam = useContext(Baglam);
  if (!baglam) throw new Error("auth() ancak AuthProvider içinde kullanılabilir");
  return baglam;
}

export function authToken(): string | null {
  return typeof window !== "undefined" ? localStorage.getItem(TOKEN_KEY) : null;
}
