"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { Search, ShoppingCart, User, LogOut, ChevronDown, Heart, Globe, Moon, Sun, Menu } from "lucide-react";
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
import { KargoTakip } from "./KargoTakip";

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
  const [aramaOdak, setAramaOdak] = useState(false);
  const [megaAcik, setMegaAcik] = useState<string | null>(null);
  const [menuVerisi, setMenuVerisi] = useState<Record<string, { urun: number; markalar: { ad: string; sayi: number }[] }> | null>(null);
  const [oneriler, setOneriler] = useState<{
    urunler: { id: number; name: string; brand?: string | null; fiyat: number; image: string | null }[];
    markalar: string[];
  } | null>(null);

  const POPULER_ARAMALAR = ["Kulaklık", "Projeksiyon", "Akıllı Saat", "Drone", "Klavye", "Hoparlör"];

  // Mega menü verisi ilk hover'da bir kez yüklenir
  const menuGetir = () => {
    if (menuVerisi) return;
    fetch("/api/menu")
      .then((r) => r.json())
      .then((d) => setMenuVerisi(d.data ?? {}))
      .catch(() => {});
  };

  const megaAc = (ad: string) => {
    if (!window.matchMedia("(min-width: 1024px)").matches) return; // yalnızca masaüstü
    menuGetir();
    setMegaAcik(ad);
  };

  // Canlı arama önerisi: yazarken 250 ms debounce ile ürün/marka eşleşmeleri
  useEffect(() => {
    const terim = q.trim();
    if (!aramaOdak || terim.length < 2) {
      setOneriler(null);
      return;
    }
    const z = setTimeout(() => {
      fetch(`/api/oneri?q=${encodeURIComponent(terim)}`)
        .then((r) => r.json())
        .then((d) => setOneriler(d.data ?? { urunler: [], markalar: [] }))
        .catch(() => setOneriler(null));
    }, 250);
    return () => clearTimeout(z);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [q, aramaOdak]);

  const ara = (e: React.FormEvent) => {
    e.preventDefault();
    const terim = q.trim();
    if (terim) router.push(`/ara?q=${encodeURIComponent(terim)}`);
  };

  return (
    <div className="flex min-h-screen flex-col bg-white text-black antialiased">
      {bannerAcik && (
        <div className="relative bg-[#334155] px-3 py-1 text-center">
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

      <header className="sticky top-0 z-40 border-b border-black/10 bg-white px-5 pb-3 pt-4 shadow-sm sm:px-10">
        <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-8 gap-y-3">
          <a href="/" className="flex items-center gap-2.5 leading-none">
            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-[var(--marka)] text-[17px] font-black text-white">hb</span>
            <span>
              <span className="block text-[24px] font-black tracking-[-0.03em] text-black sm:text-[30px]">HepsiBirarada<span className="text-[var(--marka)]">.</span></span>
              <span className="block text-[10.5px] font-medium tracking-wide text-black/45">Teknoloji & Ofis — her şey tek yerde</span>
            </span>
          </a>

          <div className="relative order-3 w-full max-w-md sm:order-2 sm:w-auto sm:flex-1"
               onBlur={(e) => { if (!e.currentTarget.contains(e.relatedTarget as Node)) setAramaOdak(false); }}>
            <form onSubmit={ara} className="flex h-10 w-full items-center overflow-hidden rounded-lg border border-black/15 bg-[#f8f8f8]">
              <input value={q} onChange={(e) => setQ(e.target.value)} onFocus={() => setAramaOdak(true)} placeholder="Ürün ara…"
                     className="h-full flex-1 bg-transparent px-4 text-sm text-black placeholder-black/35 focus:outline-none" />
              <button type="submit" aria-label="Ara" className="flex h-full w-11 items-center justify-center text-white transition bg-[var(--marka)] hover:opacity-90">
                <Search className="h-4 w-4" />
              </button>
            </form>
            {aramaOdak && (
              <div className="absolute left-0 right-0 top-11 z-50 rounded-xl border border-black/10 bg-white p-3 shadow-2xl">
                {oneriler && (oneriler.urunler.length > 0 || oneriler.markalar.length > 0) ? (
                  <>
                    <p className="mb-1.5 text-[10px] font-bold uppercase tracking-widest text-black/40">Öneriler</p>
                    <div className="space-y-0.5">
                      {oneriler.urunler.map((u) => (
                        <button
                          key={u.id}
                          type="button"
                          onMouseDown={() => { setAramaOdak(false); router.push(`/urun/${u.id}`); }}
                          className="flex w-full items-center gap-2.5 rounded-lg px-1.5 py-1.5 text-left transition hover:bg-black/5"
                        >
                          {u.image ? (
                            // eslint-disable-next-line @next/next/no-img-element
                            <img src={u.image} alt="" className="h-8 w-8 shrink-0 rounded-md object-contain" />
                          ) : (
                            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-black/5 text-[10px] font-black text-black/30">{u.name.charAt(0)}</span>
                          )}
                          <span className="min-w-0 flex-1">
                            {u.brand && <span className="block text-[9px] font-black uppercase tracking-widest text-black/35">{u.brand}</span>}
                            <span className="block truncate text-[12px] font-semibold text-black">{u.name}</span>
                          </span>
                          <span className="shrink-0 text-[11px] font-bold tabular-nums text-black/50">
                            {new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(u.fiyat)} ₺
                          </span>
                        </button>
                      ))}
                    </div>
                    {oneriler.markalar.length > 0 && (
                      <div className="mt-2 border-t border-black/10 pt-2">
                        <p className="mb-1.5 text-[10px] font-bold uppercase tracking-widest text-black/40">Markalar</p>
                        <div className="flex flex-wrap gap-1.5">
                          {oneriler.markalar.map((m) => (
                            <button
                              key={m}
                              type="button"
                              onMouseDown={() => { setAramaOdak(false); router.push(`/marka/${encodeURIComponent(m)}`); }}
                              className="rounded-full border border-black/10 px-3 py-1.5 text-[11px] font-bold text-black/60 transition hover:border-[var(--marka)] hover:bg-[var(--marka)] hover:text-white"
                            >
                              {m}
                            </button>
                          ))}
                        </div>
                      </div>
                    )}
                  </>
                ) : (
                  <>
                    <p className="mb-2 text-[10px] font-bold uppercase tracking-widest text-black/40">Popüler Aramalar</p>
                    <div className="flex flex-wrap gap-1.5">
                      {POPULER_ARAMALAR.map((terim) => (
                        <button
                          key={terim}
                          type="button"
                          onMouseDown={() => { setAramaOdak(false); router.push(`/ara?q=${encodeURIComponent(terim)}`); }}
                          className="rounded-full border border-black/10 px-3 py-1.5 text-[11px] font-bold text-black/60 transition hover:border-[var(--marka)] hover:bg-[var(--marka)] hover:text-white"
                        >
                          {terim}
                        </button>
                      ))}
                    </div>
                  </>
                )}
              </div>
            )}
          </div>

          <div className="order-2 flex items-center gap-4 sm:order-3">
            {/* Dil değiştirici */}
            <button
              type="button"
              onClick={() => dilDegistir(aktifDil === "tr" ? "en" : "tr")}
              title={aktifDil === "tr" ? "Switch to English" : "Türkçe'ye geç"}
              className="header-chip flex items-center gap-1 rounded-full border border-black/15 bg-white px-3 py-1.5 text-[11px] font-black text-black/60 transition hover:border-black/40"
            >
              <Globe className="h-3 w-3" />
              {aktifDil === "tr" ? "EN" : "TR"}
            </button>
            <a href="/favoriler" aria-label="Favoriler"
               className="relative flex items-center gap-1.5 text-black/55 transition hover:text-[var(--marka)]">
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
                        className="header-chip flex items-center gap-2 rounded-full border border-black/15 bg-white px-3 py-1.5 text-sm text-black transition hover:border-black/40">
                  <span className="flex h-7 w-7 items-center justify-center rounded-full bg-[var(--marka)] text-xs font-black text-white">
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
                      className="header-chip flex items-center gap-2 rounded-full border border-black/15 bg-white px-4 py-2 text-[13px] font-bold text-black/70 transition hover:border-black/40 hover:text-black">
                <User className="h-3.5 w-3.5" />
                Giriş Yap
              </button>
            )}
          </div>
        </div>
      </header>

      {/* Alt menü çubuğu — kampanyalar + kategoriler (her sayfada) */}
      <nav
        aria-label="Kategoriler ve kampanyalar"
        onMouseLeave={() => setMegaAcik(null)}
        className="relative border-b border-black/10 bg-white px-5 sm:px-10"
      >
        <div className="mx-auto flex max-w-7xl items-center gap-0.5 overflow-x-auto py-1.5 [scrollbar-width:thin]">
          <a href="#kategoriler" className="flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-black uppercase tracking-wide text-black transition hover:bg-black/5">
            <Menu className="h-3.5 w-3.5 text-[var(--marka)]" /> Tüm Kategoriler
          </a>
          <span className="mx-1.5 h-4 w-px shrink-0 bg-black/10" aria-hidden />
          <a href="/" className="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-black text-black transition hover:bg-black/5">Tüm Ürünler</a>
          <a href="/flas-indirimler" className="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-black text-orange-600 transition hover:bg-orange-50">⚡ Flaş İndirimler</a>
          <a href="/outlet" className="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-bold text-black/60 transition hover:bg-black/5 hover:text-black">Outlet</a>
          <a href="/cok-satanlar" className="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-bold text-black/60 transition hover:bg-black/5 hover:text-black">★ Çok Satanlar</a>
          <a href="/son-firsat" className="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-bold text-black/60 transition hover:bg-black/5 hover:text-black">Son Fırsat</a>
          <a href="/son-kalan" className="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-bold text-black/60 transition hover:bg-black/5 hover:text-black">Son Kalan</a>
          <a href="/sezon" className="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-bold text-black/60 transition hover:bg-black/5 hover:text-black">Sezon</a>
          <span className="mx-2 h-4 w-px shrink-0 bg-black/10" aria-hidden />
          {KATEGORI_LINKLERI.map((k) => (
            <a key={k} href={`/kategori/${encodeURIComponent(k)}`}
               onMouseEnter={() => megaAc(k)}
               className={`shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-[12px] font-bold transition hover:bg-black/5 hover:text-[var(--marka)] ${
                 megaAcik === k ? "bg-black/5 text-[var(--marka)]" : "text-black/60"
               }`}>
              {k}
            </a>
          ))}
        </div>

        {/* Mega menü paneli — hover edilen kategorinin markaları */}
        {megaAcik && menuVerisi?.[megaAcik] && (
          <div className="absolute inset-x-0 top-full z-50 hidden lg:block">
            <div className="mx-auto max-w-7xl rounded-b-2xl border-x border-b border-black/10 bg-white p-5 shadow-2xl">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-baseline gap-3">
                  <h3 className="text-[15px] font-black uppercase tracking-wide text-black">{megaAcik}</h3>
                  <span className="text-[12px] font-medium text-black/35">{menuVerisi[megaAcik].urun} ürün · {menuVerisi[megaAcik].markalar.length} marka</span>
                </div>
                <a href={`/kategori/${encodeURIComponent(megaAcik)}`} className="text-[12px] font-bold text-[var(--marka)] hover:underline">
                  Kategoriyi gör →
                </a>
              </div>
              <div className="mt-3 flex flex-wrap gap-1.5">
                {menuVerisi[megaAcik].markalar.map((m) => (
                  <a key={m.ad} href={`/marka/${encodeURIComponent(m.ad)}`}
                     className="flex items-center gap-1.5 rounded-full border border-black/10 px-3 py-1.5 text-[11.5px] font-bold text-black/60 transition hover:border-[var(--marka)] hover:bg-[var(--marka)] hover:text-white">
                    {m.ad}
                    <span className="text-black/30">{m.sayi}</span>
                  </a>
                ))}
              </div>
            </div>
          </div>
        )}
      </nav>

      <AuthModal acik={authModalAcik} kapat={() => setAuthModalAcik(false)} />
      <KaydirmaYardimcisi />
      <LiveChat />

      <div className="page-enter flex-1">{children}</div>

      <div className="px-5 pt-6 sm:px-10">
        <div className="mx-auto max-w-7xl">
          <KargoTakip />
        </div>
      </div>

      <footer className="mt-4 bg-[#334155] px-5 py-10 text-white sm:px-10">
        <div className="mx-auto max-w-7xl">
          <div className="flex flex-wrap gap-8 text-xs text-white/65">
            <div className="flex-1 min-w-[160px]">
              <div className="text-[10px] font-bold uppercase tracking-widest text-white/40 mb-3">Mağaza</div>
              <div className="flex flex-col gap-2">
                <a href="/" className="hover:text-white transition">Tüm Ürünler</a>
                <a href="/flas-indirimler" className="font-bold text-[#ffb14d] transition hover:text-[#ffc47a]">⚡ Flaş İndirimler</a>
                <a href="/outlet" className="font-bold text-[#ffb14d] transition hover:text-[#ffc47a]">🏭 Outlet</a>
                <a href="/cok-satanlar" className="hover:text-white transition">★ En Çok Satanlar</a>
                <a href="/son-kalan" className="hover:text-white transition">📏 Son Kalan Ölçüler</a>
                <a href="/son-firsat" className="hover:text-white transition">⏳ Son Fırsat</a>
                <a href="/sezon" className="hover:text-white transition">🗓️ Sezon Ürünleri</a>
                <a href="/sepet" className="hover:text-white transition">Sepetim</a>
                <a href="/siparislerim" className="hover:text-white transition">Siparişlerim</a>
              </div>
            </div>
            <div className="flex-1 min-w-[160px]">
              <div className="text-[10px] font-bold uppercase tracking-widest text-white/40 mb-3">Kurumsal</div>
              <div className="flex flex-col gap-2">
                <a href="/hakkinda" className="hover:text-white transition">Hakkında</a>
                <a href="/iletisim" className="hover:text-white transition">İletişim</a>
              </div>
            </div>
            <div className="flex-[2] min-w-[220px]">
              <div className="text-[10px] font-bold uppercase tracking-widest text-white/40 mb-3">Kampanyalardan Haberdar Ol</div>
              <div className="mb-5 max-w-[280px]"><BultenFormu /></div>
              <div className="text-[10px] font-bold uppercase tracking-widest text-white/40 mb-3">Bilgi</div>
              <div className="flex flex-col gap-2">
                <span>⛓️ CSPR ile kripto ödeme</span>
                <span>🔒 Token ile korunan güvenli API</span>
                <a href="/sozlesme/mesafeli-satis" className="hover:text-white transition">↩️ 14 gün koşulsuz iade — Mesafeli Satış Sözleşmesi</a>
              </div>
            </div>
            <div className="flex-1 min-w-[160px]">
              <div className="text-[10px] font-bold uppercase tracking-widest text-white/40 mb-3">Yasal</div>
              <div className="flex flex-col gap-2">
                <a href="/sozlesme/mesafeli-satis" className="hover:text-white transition">Mesafeli Satış Sözleşmesi</a>
                <a href="/sozlesme/uyelik" className="hover:text-white transition">Üyelik Sözleşmesi</a>
                <a href="/gizlilik" className="hover:text-white transition">KVKK Aydınlatma Metni</a>
                <a href="/cerez-politikasi" className="hover:text-white transition">Çerez Politikası</a>
              </div>
            </div>
          </div>
          <div className="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-6 text-xs text-white/40">
            <div className="flex items-center gap-3">
              <div className="flex items-center gap-2">
                {[
                  ["IG", "Instagram"],
                  ["X", "X (Twitter)"],
                  ["YT", "YouTube"],
                  ["f", "Facebook"],
                ].map(([kisa, ad]) => (
                  <a key={ad} href="#" aria-label={ad} title={ad}
                     className="flex h-8 w-8 items-center justify-center rounded-full border border-white/20 text-[11px] font-black text-white/50 transition hover:border-white hover:bg-white/10 hover:text-white">
                    {kisa}
                  </a>
                ))}
              </div>
              <span className="hidden sm:inline">© {new Date().getFullYear()} HepsiBirarada</span>
            </div>
            <div className="flex flex-wrap items-center gap-1.5">
              {["VISA", "Mastercard", "Troy", "⛓️ CSPR"].map((odeme) => (
                <span key={odeme} className="rounded-md border border-white/15 px-2 py-1 text-[10px] font-black tracking-wide text-white/50">
                  {odeme}
                </span>
              ))}
            </div>
            <span>Stok & sipariş: <strong className="font-semibold text-white/70">Ön Muhasebe API</strong> ile gerçek zamanlı.</span>
          </div>
        </div>
      </footer>

      <CerezOnayi />
    </div>
  );
}
