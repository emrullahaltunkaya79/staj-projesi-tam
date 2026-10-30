import { sellerSiparisler } from "@/lib/seller";
import { IadeOnayButonu } from "@/components/seller/IadeOnayButonu";

export const dynamic = "force-dynamic";
export const metadata = { title: "Siparişler — Satıcı Paneli" };

export default async function SaticiSiparisler() {
  const siparisler = await sellerSiparisler();
  const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { maximumFractionDigits: 0 }).format(v) + " ₺";
  const DURUM: Record<string, { label: string; renk: string }> = {
    pending: { label: "Bekliyor", renk: "bg-amber-50 text-amber-700" },
    paid: { label: "Ödendi", renk: "bg-emerald-50 text-emerald-700" },
    preparing: { label: "Hazırlanıyor", renk: "bg-indigo-50 text-indigo-700" },
    shipped: { label: "Kargoda", renk: "bg-sky-50 text-sky-700" },
    delivered: { label: "Teslim", renk: "bg-emerald-50 text-emerald-700" },
    return_requested: { label: "İade Talebi", renk: "bg-amber-100 text-amber-800" },
    returned: { label: "İade Edildi", renk: "bg-purple-50 text-purple-700" },
    cancelled: { label: "İptal", renk: "bg-red-50 text-red-600" },
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-black tracking-tight text-slate-900">Siparişler</h1>
        <p className="mt-1 text-sm text-slate-400">{siparisler.length} sipariş</p>
      </div>

      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[780px] text-left text-sm">
            <thead>
              <tr className="border-b border-slate-100 bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-400">
                <th className="px-5 py-3">Sipariş No</th>
                <th className="px-3 py-3">Müşteri</th>
                <th className="px-3 py-3">Fatura</th>
                <th className="px-3 py-3">Tarih</th>
                <th className="px-3 py-3">Durum</th>
                <th className="px-3 py-3 text-right">Tutar</th>
                <th className="px-5 py-3 text-right">İşlem</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50">
              {siparisler.map((s) => {
                const durum = DURUM[s.status] ?? { label: s.status, renk: "bg-slate-50 text-slate-500" };
                return (
                  <tr key={s.order_no} className={`transition hover:bg-slate-50 ${s.status === "return_requested" ? "bg-amber-50/40" : ""}`}>
                    <td className="px-5 py-3 font-mono font-bold text-slate-800">{s.order_no}</td>
                    <td className="px-3 py-3 text-slate-600">{s.customer_name ?? "—"}</td>
                    <td className="px-3 py-3 font-mono text-xs text-slate-400">{s.invoice_no ?? "—"}</td>
                    <td className="px-3 py-3 text-xs text-slate-500">
                      {new Date(s.created_at).toLocaleDateString("tr-TR", { day: "2-digit", month: "short", year: "numeric" })}
                    </td>
                    <td className="px-3 py-3">
                      <span className={`rounded-full px-2.5 py-1 text-[11px] font-bold ${durum.renk}`}>{durum.label}</span>
                    </td>
                    <td className="px-3 py-3 text-right font-black tabular-nums text-slate-900">{fmt(s.total)}</td>
                    <td className="px-5 py-3 text-right">
                      {s.status === "return_requested" && <IadeOnayButonu orderNo={s.order_no} />}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
