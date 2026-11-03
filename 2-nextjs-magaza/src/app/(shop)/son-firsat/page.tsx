import { urunler } from "@/lib/backoffice";
import { sonFirsatUrunler } from "@/lib/koleksiyonlar";
import { KatalogIcerik } from "@/components/KatalogIcerik";

export const dynamic = "force-dynamic";

export const metadata = { title: "Son Fırsat — HepsiBirarada" };

export default async function SonFirsatSayfasi() {
  const tumu = await urunler();
  return <KatalogIcerik tumUrunler={sonFirsatUrunler(tumu)} kategori={null} baslik="⏳ Son Fırsat — son adetler" />;
}
