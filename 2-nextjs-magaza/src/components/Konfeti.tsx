"use client";

import { useEffect } from "react";

const RENKLER = [
  "#4f46e5", "#1e3a8a", "#7f1d1d", "#065f46",
  "#111827", "#64748b", "#312e81", "#0f766e",
];

export function Konfeti({ aktif }: { aktif: boolean }) {
  useEffect(() => {
    if (!aktif) return;

    const parcalar: HTMLElement[] = [];
    const sayi = 60;

    for (let i = 0; i < sayi; i++) {
      const el = document.createElement("div");
      el.className = "confetti-piece";
      el.style.left = `${Math.random() * 100}vw`;
      el.style.backgroundColor = RENKLER[i % RENKLER.length];
      el.style.animationDuration = `${2 + Math.random() * 2}s`;
      el.style.animationDelay = `${Math.random() * 0.5}s`;
      el.style.width = `${6 + Math.random() * 4}px`;
      el.style.height = `${6 + Math.random() * 6}px`;
      el.style.borderRadius = Math.random() > 0.5 ? "50%" : "2px";
      document.body.appendChild(el);
      parcalar.push(el);
    }

    return () => parcalar.forEach((p) => p.remove());
  }, [aktif]);

  return null;
}
