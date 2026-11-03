import { urunler } from "@/lib/backoffice";
import { sezonUrunleri, mevsimBul, MEVSIMLER } from "@/lib/sezonlar";
import { KatalogIcerik } from "@/components/KatalogIcerik";

export const dynamic = "force-dynamic";

export async function generateMetadata() {
  const m = mevsimBul();
  return { title: `${MEVSIMLER[m].ad} Ürünleri — HepsiBirarada` };
}

export default async function SezonSayfasi() {
  const tumu = await urunler();
  const m = mevsimBul();
  return (
    <KatalogIcerik
      tumUrunler={sezonUrunleri(tumu)}
      kategori={null}
      baslik={`${MEVSIMLER[m].emoji} ${MEVSIMLER[m].ad} Ürünleri`}
    />
  );
}
