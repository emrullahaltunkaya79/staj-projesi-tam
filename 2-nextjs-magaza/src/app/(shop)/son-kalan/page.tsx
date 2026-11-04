import { urunler } from "@/lib/backoffice";
import { sonKalanUrunler } from "@/lib/koleksiyonlar";
import { KatalogIcerik } from "@/components/KatalogIcerik";

export const dynamic = "force-dynamic";

export const metadata = {
  title: "Son Kalan Ölçüler — HepsiBirarada",
  description: "Son kalan beden ve ölçüler — tükenmeden sipariş verin, 14 gün koşulsuz iade garantisiyle.",
};

export default async function SonKalanSayfasi() {
  const tumu = await urunler();
  return <KatalogIcerik tumUrunler={sonKalanUrunler(tumu)} kategori={null} baslik="📏 Son Kalan Ölçüler" />;
}
