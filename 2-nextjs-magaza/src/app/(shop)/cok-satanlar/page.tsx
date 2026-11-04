import { urunler } from "@/lib/backoffice";
import { KatalogIcerik } from "@/components/KatalogIcerik";

export const dynamic = "force-dynamic";

export const metadata = {
  title: "En Çok Satanlar — HepsiBirarada",
  description: "Gerçek satış verilerine göre en çok tercih edilen ürünler — müşteri puanları ve anlık stok ile.",
};

export default async function CokSatanlarSayfasi() {
  const tumu = await urunler();
  const cokSatan = tumu.filter((u) => u.is_bestseller);

  return <KatalogIcerik tumUrunler={cokSatan} kategori={null} baslik="★ En Çok Satanlar" />;
}
