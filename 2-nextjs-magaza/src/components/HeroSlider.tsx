"use client";

import { useState, useEffect, useCallback } from "react";

const SLIDLAR = [
  {
    baslik: "İlk Siparişine %15 İndirim",
    alt: "ILKSIPARIS kodu ile 200₺ üzeri siparişlerde",
    cta: "Alışverişe Başla",
    link: "/kategori/Elektronik",
    emoji: "🎁",
  },
  {
    baslik: "CSPR ile Öde, Anında Onayla",
    alt: "Kripto ödeme + otomatik fatura + aynı gün kargo",
    cta: "Nasıl Çalışır?",
    link: "/sss",
    emoji: "⛓️",
  },
  {
    baslik: "2.000₺ Üzeri Ücretsiz Kargo",
    alt: "Tüm Türkiye'ye hızlı teslimat + 14 gün koşulsuz iade",
    cta: "Ürünlere Göz At",
    link: "/kategori/Spor%20%26%20Outdoor",
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
      className="relative overflow-hidden rounded-xl"
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
            className="relative flex min-w-full flex-col items-start gap-3 px-5 py-6 sm:flex-row sm:items-center sm:gap-5 sm:px-8 sm:py-9"
            style={{ background: "linear-gradient(100deg, var(--marka) 0%, #334155 85%)" }}
          >
            <div className="pointer-events-none absolute -right-6 -top-10 h-28 w-28 rounded-full bg-white/10" />
            <div className="pointer-events-none absolute -bottom-10 left-1/3 h-20 w-20 rounded-full bg-white/5" />
            <span className="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/15 text-2xl backdrop-blur">
              {s.emoji}
            </span>
            <div className="relative min-w-0 flex-1">
              <h2 className="truncate text-sm font-black leading-tight text-white sm:text-base">{s.baslik}</h2>
              <p className="mt-0.5 truncate text-[11px] text-white/70 sm:text-xs">{s.alt}</p>
            </div>
            <a
              href={s.link}
              className="relative hidden h-8 shrink-0 items-center rounded-lg bg-white px-4 text-xs font-bold text-slate-900 shadow transition hover:bg-white/90 active:scale-95 sm:flex"
            >
              {s.cta} →
            </a>
          </div>
        ))}
      </div>

      {/* Nokta navigasyon */}
      <div className="absolute bottom-1.5 left-1/2 flex -translate-x-1/2 gap-1.5">
        {SLIDLAR.map((_, i) => (
          <button
            key={i}
            type="button"
            onClick={() => setAktif(i)}
            aria-label={`Slide ${i + 1}`}
            className={`h-1.5 rounded-full transition-all duration-300 ${
              i === aktif ? "w-5 bg-white" : "w-1.5 bg-white/30 hover:bg-white/50"
            }`}
          />
        ))}
      </div>

      {/* Ok butonları */}
      <button
        type="button"
        onClick={() => setAktif((p) => (p - 1 + SLIDLAR.length) % SLIDLAR.length)}
        aria-label="Önceki"
        className="absolute left-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-white/20 text-sm text-white backdrop-blur transition hover:bg-white/30"
      >
        ‹
      </button>
      <button
        type="button"
        onClick={ileri}
        aria-label="Sonraki"
        className="absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-white/20 text-sm text-white backdrop-blur transition hover:bg-white/30"
      >
        ›
      </button>
    </div>
  );
}
