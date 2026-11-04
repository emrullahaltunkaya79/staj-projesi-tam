"use client";

import { useEffect, useState } from "react";
import { CheckCircle2, EyeOff, Trash2, Star } from "lucide-react";

type YorumKaydi = {
  id: number;
  urun: string;
  kullanici: string;
  rating: number;
  comment: string | null;
  gorsel: string | null;
  onayli: boolean;
  tarih: string;
};

export default function YorumModerasyonSayfasi() {
  const [yorumlar, setYorumlar] = useState<YorumKaydi[]>([]);
  const [yukleniyor, setYukleniyor] = useState(true);
  const [islemde, setIslemde] = useState<number | null>(null);

  const yukle = () => {
    fetch("/api/yorumlar")
      .then((r) => r.json())
      .then((d) => setYorumlar(d.data ?? []))
      .catch(() => {})
      .finally(() => setYukleniyor(false));
  };

  useEffect(yukle, []);

  const islem = async (id: number, tip: "onay" | "sil") => {
    setIslemde(id);
    try {
      await fetch(`/api/yorumlar/${id}`, { method: tip === "onay" ? "PATCH" : "DELETE" });
      setYorumlar((m) => (tip === "sil" ? m.filter((y) => y.id !== id) : m.map((y) => (y.id === id ? { ...y, onayli: !y.onayli } : y))));
    } finally {
      setIslemde(null);
    }
  };

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-black text-slate-900">Yorum Moderasyonu</h1>
        <p className="mt-1 text-sm text-slate-400">Ürün yorumlarını göster, gizle veya kalıcı sil.</p>
      </div>

      {yukleniyor ? (
        <p className="text-sm text-slate-400">Yükleniyor…</p>
      ) : yorumlar.length === 0 ? (
        <p className="text-sm text-slate-400">Henüz yorum yok.</p>
      ) : (
        <div className="space-y-3">
          {yorumlar.map((y) => (
            <div
              key={y.id}
              className={`rounded-2xl border bg-white p-4 ${y.onayli ? "border-slate-200" : "border-dashed border-slate-300 bg-slate-50 opacity-75"}`}
            >
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm font-bold text-slate-900">{y.kullanici}</span>
                    <span className="flex items-center gap-0.5 text-amber-400">
                      {[1, 2, 3, 4, 5].map((i) => (
                        <Star key={i} className={`h-3 w-3 ${i <= y.rating ? "fill-current" : "text-slate-300"}`} />
                      ))}
                    </span>
                    <span className="text-[11px] text-slate-400">{new Date(y.tarih).toLocaleDateString("tr-TR")}</span>
                    {!y.onayli && (
                      <span className="rounded bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-500">Gizli</span>
                    )}
                  </div>
                  <p className="mt-1 text-[11px] font-medium text-slate-400">{y.urun}</p>
                  {y.comment && <p className="mt-1.5 text-sm text-slate-600">{y.comment}</p>}
                  {y.gorsel && (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img
                      src={`/api/yorum-gorseli/${String(y.gorsel).split("/").pop()}`}
                      alt="Yorum fotoğrafı"
                      className="mt-2 h-20 w-28 rounded-lg border border-slate-200 object-cover"
                    />
                  )}
                </div>
                <div className="flex shrink-0 items-center gap-2">
                  <button
                    type="button"
                    onClick={() => islem(y.id, "onay")}
                    disabled={islemde === y.id}
                    className="flex items-center gap-1 rounded-full border px-3 py-1.5 text-[11px] font-bold transition disabled:opacity-50"
                    style={{ borderColor: y.onayli ? "#cbd5e1" : "#2bb673", color: y.onayli ? "#64748b" : "#2bb673" }}
                  >
                    {y.onayli ? <EyeOff className="h-3.5 w-3.5" /> : <CheckCircle2 className="h-3.5 w-3.5" />}
                    {y.onayli ? "Gizle" : "Göster"}
                  </button>
                  <button
                    type="button"
                    onClick={() => islem(y.id, "sil")}
                    disabled={islemde === y.id}
                    className="flex items-center gap-1 rounded-full border border-red-200 px-3 py-1.5 text-[11px] font-bold text-red-500 transition hover:bg-red-50 disabled:opacity-50"
                  >
                    <Trash2 className="h-3.5 w-3.5" /> Sil
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
