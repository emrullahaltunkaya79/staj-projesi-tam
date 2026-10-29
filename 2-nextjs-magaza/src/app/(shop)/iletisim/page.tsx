"use client";

import { useState } from "react";

export default function Iletisim() {
  const [gonderildi, setGonderildi] = useState(false);
  const [ad, setAd] = useState("");
  const [eposta, setEposta] = useState("");
  const [mesaj, setMesaj] = useState("");

  const gonder = (e: React.FormEvent) => {
    e.preventDefault();
    setGonderildi(true);
  };

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-3xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[56px] font-black leading-none tracking-[-0.03em] sm:text-[84px]">İletişim</h1>
        </div>

        <div className="mt-10 grid grid-cols-1 gap-12 md:grid-cols-2">
          <div className="space-y-6 text-[14px] leading-relaxed text-black/70">
            <p>
              Sorularınız, önerileriniz veya toplu alım talepleriniz için bana yazın.
              Genellikle aynı gün içinde yanıtlıyorum.
            </p>
            <div className="space-y-3 border-t border-black/15 pt-5">
              <div>
                <p className="text-[10px] font-bold uppercase tracking-widest text-black/40">E-posta</p>
                <p className="mt-1 text-sm font-bold text-black">merhaba@hepsibirarada.test</p>
              </div>
              <div>
                <p className="text-[10px] font-bold uppercase tracking-widest text-black/40">Atölye</p>
                <p className="mt-1 text-sm text-black/60">Pittsburgh, PA — ziyaret randevusuyla</p>
              </div>
              <div>
                <p className="text-[10px] font-bold uppercase tracking-widest text-black/40">Kripto Ödeme</p>
                <p className="mt-1 text-sm text-black/60">Casper Network (CSPR) — QR ile anında</p>
              </div>
            </div>
          </div>

          {gonderildi ? (
            <div className="flex h-full min-h-[280px] flex-col items-center justify-center border border-black/15 bg-[#f8f8f8] text-center">
              <span className="text-4xl">✓</span>
              <p className="mt-4 text-sm font-bold text-black">Mesajınız alındı!</p>
              <p className="mt-1 text-xs text-black/50">En kısa sürede {eposta} adresine dönüş yapacağım.</p>
            </div>
          ) : (
            <form onSubmit={gonder} className="space-y-4">
              <div>
                <label className="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-black/40">Ad Soyad *</label>
                <input required value={ad} onChange={(e) => setAd(e.target.value)}
                       className="h-11 w-full border border-black/15 bg-white px-3.5 text-sm focus:border-black focus:outline-none" />
              </div>
              <div>
                <label className="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-black/40">E-posta *</label>
                <input required type="email" value={eposta} onChange={(e) => setEposta(e.target.value)}
                       className="h-11 w-full border border-black/15 bg-white px-3.5 text-sm focus:border-black focus:outline-none" />
              </div>
              <div>
                <label className="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-black/40">Mesajınız *</label>
                <textarea required rows={5} value={mesaj} onChange={(e) => setMesaj(e.target.value)}
                          className="w-full border border-black/15 bg-white px-3.5 py-2.5 text-sm focus:border-black focus:outline-none" />
              </div>
              <button type="submit"
                      className="h-11 w-full rounded-full bg-black text-sm font-bold text-white transition hover:bg-black/80 active:scale-95">
                Gönder
              </button>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
