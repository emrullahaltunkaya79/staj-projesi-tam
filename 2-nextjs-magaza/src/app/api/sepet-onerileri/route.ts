import { NextRequest, NextResponse } from "next/server";
import { urunler, ayniAldilar, indirimliFiyat, type Urun } from "@/lib/backoffice";

/**
 * Sepet çapraz satışı: sepetteki ürünlerle birlikte alınanlar (yedek: çok satanlar).
 * `acik` verilirse (ücretsiz kargoya kalan tutar, KDV hariç) bu açığı kapatan en uygun ürün öne alınır.
 */
export async function GET(request: NextRequest) {
  const idler = (request.nextUrl.searchParams.get("ids") ?? "")
    .split(",")
    .map(Number)
    .filter((n) => n > 0)
    .slice(0, 4);
  const acik = Math.max(0, Number(request.nextUrl.searchParams.get("acik")) || 0);
  if (idler.length === 0) return NextResponse.json({ data: [] });

  const [tumu, ...ortakListeleri] = await Promise.all([urunler(), ...idler.map((id) => ayniAldilar(id))]);
  const harita = new Map(tumu.map((u) => [u.id, u]));
  const sepette = new Set(idler);

  // Sık birlikte alınan önce gelir; listedeki sırası ağırlığı belirler
  const puan = new Map<number, number>();
  for (const liste of ortakListeleri) {
    liste.forEach((u, sira) => puan.set(u.id, (puan.get(u.id) ?? 0) + (10 - sira)));
  }
  for (const u of tumu) {
    if (u.is_bestseller && !puan.has(u.id)) puan.set(u.id, 1);
  }

  const adaylar = [...puan.entries()]
    .sort((a, b) => b[1] - a[1])
    .map(([id]) => harita.get(id))
    .filter((u): u is Urun => !!u && !sepette.has(u.id) && u.in_stock && (u.varyantlar?.length ?? 0) === 0);

  const kargoyuKapatan =
    acik > 0
      ? adaylar.filter((u) => indirimliFiyat(u) >= acik).sort((a, b) => indirimliFiyat(a) - indirimliFiyat(b))[0]
      : undefined;
  const sirali = kargoyuKapatan ? [kargoyuKapatan, ...adaylar.filter((u) => u.id !== kargoyuKapatan.id)] : adaylar;

  return NextResponse.json({
    data: sirali.slice(0, 4).map((u) => ({
      id: u.id,
      ad: u.name,
      marka: u.brand ?? null,
      fiyat: indirimliFiyat(u),
      gorsel: u.image_url,
      birim: u.unit,
      musait: u.available_quantity,
      kargoyuKapatir: u.id === kargoyuKapatan?.id,
    })),
  });
}
