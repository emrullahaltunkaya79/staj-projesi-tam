"use client";

import { useState, useEffect, useCallback } from "react";

const SLIDLAR = [
  {
    baslik: "İlk Siparişine %15 İndirim",
    alt: "ILKSIPARIS kodu ile 200₺ üzeri siparişlerde",
    cta: "Alışverişe Başla",
    link: "/kategori/Elektronik",
    gradient: "from-indigo-600 via-purple-600 to-pink-600",
    emoji: "🎁",
  },
  {
    baslik: "CSPR ile Öde, Anında Onayla",
    alt: "Kripto ödeme + otomatik fatura + aynı gün kargo",
    cta: "Nasıl Çalışır?",
    link: "/sss",
    gradient: "from-cyan-500 via-blue-600 to-indigo-700",
    emoji: "⛓️",
  },
  {
    baslik: "2.000₺ Üzeri Ücretsiz Kargo",
    alt: "Tüm Türkiye'ye hızlı teslimat + 14 gün koşulsuz iade",
    cta: "Ürünlere Göz At",
    link: "/kategori/Spor%20%26%20Outdoor",
    gradient: "from-emerald-500 via-teal-600 to-cyan-700",
    emoji: "🚚",
  },
];

export function HeroSlider() {
  const [aktif, setAktif] = useState(0);
  const [duraklat, setDuraklat] = useState(false);

  const ileri = useCallback(() => setAktif((p) => (p + 1) % SLIDLAR.length), []);

  useEffect(() => {
    if (duraklat) return;
    const t = setInterval(ileri, 4000);
    return () => clearInterval(t);
  }, [ileri, duraklat]);

  return (
    <div
      className="relative overflow-hidden rounded-2xl"
      onMouseEnter={() => setDuraklat(true)}
      onMouseLeave={() => setDuraklat(false)}
    >
      <div
        className="flex transition-transform duration-500 ease-out"
        style={{ transform: `translateX(-${aktif * 100}%)` }}
      >
        {SLIDLAR.map((s, i) => (
          <div
            key={i}
            className={`relative flex min-w-full flex-col items-start justify-center bg-gradient-to-br ${s.gradient} px-8 py-12 sm:px-12 sm:py-16`}
          >
            <div className="pointer-events-none absolute -right-8 -top-8 h-48 w-48 rounded-full bg-white/10" />
            <div className="pointer-events-none absolute -bottom-12 left-1/3 h-36 w-36 rounded-full bg-white/5" />
            <span className="text-4xl">{s.emoji}</span>
            <h2 className="relative mt-4 max-w-md text-2xl font-black leading-tight text-white sm:text-4xl">
              {s.baslik}
            </h2>
            <p className="relative mt-2 max-w-md text-sm text-white/70 sm:text-base">{s.alt}</p>
            <a
              href={s.link}
              className="relative mt-6 flex h-11 items-center rounded-xl bg-white px-6 text-sm font-bold text-slate-900 shadow-lg transition hover:bg-white/90 active:scale-95"
            >
              {s.cta} →
            </a>
          </div>
        ))}
      </div>

      {/* Nokta navigasyon */}
      <div className="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-1.5">
        {SLIDLAR.map((_, i) => (
          <button
            key={i}
            type="button"
            onClick={() => setAktif(i)}
            aria-label={`Slide ${i + 1}`}
            className={`h-2 rounded-full transition-all duration-300 ${
              i === aktif ? "w-6 bg-white" : "w-2 bg-white/30 hover:bg-white/50"
            }`}
          />
        ))}
      </div>

      {/* Ok butonları */}
      <button
        type="button"
        onClick={() => setAktif((p) => (p - 1 + SLIDLAR.length) % SLIDLAR.length)}
        aria-label="Önceki"
        className="absolute left-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/30"
      >
        ‹
      </button>
      <button
        type="button"
        onClick={ileri}
        aria-label="Sonraki"
        className="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/30"
      >
        ›
      </button>
    </div>
  );
}
