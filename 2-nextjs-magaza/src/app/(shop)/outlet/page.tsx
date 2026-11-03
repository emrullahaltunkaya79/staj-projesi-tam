import { urunler } from "@/lib/backoffice";
import { outletUrunler } from "@/lib/koleksiyonlar";
import { KatalogIcerik } from "@/components/KatalogIcerik";

export const dynamic = "force-dynamic";

export const metadata = { title: "Outlet — HepsiBirarada" };

export default async function OutletSayfasi() {
  const tumu = await urunler();
  return <KatalogIcerik tumUrunler={outletUrunler(tumu)} kategori={null} baslik="🏭 Outlet — %25 ve üzeri indirim" />;
}
