"use client";

import { useState, useEffect } from "react";
import { Star, Loader2, MessageSquare } from "lucide-react";

type Yorum = {
  id: number;
  user_name: string;
  rating: number;
  comment: string | null;
  created_at: string;
};

export function Yorumlar({ urunId }: { urunId: number }) {
  const [yorumlar, setYorumlar] = useState<Yorum[]>([]);
  const [ortalam, setOrtalam] = useState(0);
  const [sayi, setSayi] = useState(0);
  const [puan, setPuan] = useState(5);
  const [mesaj, setMesaj] = useState("");
  const [gonderildi, setGonderildi] = useState(false);
  const [yukleniyor, setYukleniyor] = useState(false);

  useEffect(() => {
    fetch(`/api/products/${urunId}/reviews`)
      .then((r) => r.json())
      .then((d) => {
        setYorumlar(d.data?.yorumlar ?? []);
        setOrtalam(d.data?.ortalam_puan ?? 0);
        setSayi(d.data?.yorum_sayisi ?? 0);
      })
      .catch(() => {});
  }, [urunId]);

  const gonder = async (e: React.FormEvent) => {
    e.preventDefault();
    setYukleniyor(true);
    try {
      const r = await fetch(`/api/products/${urunId}/reviews`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ rating: puan, comment: mesaj || null }),
      });
      if (r.ok) {
        setGonderildi(true);
        setMesaj("");
        const d = await fetch(`/api/products/${urunId}/reviews`).then((r) => r.json());
        setYorumlar(d.data?.yorumlar ?? []);
        setOrtalam(d.data?.ortalam_puan ?? 0);
        setSayi(d.data?.yorum_sayisi ?? 0);
      }
    } finally {
      setYukleniyor(false);
    }
  };

  return (
    <section className="px-5 pb-24 pt-16 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="flex items-end justify-between border-b border-black/20 pb-5">
          <h2 className="text-[32px] font-black leading-none tracking-[-0.02em] sm:text-[48px]">
            Değerlendirmeler
          </h2>
          {sayi > 0 && (
            <div className="flex items-center gap-2 pb-1">
              <div className="flex text-amber-400">
                {[1, 2, 3, 4, 5].map((i) => (
                  <Star key={i} className={`h-4 w-4 ${i <= Math.round(ortalam) ? "fill-current" : "text-black/10"}`} />
                ))}
              </div>
              <span className="text-lg font-black">{ortalam.toFixed(1)}</span>
              <span className="text-sm text-black/40">({sayi})</span>
            </div>
          )}
        </div>

        <div className="mt-8 grid grid-cols-1 gap-10 lg:grid-cols-3">
          {/* Yorum formu */}
          <div className="h-fit border border-black/10 bg-[#f8f8f8] p-5">
            <h3 className="flex items-center gap-2 text-sm font-bold text-black">
              <MessageSquare className="h-4 w-4" /> Değerlendirme Yaz
            </h3>

            {gonderildi ? (
              <p className="mt-4 text-sm font-bold text-emerald-600">✓ Teşekkürler! Değerlendirmeniz kaydedildi.</p>
            ) : (
              <form onSubmit={gonder} className="mt-4 space-y-3">
                <div>
                  <label className="mb-1 block text-[10px] font-bold uppercase tracking-widest text-black/40">Puanınız</label>
                  <div className="flex gap-1">
                    {[1, 2, 3, 4, 5].map((i) => (
                      <button key={i} type="button" onClick={() => setPuan(i)}
                              className="transition hover:scale-110">
                        <Star className={`h-6 w-6 ${i <= puan ? "fill-amber-400 text-amber-400" : "text-black/15"}`} />
                      </button>
                    ))}
                  </div>
                </div>
                <div>
                  <label className="mb-1 block text-[10px] font-bold uppercase tracking-widest text-black/40">Yorumunuz</label>
                  <textarea value={mesaj} onChange={(e) => setMesaj(e.target.value)} rows={3}
                            placeholder="Ürün hakkındaki düşünceniz…"
                            className="w-full border border-black/15 bg-white px-3 py-2 text-sm focus:border-black focus:outline-none" />
                </div>
                <button type="submit" disabled={yukleniyor}
                        className="h-10 w-full rounded-full bg-black text-xs font-bold text-white transition hover:bg-black/80 disabled:opacity-50">
                  {yukleniyor ? <Loader2 className="mx-auto h-4 w-4 animate-spin" /> : "Gönder"}
                </button>
              </form>
            )}
          </div>

          {/* Yorum listesi */}
          <div className="lg:col-span-2">
            {yorumlar.length === 0 ? (
              <p className="py-12 text-center text-sm text-black/40">Henüz değerlendirme yok. İlk yazan siz olun!</p>
            ) : (
              <div className="divide-y divide-black/10">
                {yorumlar.map((y) => (
                  <div key={y.id} className="py-4">
                    <div className="flex items-center gap-3">
                      <span className="flex h-8 w-8 items-center justify-center rounded-full bg-black/5 text-sm font-black text-black/50">
                        {y.user_name.charAt(0).toUpperCase()}
                      </span>
                      <div>
                        <p className="text-sm font-bold text-black">{y.user_name}</p>
                        <div className="flex items-center gap-1 text-amber-400">
                          {[1, 2, 3, 4, 5].map((i) => (
                            <Star key={i} className={`h-3 w-3 ${i <= y.rating ? "fill-current" : "text-black/10"}`} />
                          ))}
                          <span className="ml-1 text-xs text-black/30">
                            {new Date(y.created_at).toLocaleDateString("tr-TR", { day: "numeric", month: "short" })}
                          </span>
                        </div>
                      </div>
                    </div>
                    {y.comment && <p className="mt-2 pl-11 text-sm leading-relaxed text-black/70">{y.comment}</p>}
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}
