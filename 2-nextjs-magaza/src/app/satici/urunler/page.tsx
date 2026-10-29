import { sellerUrunler } from "@/lib/seller";
import { UrunYonetim } from "@/components/seller/UrunYonetim";

export const dynamic = "force-dynamic";
export const metadata = { title: "Ürünler — Satıcı Paneli" };

export default async function SaticiUrunler() {
  const urunler = await sellerUrunler();
  const kategoriler = [...new Set(urunler.map((u) => u.category).filter(Boolean))] as string[];

  return <UrunYonetim urunler={urunler} kategoriler={kategoriler} />;
}
