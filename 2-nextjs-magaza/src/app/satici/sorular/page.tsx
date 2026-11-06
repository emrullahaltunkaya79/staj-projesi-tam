"use client";

import { useEffect, useState } from "react";
import { Send, Trash2 } from "lucide-react";

type SoruKaydi = {
  id: number;
  ad: string;
  email: string;
  urun_adi: string;
  soru: string;
  cevap: string | null;
  created_at: string;
};

export default function SorularSayfasi() {
  const [sorular, setSorular] = useState<SoruKaydi[]>([]);
  const [cevaplar, setCevaplar] = useState<Record<number, string>>({});
  const [yukleniyor, setYukleniyor] = useState(true);

  const yukle = () => {
    fetch("/api/sorular")
      .then((r) => r.json())
      .then((d) => setSorular(d.data ?? []))
      .catch(() => {})
      .finally(() => setYukleniyor(false));
  };

  useEffect(() => { yukle(); }, []);

  const cevapla = async (id: number) => {
    const cevap = cevaplar[id]?.trim();
    if (!cevap) return;
    await fetch(`/api/sorular/${id}/cevap`, {
      method: "PATCH",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ cevap }),
    });
    yukle();
  };

  const sil = async (id: number) => {
    if (!window.confirm("Bu soruyu silmek istediğinize emin misiniz?")) return;
    await fetch(`/api/sorular/${id}`, { method: "DELETE" });
    yukle();
  };

  const bekleyen = sorular.filter((s) => !s.cevap);

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-black text-slate-900">Ürün Soruları</h1>
        <p className="mt-1 text-sm text-slate-400">
          Müşterilerin ürün sayfasından sorduğu sorular. Yanıtladığınızda cevap mağazada yayınlanır ve müşteriye e-posta gider.
          {sorular.length > 0 && <b className="text-slate-600"> {bekleyen.length} yanıt bekliyor.</b>}
        </p>
      </div>

      {yukleniyor ? (
        <p className="text-sm text-slate-400">Yükleniyor…</p>
      ) : sorular.length === 0 ? (
        <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-400">Henüz soru yok.</div>
      ) : (
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
          <div className="divide-y divide-slate-100">
            {sorular.map((s) => (
              <div key={s.id} className={`px-4 py-4 ${s.cevap ? "bg-slate-50/60" : ""}`}>
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <p className="text-sm font-bold text-slate-800">
                    {s.ad} <span className="ml-1 font-normal text-slate-400">· {s.email}</span>
                  </p>
                  <span className="text-[11px] text-slate-400">{new Date(s.created_at).toLocaleString("tr-TR")}</span>
                </div>
                <p className="mt-0.5 text-[11px] font-bold uppercase tracking-wide text-slate-400">{s.urun_adi}</p>
                <p className="mt-1.5 text-sm text-slate-700">{s.soru}</p>

                {s.cevap ? (
                  <p className="mt-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800"><b>Cevap:</b> {s.cevap}</p>
                ) : (
                  <div className="mt-2.5 flex gap-2">
                    <input value={cevaplar[s.id] ?? ""} onChange={(e) => setCevaplar((c) => ({ ...c, [s.id]: e.target.value }))}
                           placeholder="Cevabınız…"
                           className="h-9 flex-1 rounded-lg border border-slate-200 px-3 text-sm focus:border-slate-900 focus:outline-none" />
                    <button type="button" onClick={() => cevapla(s.id)} disabled={!cevaplar[s.id]?.trim()}
                            className="flex h-9 items-center gap-1.5 rounded-lg bg-slate-900 px-3 text-xs font-bold text-white transition hover:bg-slate-700 disabled:opacity-40">
                      <Send className="h-3.5 w-3.5" /> Yanıtla
                    </button>
                    <button type="button" onClick={() => sil(s.id)} aria-label="Sil"
                            className="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-400 transition hover:border-red-300 hover:text-red-600">
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
