import { urunler } from "@/lib/backoffice";
import { KatalogIcerik } from "@/components/KatalogIcerik";
import { notFound } from "next/navigation";

export const dynamic = "force-dynamic";

export async function generateMetadata({ params }: { params: Promise<{ ad: string }> }) {
  const { ad } = await params;
  return { title: `${decodeURIComponent(ad)} Ürünleri — HepsiBirarada`, description: `${decodeURIComponent(ad)} markasının orijinal ürünleri — aynı gün kargo, 14 gün koşulsuz iade.` };
}

export default async function MarkaSayfasi({ params }: { params: Promise<{ ad: string }> }) {
  const { ad } = await params;
  const marka = decodeURIComponent(ad);
  const tumu = await urunler();

  if (!tumu.some((u) => u.brand === marka)) notFound();

  return <KatalogIcerik tumUrunler={tumu} kategori={null} marka={marka} baslik={`${marka} Ürünleri`} />;
}
