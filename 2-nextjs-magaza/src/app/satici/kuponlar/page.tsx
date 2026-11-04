"use client";

import { useEffect, useState } from "react";
import { Trash2 } from "lucide-react";

type Kupon = {
  id: number;
  code: string;
  name: string;
  type: string;
  value: number;
  min_order_total: number;
  used_count: number;
  usage_limit: number | null;
  expires_at: string | null;
};

const TIP_ETIKETI: Record<string, string> = {
  percent: "% indirim",
  fixed: "₺ indirim",
  free_shipping: "Ücretsiz kargo",
};

export default function KuponlarSayfasi() {
  const [kuponlar, setKuponlar] = useState<Kupon[]>([]);
  const [yukleniyor, setYukleniyor] = useState(true);
  const [form, setForm] = useState({ code: "", name: "", type: "percent", value: "10", min_order_total: "0", expires_at: "" });
  const [mesaj, setMesaj] = useState<{ tur: "ok" | "hata"; metin: string } | null>(null);

  const yukle = () => {
    fetch("/api/kuponlar")
      .then((r) => r.json())
      .then((d) => setKuponlar(d.data ?? []))
      .catch(() => {})
      .finally(() => setYukleniyor(false));
  };

  useEffect(yukle, []);

  const olustur = async (e: React.FormEvent) => {
    e.preventDefault();
    setMesaj(null);
    try {
      const r = await fetch("/api/kuponlar", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          code: form.code.trim().toUpperCase(),
          name: form.name.trim() || `${form.code} kampanyası`,
          type: form.type,
          value: Number(form.value),
          min_order_total: Number(form.min_order_total || 0),
          expires_at: form.expires_at || null,
        }),
      });
      const d = await r.json();
      if (!r.ok) throw new Error(d.message ?? "Kupon oluşturulamadı.");
      setMesaj({ tur: "ok", metin: `${d.data?.code} oluşturuldu ✓` });
      setForm({ code: "", name: "", type: "percent", value: "10", min_order_total: "0", expires_at: "" });
      yukle();
    } catch (err) {
      setMesaj({ tur: "hata", metin: err instanceof Error ? err.message : "Hata oluştu." });
    }
  };

  const pasiflestir = async (id: number) => {
    await fetch(`/api/kuponlar/${id}`, { method: "DELETE" });
    yukle();
  };

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-black text-slate-900">Kuponlar</h1>
        <p className="mt-1 text-sm text-slate-400">Kampanya kuponlarını oluştur, listele ve devre dışı bırak.</p>
      </div>

      {/* Oluşturma formu */}
      <form onSubmit={olustur} className="rounded-2xl border border-slate-200 bg-white p-5">
        <p className="mb-3 text-[13px] font-black text-slate-900">Yeni Kupon</p>
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-6">
          <input required value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value })} placeholder="KOD"
                 className="h-10 col-span-2 sm:col-span-1 rounded-lg border border-slate-200 px-3 text-sm uppercase focus:border-slate-900 focus:outline-none" />
          <input required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="Kampanya adı"
                 className="h-10 col-span-2 rounded-lg border border-slate-200 px-3 text-sm focus:border-slate-900 focus:outline-none" />
          <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })}
                  className="h-10 rounded-lg border border-slate-200 px-2 text-sm focus:border-slate-900 focus:outline-none">
            <option value="percent">% İndirim</option>
            <option value="fixed">₺ İndirim</option>
            <option value="free_shipping">Ücretsiz Kargo</option>
          </select>
          <input required type="number" min="0" step="0.01" value={form.value} onChange={(e) => setForm({ ...form, value: e.target.value })} placeholder="Değer"
                 className="h-10 rounded-lg border border-slate-200 px-3 text-sm focus:border-slate-900 focus:outline-none" />
          <button type="submit" className="h-10 rounded-lg bg-slate-900 text-sm font-bold text-white transition hover:bg-slate-700">
            Oluştur
          </button>
        </div>
        {mesaj && (
          <p className={`mt-2 text-xs font-bold ${mesaj.tur === "ok" ? "text-emerald-600" : "text-red-600"}`}>{mesaj.metin}</p>
        )}
      </form>

      {/* Liste */}
      {yukleniyor ? (
        <p className="text-sm text-slate-400">Yükleniyor…</p>
      ) : kuponlar.length === 0 ? (
        <p className="text-sm text-slate-400">Henüz kupon yok.</p>
      ) : (
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-wide text-slate-400">
              <tr>
                <th className="px-4 py-2.5 font-bold">Kod</th>
                <th className="px-4 py-2.5 font-bold">Kampanya</th>
                <th className="px-4 py-2.5 font-bold">İndirim</th>
                <th className="px-4 py-2.5 font-bold">Kullanım</th>
                <th className="px-4 py-2.5 font-bold">Son Gün</th>
                <th className="px-4 py-2.5"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {kuponlar.map((k) => (
                <tr key={k.id}>
                  <td className="px-4 py-2.5 font-mono font-bold text-slate-800">{k.code}</td>
                  <td className="px-4 py-2.5 text-slate-600">{k.name}</td>
                  <td className="px-4 py-2.5 text-slate-600">
                    {k.type === "percent" ? `%${Number(k.value)}` : k.type === "fixed" ? `${Number(k.value)} ₺` : "Kargo"}
                  </td>
                  <td className="px-4 py-2.5 text-slate-500">{k.used_count} / {k.usage_limit ?? "∞"}</td>
                  <td className="px-4 py-2.5 text-slate-400">{k.expires_at ? new Date(k.expires_at).toLocaleDateString("tr-TR") : "—"}</td>
                  <td className="px-4 py-2.5 text-right">
                    <button
                      type="button"
                      onClick={() => pasiflestir(k.id)}
                      aria-label={`${k.code} pasifleştir`}
                      className="rounded-full p-1.5 text-slate-300 transition hover:bg-red-50 hover:text-red-500"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
