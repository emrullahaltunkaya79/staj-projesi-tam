"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Search, ShoppingCart, User, LogOut, ChevronDown, Heart, Globe, Moon, Sun } from "lucide-react";
import { sepet } from "./CartProvider";
import { auth } from "./AuthProvider";
import { favori } from "./FavoriProvider";
import { DarkModeToggle, karanlikModKullan } from "./DarkModeToggle";
import { dil } from "./DilProvider";
import { LiveChat } from "./LiveChat";
import { CerezOnayi } from "./CerezOnayi";
import { GeriSayim } from "./GeriSayim";
import { TemaSecici } from "./TemaSecici";
import { KaydirmaYardimcisi } from "./KaydirmaYardimcisi";
import { LogoIshari } from "./Logo";
import { AuthModal } from "./AuthModal";
import { BultenFormu } from "./BultenFormu";

/**
 * Tüm mağaza sayfalarını saran kabuk: duyuru bandı + header + kategori menüsü + alt bilgi.
 */
const KATEGORI_LINKLERI = [
  "Bilgisayar", "Elektronik", "Ev & Yaşam", "Kişisel Bakım", "Kırtasiye",
  "Müzik", "Ofis", "Oyun & Eğlence", "Spor & Outdoor", "Fotoğraf & Kamera",
  "Ev Sineması", "Wellness & Sağlık",
];

