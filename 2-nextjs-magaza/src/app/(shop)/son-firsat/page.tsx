import { urunler } from "@/lib/backoffice";
import { sonFirsatUrunler } from "@/lib/koleksiyonlar";
import { KatalogIcerik } from "@/components/KatalogIcerik";

export const dynamic = "force-dynamic";

export const metadata = {
  title: "Son Fırsat — HepsiBirarada",
  description: "Tükenmek üzere olan son adetler — kaçmadan yakalayın, aynı gün kargo fırsatıyla.",
};

export default async function SonFirsatSayfasi() {
  const tumu = await urunler();
  return <KatalogIcerik tumUrunler={sonFirsatUrunler(tumu)} kategori={null} baslik="⏳ Son Fırsat — son adetler" />;
}
