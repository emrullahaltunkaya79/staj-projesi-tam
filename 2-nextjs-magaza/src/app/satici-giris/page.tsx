"use client";

import { useState } from "react";
import Link from "next/link";
import { Lock } from "lucide-react";
import { AuthModal } from "@/components/AuthModal";
import { auth } from "@/components/AuthProvider";

/** Satıcı paneline yetkisiz erişimde gösterilir (proxy.ts /satici/* isteklerini buraya yönlendirir). */
export default function SaticiGirisSayfasi() {
  const { kullanici, girisYapmis, cikis } = auth();
  const [acik, setAcik] = useState(false);

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-100 p-4">
      <div className="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-lg">
        <span className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 text-white">
          <Lock className="h-6 w-6" />
        </span>
        <h1 className="mt-4 text-xl font-black text-slate-900">Satıcı Paneli</h1>
        {girisYapmis && kullanici ? (
          <>
            <p className="mt-2 text-sm text-slate-500">
              <b>{kullanici.email}</b> bir mağaza müşteri hesabı; satıcı paneline erişim yetkisi yok.
            </p>
            <button type="button" onClick={() => { cikis(); }}
                    className="mt-6 h-11 w-full rounded-full border border-slate-300 text-sm font-bold text-slate-700 transition hover:border-slate-900">
              Farklı hesapla giriş yap
            </button>
          </>
        ) : (
          <>
            <p className="mt-2 text-sm text-slate-400">Bu alan yetkilendirilmiş satıcılar içindir. Devam etmek için giriş yapın.</p>
            <button type="button" onClick={() => setAcik(true)}
                    className="mt-6 h-11 w-full rounded-full bg-slate-900 text-sm font-bold text-white transition hover:bg-slate-700 active:scale-95">
              Giriş Yap
            </button>
          </>
        )}
        <Link href="/" className="mt-3 block text-xs font-bold text-slate-400 underline underline-offset-4">← Mağazaya dön</Link>
      </div>
      <AuthModal acik={acik} kapat={() => { setAcik(false); window.location.reload(); }} />
    </div>
  );
}