export default function SiteShell({ children }: { children: React.ReactNode }) {
  const { toplamAdet } = sepet();
  const router = useRouter();
  const [bannerAcik, setBannerAcik] = useState(true);
  const [q, setQ] = useState("");
  const { kullanici, girisYapmis, cikis } = auth();
  const { sayi: favoriSayi } = favori();
  const { dil: aktifDil, dilDegistir, cevir } = dil();
  const { dark: karanlik, degistir: karanlikDegistir } = karanlikModKullan();
  const [authModalAcik, setAuthModalAcik] = useState(false);
  const [menuAcik, setMenuAcik] = useState(false);

  const ara = (e: React.FormEvent) => {
    e.preventDefault();
    const terim = q.trim();
    if (terim) router.push(`/ara?q=${encodeURIComponent(terim)}`);
  };

  return (
    <div className="flex min-h-screen flex-col bg-white text-black antialiased">
      {bannerAcik && (
        <div className="relative bg-[#1a1a2e] px-3 py-1 text-center">
          <p className="text-[11px] font-medium text-white/85 sm:text-xs">
            İlk siparişine <strong className="font-black text-white">%15 indirim</strong> — Kupon:{" "}
            <code className="rounded bg-white/15 px-1.5 py-px font-mono text-[10px] font-bold text-white">ILKSIPARIS</code>
            <span className="mx-2 text-white/25">·</span>
            2.000 ₺ üzeri ÜCRETSİZ kargo!
            <span className="ml-2 inline-flex items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 align-middle text-[10px] font-bold text-white">
              ⏳ <GeriSayim />
            </span>
          </p>
          <button type="button" onClick={() => setBannerAcik(false)} aria-label="Kapat"
                  className="absolute right-2 top-1/2 -translate-y-1/2 text-[11px] text-white/50 transition hover:text-white">✕</button>
        </div>
      )}

      <header className="sticky top-0 z-40 border-b-2 border-black bg-[#1a1a2e] px-5 pb-4 pt-5 shadow-lg shadow-black/20 sm:px-10">
        <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-8 gap-y-4">
          <a href="/" className="leading-none">
            <span className="flex items-center gap-2 text-[28px] font-black tracking-[-0.04em] text-white sm:text-[36px]"><LogoIshari boyut={30} />HepsiBirarada<span className="text-indigo-400">.</span></span>
            <span className="mt-0.5 block text-center text-[11px] font-medium tracking-wide text-indigo-200/70 sm:text-xs">Teknoloji & Ofis — her şey tek yerde</span>
          </a>

          <form onSubmit={ara} className="order-3 flex h-10 w-full max-w-md items-center overflow-hidden rounded-lg border border-white/20 bg-white/10 backdrop-blur sm:order-2 sm:w-auto sm:flex-1">
            <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Ürün ara…"
                   className="h-full flex-1 bg-transparent px-4 text-sm text-white placeholder-white/40 focus:outline-none" />
            <button type="submit" aria-label="Ara" className="flex h-full w-10 items-center justify-center border-l border-white/10 text-white/60 transition bg-[var(--marka)] hover:opacity-90 hover:text-white">
              <Search className="h-4 w-4" />
            </button>
          </form>

          <div className="order-2 flex items-center gap-4 sm:order-3">
            {/* Dil değiştirici */}
            <button
              type="button"
              onClick={() => dilDegistir(aktifDil === "tr" ? "en" : "tr")}
              title={aktifDil === "tr" ? "Switch to English" : "Türkçe'ye geç"}
              className="flex items-center gap-1 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-[11px] font-black text-white/80 backdrop-blur transition hover:border-white/40"
            >
              <Globe className="h-3 w-3" />
              {aktifDil === "tr" ? "EN" : "TR"}
            </button>
            <a href="/favoriler" aria-label="Favoriler"
               className="relative flex items-center gap-1.5 text-white/70 transition hover:text-white">
              <Heart className="h-5 w-5" strokeWidth={1.8} />
              {favoriSayi > 0 && (
                <span className="text-xs font-bold tabular-nums">{favoriSayi}</span>
              )}
            </a>
            <a href="/sepet" aria-label="Sepet" data-sepet-hedef className="relative flex items-center gap-1.5 rounded-full bg-[var(--marka)] px-3.5 py-2 text-white transition hover:opacity-90 active:scale-95">
              <ShoppingCart className="h-4 w-4" strokeWidth={2} />
              <span className="text-sm font-bold tabular-nums">{toplamAdet}</span>
            </a>
            <TemaSecici />
            {/* Giriş yapılmamışken karanlık mod butonu header'da kalır; giriş sonrası menü içine taşınır */}
            {!girisYapmis && <DarkModeToggle />}

            {girisYapmis && kullanici ? (
              <div className="relative">
                <button type="button" onClick={() => setMenuAcik(!menuAcik)}
                        className="flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-sm text-white backdrop-blur transition hover:border-white/40">
                  <span className="flex h-7 w-7 items-center justify-center rounded-full bg-[var(--marka)] text-xs font-black">
                    {kullanici.name.charAt(0).toUpperCase()}
                  </span>
                  <span className="hidden max-w-[100px] truncate font-bold sm:block">{kullanici.name.split(" ")[0]}</span>
                  <ChevronDown className={`h-3.5 w-3.5 text-white/50 transition-transform ${menuAcik ? "rotate-180" : ""}`} />
                </button>
                {menuAcik && (
                  <div className="absolute right-0 top-full z-50 mt-2 w-56 overflow-hidden rounded-xl border border-black/10 bg-white shadow-xl">
                    <div className="border-b border-black/10 px-4 py-3">
                      <p className="text-sm font-bold text-black">{kullanici.name}</p>
                      <p className="text-xs text-black/40">{kullanici.email}</p>
                    </div>
                    <button
                      type="button"
                      onClick={karanlikDegistir}
                      className="flex w-full items-center justify-between px-4 py-3 text-sm font-medium text-black/70 transition hover:bg-black/5"
                    >
                      <span className="flex items-center gap-3">
                        {karanlik ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
                        Karanlık Mod
                      </span>
                      <span className={`relative h-5 w-9 shrink-0 rounded-full transition-colors ${karanlik ? "bg-[var(--marka)]" : "bg-black/10"}`}>
                        <span className={`absolute top-0.5 h-4 w-4 rounded-full bg-gray-100 shadow transition-all ${karanlik ? "left-[18px]" : "left-0.5"}`} />
                      </span>
                    </button>
                    <a href="/siparislerim" onClick={() => setMenuAcik(false)}
                       className="flex items-center gap-3 px-4 py-3 text-sm font-medium text-black/70 transition hover:bg-black/5">
                      <ShoppingCart className="h-4 w-4" /> Siparişlerim
                    </a>
                    <button type="button" onClick={() => { cikis(); setMenuAcik(false); }}
                            className="flex w-full items-center gap-3 border-t border-black/10 px-4 py-3 text-sm font-medium text-red-600 transition hover:bg-red-50">
                      <LogOut className="h-4 w-4" /> Çıkış Yap
                    </button>
                  </div>
                )}
              </div>
            ) : (
              <button type="button" onClick={() => setAuthModalAcik(true)}
                      className="flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-[13px] font-bold text-white backdrop-blur transition hover:border-white/40 hover:bg-white/20">
                <User className="h-3.5 w-3.5" />
                Giriş Yap
              </button>
            )}
          </div>
        </div>
      </header>

      {/* Alt menü çubuğu — kampanyalar + kategoriler (her sayfada) */}
      <nav aria-label="Kategoriler ve kampanyalar" className="border-b border-white/10 bg-[#1a1a2e] px-5 sm:px-10">
        <div className="mx-auto flex max-w-7xl items-center gap-0.5 overflow-x-auto py-1.5 [scrollbar-width:thin]">
          <a href="/" className="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-black text-white transition hover:bg-white/10">Tüm Ürünler</a>
          <a href="/flas-indirimler" className="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-black text-amber-400 transition hover:bg-white/10">⚡ Flaş İndirimler</a>
          <a href="/outlet" className="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-bold text-white/75 transition hover:bg-white/10 hover:text-white">Outlet</a>
          <a href="/cok-satanlar" className="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-bold text-white/75 transition hover:bg-white/10 hover:text-white">★ Çok Satanlar</a>
          <a href="/son-firsat" className="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-bold text-white/75 transition hover:bg-white/10 hover:text-white">Son Fırsat</a>
          <a href="/son-kalan" className="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-bold text-white/75 transition hover:bg-white/10 hover:text-white">Son Kalan</a>
          <a href="/sezon" className="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-bold text-white/75 transition hover:bg-white/10 hover:text-white">Sezon</a>
          <span className="mx-2 h-4 w-px shrink-0 bg-white/15" aria-hidden />
          {KATEGORI_LINKLERI.map((k) => (
            <a key={k} href={`/kategori/${encodeURIComponent(k)}`}
               className="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-bold text-white/75 transition hover:bg-white/10 hover:text-white">
              {k}
            </a>
          ))}
        </div>
      </nav>

      <AuthModal acik={authModalAcik} kapat={() => setAuthModalAcik(false)} />
      <KaydirmaYardimcisi />
      <LiveChat />

      <div className="page-enter flex-1">{children}</div>

      <footer className="border-t border-black/10 px-5 py-10 sm:px-10">
        <div className="mx-auto max-w-7xl">
          <div className="flex flex-wrap gap-8 text-xs text-black/50">
            <div className="flex-1 min-w-[160px]">
              <div className="text-[10px] font-bold uppercase tracking-widest text-black/30 mb-3">Mağaza</div>
              <div className="flex flex-col gap-2">
                <a href="/" className="hover:text-black transition">Tüm Ürünler</a>
                <a href="/flas-indirimler" className="font-bold text-red-600 transition hover:text-red-700">⚡ Flaş İndirimler</a>
                <a href="/outlet" className="font-bold text-red-600 transition hover:text-red-700">🏭 Outlet</a>
                <a href="/cok-satanlar" className="hover:text-black transition">★ En Çok Satanlar</a>
                <a href="/son-kalan" className="hover:text-black transition">📏 Son Kalan Ölçüler</a>
                <a href="/son-firsat" className="hover:text-black transition">⏳ Son Fırsat</a>
                <a href="/sezon" className="hover:text-black transition">🗓️ Sezon Ürünleri</a>
                <a href="/sepet" className="hover:text-black transition">Sepetim</a>
                <a href="/siparislerim" className="hover:text-black transition">Siparişlerim</a>
              </div>
            </div>
            <div className="flex-1 min-w-[160px]">
              <div className="text-[10px] font-bold uppercase tracking-widest text-black/30 mb-3">Kurumsal</div>
              <div className="flex flex-col gap-2">
                <a href="/hakkinda" className="hover:text-black transition">Hakkında</a>
                <a href="/iletisim" className="hover:text-black transition">İletişim</a>
              </div>
            </div>
            <div className="flex-[2] min-w-[220px]">
              <div className="text-[10px] font-bold uppercase tracking-widest text-black/30 mb-3">Kampanyalardan Haberdar Ol</div>
              <div className="mb-5 max-w-[280px]"><BultenFormu /></div>
              <div className="text-[10px] font-bold uppercase tracking-widest text-black/30 mb-3">Bilgi</div>
              <div className="flex flex-col gap-2">
                <span>⛓️ CSPR ile kripto ödeme</span>
                <span>🔒 HMAC imzalı güvenli API</span>
                <a href="/sozlesme/mesafeli-satis" className="hover:text-black transition">↩️ 14 gün koşulsuz iade — Mesafeli Satış Sözleşmesi</a>
              </div>
            </div>
            <div className="flex-1 min-w-[160px]">
              <div className="text-[10px] font-bold uppercase tracking-widest text-black/30 mb-3">Yasal</div>
              <div className="flex flex-col gap-2">
                <a href="/sozlesme/mesafeli-satis" className="hover:text-black transition">Mesafeli Satış Sözleşmesi</a>
                <a href="/sozlesme/uyelik" className="hover:text-black transition">Üyelik Sözleşmesi</a>
                <a href="/gizlilik" className="hover:text-black transition">KVKK Aydınlatma Metni</a>
                <a href="/cerez-politikasi" className="hover:text-black transition">Çerez Politikası</a>
              </div>
            </div>
          </div>
          <div className="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-black/5 pt-6 text-xs text-black/30">
            <span>© {new Date().getFullYear()} HepsiBirarada. Tüm hakları saklıdır.</span>
            <span>Stok & sipariş: <strong className="font-semibold text-black/50">Ön Muhasebe API</strong> ile gerçek zamanlı.</span>
          </div>
        </div>
      </footer>

      <CerezOnayi />
    </div>
  );
}
