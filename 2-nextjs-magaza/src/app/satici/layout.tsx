"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState, useEffect } from "react";
import {
  LayoutDashboard,
  Package,
  ShoppingCart,
  BarChart3,
  Store,
  Menu,
  X,
  Lock,
} from "lucide-react";
import { AuthModal } from "@/components/AuthModal";
import { authToken } from "@/components/AuthProvider";

const MENU = [
  { href: "/satici", label: "Dashboard", ikon: LayoutDashboard },
  { href: "/satici/urunler", label: "Ürünler", ikon: Package },
  { href: "/satici/siparisler", label: "Siparişler", ikon: ShoppingCart },
  { href: "/satici/raporlar", label: "Raporlar", ikon: BarChart3 },
];

export default function SaticiLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const [menuAcik, setMenuAcik] = useState(false);
  const [yetkili, setYetkili] = useState<boolean | null>(null);
  const [authModalAcik, setAuthModalAcik] = useState(false);

  // Yetki kontrolü: token var mı ve /auth/me geçerli mi
  useEffect(() => {
    const token = authToken();
    if (!token) {
      setYetkili(false);
      return;
    }
    fetch("/api/auth/me", { headers: { Authorization: `Bearer ${token}` } })
      .then((r) => {
        if (r.ok) {
          setYetkili(true);
        } else {
          setYetkili(false);
        }
      })
      .catch(() => setYetkili(false));
  }, []);

  // Yükleniyor
  if (yetkili === null) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-100">
        <div className="text-sm font-medium text-slate-400">Yetki kontrol ediliyor…</div>
      </div>
    );
  }

  // Yetkisiz
  if (!yetkili) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-100 p-4">
        <div className="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-lg">
          <span className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 text-white">
            <Lock className="h-6 w-6" />
          </span>
          <h2 className="mt-4 text-xl font-black text-slate-900">Satıcı Paneli</h2>
          <p className="mt-2 text-sm text-slate-400">
            Bu alan yetkilendirilmiş satıcılar içindir. Devam etmek için giriş yapın.
          </p>
          <button
            type="button"
            onClick={() => setAuthModalAcik(true)}
            className="mt-6 h-11 w-full rounded-full bg-slate-900 text-sm font-bold text-white transition hover:bg-slate-700 active:scale-95"
          >
            Giriş Yap
          </button>
          <Link href="/" className="mt-3 block text-xs font-bold text-slate-400 underline underline-offset-4">
            ← Mağazaya dön
          </Link>
        </div>
        <AuthModal acik={authModalAcik} kapat={() => {
          setAuthModalAcik(false);
          // Modal kapatinca tekrar kontrol et
          const token = authToken();
          if (token) {
            fetch("/api/auth/me", { headers: { Authorization: `Bearer ${token}` } })
              .then((r) => setYetkili(r.ok))
              .catch(() => {});
          }
        }} />
      </div>
    );
  }

  return (
    <div className="flex min-h-screen bg-slate-100">
      {/* Sidebar */}
      <aside
        className={`fixed inset-y-0 left-0 z-40 w-60 bg-slate-900 text-slate-300 transition-transform duration-300 lg:static lg:translate-x-0 ${
          menuAcik ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        <div className="flex h-16 items-center border-b border-slate-800 px-5">
          <span className="text-lg font-black text-white">HepsiBirarada</span>
          <span className="ml-2 rounded bg-indigo-600 px-2 py-0.5 text-[10px] font-bold text-white">SATICI</span>
        </div>
        <nav className="space-y-1 p-3">
          {MENU.map(({ href, label, ikon: Ikon }) => {
            const aktif = pathname === href;
            return (
              <Link
                key={href}
                href={href}
                onClick={() => setMenuAcik(false)}
                className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-[14px] font-medium transition ${
                  aktif
                    ? "bg-indigo-600 text-white"
                    : "text-slate-400 hover:bg-slate-800 hover:text-white"
                }`}
              >
                <Ikon className="h-[18px] w-[18px]" />
                {label}
              </Link>
            );
          })}
        </nav>
        <div className="mt-auto border-t border-slate-800 p-3">
          <Link
            href="/"
            className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-[14px] font-medium text-slate-500 transition hover:bg-slate-800 hover:text-white"
          >
            <Store className="h-[18px] w-[18px]" />
            Mağazaya Dön
          </Link>
        </div>
      </aside>

      {/* Mobil overlay */}
      {menuAcik && (
        <div onClick={() => setMenuAcik(false)} className="fixed inset-0 z-30 bg-black/50 lg:hidden" />
      )}

      {/* Content */}
      <div className="flex min-w-0 flex-1 flex-col">
        {/* Mobil header */}
        <div className="flex h-14 items-center justify-between border-b border-slate-200 bg-white px-4 lg:hidden">
          <button onClick={() => setMenuAcik(true)} aria-label="Menü"
                  className="rounded-lg p-2 text-slate-500 hover:bg-slate-100">
            <Menu className="h-5 w-5" />
          </button>
          <span className="text-sm font-black text-slate-900">Satıcı Paneli</span>
        </div>

        <main className="flex-1 p-6 lg:p-8">{children}</main>
      </div>
    </div>
  );
}
