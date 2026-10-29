import { statsSeller } from "@/lib/seller";
import { OzetKartlari, SonSiparisler, KritikStok } from "@/components/seller/Dashboard";

export const dynamic = "force-dynamic";
export const metadata = { title: "Satıcı Paneli — HepsiBirarada" };

export default async function SaticiDashboard() {
  const data = await statsSeller();

  if (!data) {
    return <div className="py-20 text-center text-sm text-slate-400">İstatistikler yüklenemedi.</div>;
  }

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-black tracking-tight text-slate-900">Dashboard</h1>
        <p className="mt-1 text-sm text-slate-400">Mağazanızın güncel durumu</p>
      </div>

      <OzetKartlari ozet={data.ozet} />
      <div className="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <SonSiparisler siparisler={data.son_siparisler} />
        <KritikStok urunler={data.kritik_urunler} />
      </div>
    </div>
  );
}
