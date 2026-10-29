import { TrendingUp, ShoppingBag, Package, AlertTriangle } from "lucide-react";

const fmt = (v: number) =>
  new Intl.NumberFormat("tr-TR", { maximumFractionDigits: 0 }).format(v) + " ₺";

export function OzetKartlari({ ozet }: { ozet: SellerStats["ozet"] }) {
  const kartlar = [
    { ikon: TrendingUp, label: "Toplam Ciro", deger: fmt(ozet.toplam_ciro), alt: `Bu ay ${fmt(ozet.bu_ay_ciro)}`, renk: "text-emerald-600 bg-emerald-50" },
    { ikon: ShoppingBag, label: "Siparişler", deger: String(ozet.siparis_sayisi), alt: `${ozet.kargolanacak} kargolanacak · ${ozet.bekleyen_siparis} bekliyor`, renk: "text-indigo-600 bg-indigo-50" },
    { ikon: Package, label: "Ürünler", deger: String(ozet.urun_sayisi), alt: `Stok değeri ${fmt(ozet.stok_degeri)}`, renk: "text-violet-600 bg-violet-50" },
    { ikon: AlertTriangle, label: "Kritik Stok", deger: String(ozet.kritik_stok), alt: `${ozet.tukenen} ürün tükendi`, renk: "text-amber-600 bg-amber-50" },
  ];

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
      {kartlar.map(({ ikon: Ikon, label, deger, alt, renk }) => (
        <div key={label} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div className="flex items-start justify-between">
            <div>
              <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p>
              <p className="mt-2 text-2xl font-black tabular-nums text-slate-900">{deger}</p>
              <p className="mt-1 text-xs text-slate-400">{alt}</p>
            </div>
            <span className={`flex h-11 w-11 items-center justify-center rounded-xl ${renk}`}>
              <Ikon className="h-5 w-5" />
            </span>
          </div>
        </div>
      ))}
    </div>
  );
}

export function SonSiparisler({ siparisler }: { siparisler: SellerStats["son_siparisler"] }) {
  const fmtT = (v: number) => new Intl.NumberFormat("tr-TR", { maximumFractionDigits: 0 }).format(v) + " ₺";
  const DURUM: Record<string, string> = {
    pending: "bg-amber-50 text-amber-700",
    paid: "bg-emerald-50 text-emerald-700",
    preparing: "bg-indigo-50 text-indigo-700",
    shipped: "bg-sky-50 text-sky-700",
    delivered: "bg-emerald-50 text-emerald-700",
    cancelled: "bg-red-50 text-red-600",
  };

  return (
    <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div className="border-b border-slate-100 px-5 py-4 text-sm font-bold text-slate-800">Son Siparişler</div>
      <div className="divide-y divide-slate-50">
        {siparisler.length === 0 && <p className="px-5 py-8 text-center text-sm text-slate-400">Henüz sipariş yok.</p>}
        {siparisler.map((s) => (
          <div key={s.order_no} className="flex items-center gap-3 px-5 py-3">
            <div className="min-w-0 flex-1">
              <p className="font-mono text-[13px] font-bold text-slate-700">{s.order_no}</p>
              <p className="text-xs text-slate-400">{s.customer ?? "—"}</p>
            </div>
            <span className={`shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-bold ${DURUM[s.status] ?? "bg-slate-50 text-slate-500"}`}>
              {s.status_label}
            </span>
            <span className="shrink-0 text-sm font-black tabular-nums text-slate-800">{fmtT(s.total)}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

export function KritikStok({ urunler }: { urunler: SellerStats["kritik_urunler"] }) {
  return (
    <div className="rounded-2xl border border-amber-200 bg-white shadow-sm">
      <div className="flex items-center gap-2 border-b border-amber-100 bg-amber-50 px-5 py-4 text-sm font-bold text-amber-800">
        <AlertTriangle className="h-4 w-4" /> Kritik Stok
        <span className="ml-auto rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">
          {urunler.length} ürün
        </span>
      </div>
      <div className="divide-y divide-amber-50">
        {urunler.length === 0 && <p className="px-5 py-8 text-center text-sm text-slate-400">Kritik seviyede ürün yok 👍</p>}
        {urunler.map((u) => (
          <div key={u.id} className="flex items-center justify-between gap-3 px-5 py-3">
            <div className="min-w-0 flex-1">
              <p className="text-[13px] font-bold text-slate-700">{u.name}</p>
              <p className="text-xs text-slate-400">{u.category}</p>
            </div>
            <span className={`shrink-0 rounded-full px-3 py-1 text-xs font-black tabular-nums ${
              u.stock_quantity === 0 ? "bg-red-50 text-red-600" : "bg-amber-50 text-amber-700"
            }`}>
              {u.stock_quantity} adet
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}

import type { SellerStats } from "@/lib/seller";
