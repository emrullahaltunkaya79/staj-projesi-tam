import { urunler } from "@/lib/backoffice";
import { notFound } from "next/navigation";
import { KatalogIcerik } from "@/components/KatalogIcerik";

export const dynamic = "force-dynamic";

export async function generateMetadata({ params }: { params: Promise<{ ad: string }> }) {
  const { ad } = await params;
  return { title: `${decodeURIComponent(ad)} — HepsiBirarada` };
}

export default async function KategoriSayfasi({ params }: { params: Promise<{ ad: string }> }) {
  const { ad } = await params;
  const kategori = decodeURIComponent(ad);
  const tumu = await urunler();

  if (!tumu.some((u) => u.category === kategori)) notFound();

  return <KatalogIcerik tumUrunler={tumu} kategori={kategori} baslik={kategori} />;
}
