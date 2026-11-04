import { urunler } from "@/lib/backoffice";
import { KatalogIcerik } from "@/components/KatalogIcerik";

export const dynamic = "force-dynamic";

export const metadata = {
  title: "Flaş İndirimler — HepsiBirarada",
  description: "Günlük flaş indirimler — %40'a kadar iskonto, bugün gece bitiyor. Kaçırma!",
};

export default async function FlasIndirimlerSayfasi() {
  const tumu = await urunler();
  const indirimli = tumu
    .filter((u) => (u.discount_percent ?? 0) > 0)
    .sort((a, b) => (b.discount_percent ?? 0) - (a.discount_percent ?? 0));

  return <KatalogIcerik tumUrunler={indirimli} kategori={null} baslik="⚡ Flaş İndirimler" />;
}
