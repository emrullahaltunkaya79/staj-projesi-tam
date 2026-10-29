import { statsSeller } from "@/lib/seller";
import { BarChart3 } from "lucide-react";

export const dynamic = "force-dynamic";
export const metadata = { title: "Raporlar — Satıcı Paneli" };

export default async function SaticiRaporlar() {
  const data = await statsSeller();

  if (!data) {
    return <div className="py-20 text-center text-sm text-slate-400">Raporlar yüklenemedi.</div>;
  }

  const maxDeger = Math.max(...data.kategori_dagilimi.map((k) => k.stok_degeri), 1);
  const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { maximumFractionDigits: 0 }).format(v) + " ₺";

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-black tracking-tight text-slate-900">Raporlar</h1>
        <p className="mt-1 text-sm text-slate-400">Kategori bazlı stok dağılımı ve özet metrikler</p>
      </div>

      {/* Kategori dağılımı */}
      <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div className="mb-5 flex items-center gap-2 text-sm font-bold text-slate-800">
          <BarChart3 className="h-4 w-4" /> Kategori Bazlı Stok Değeri
        </div>
        <div className="space-y-4">
          {data.kategori_dagilimi.map((k) => (
            <div key={k.kategori}>
              <div className="mb-1 flex items-center justify-between text-sm">
                <span className="font-bold text-slate-700">{k.kategori}</span>
                <span className="text-slate-500">
                  {k.urun_sayisi} ürün · <strong className="text-slate-800">{fmt(k.stok_degeri)}</strong>
                </span>
              </div>
              <div className="h-3 overflow-hidden rounded-full bg-slate-100">
                <div
                  className="h-full rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-500"
                  style={{ width: `${Math.max((k.stok_degeri / maxDeger) * 100, 3)}%` }}
                />
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Özet tablo */}
      <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div className="mb-4 text-sm font-bold text-slate-800">Genel Özet</div>
        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
          {[
            ["Toplam Ciro", fmt(data.ozet.toplam_ciro)],
            ["Bu Ay Ciro", fmt(data.ozet.bu_ay_ciro)],
            ["Fatura Sayısı", String(data.ozet.fatura_sayisi)],
            ["Sipariş Sayısı", String(data.ozet.siparis_sayisi)],
            ["Ürün Çeşidi", String(data.ozet.urun_sayisi)],
            ["Stok Değeri", fmt(data.ozet.stok_degeri)],
            ["Kritik Stok", `${data.ozet.kritik_stok} ürün`],
            ["Kargolanacak", `${data.ozet.kargolanacak} sipariş`],
          ].map(([label, deger]) => (
            <div key={label} className="rounded-xl border border-slate-100 bg-slate-50 p-4">
              <p className="text-[10px] font-bold uppercase tracking-wide text-slate-400">{label}</p>
              <p className="mt-1 text-lg font-black tabular-nums text-slate-900">{deger}</p>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
