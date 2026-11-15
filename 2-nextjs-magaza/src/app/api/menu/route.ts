import { NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

const KDV_ORANI = 1.2;

/**
 * Mega menü verisi — kategori başına: ürün sayısı + marka dağılımı + öne çıkan ürün önizlemesi.
 * Hover'da ürün önizlemesi göstermek için her kategoriden en fazla 4 ürün döner.
 * 2 dakikada bir arka ofisten tazelenir; panel hover'da geç yüklenir.
 */
export async function GET() {
  try {
    const yanit = await fetch(`${BASE}/products`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      next: { revalidate: 120 },
    });

    if (!yanit.ok) return NextResponse.json({ data: [] });

    const urunler = (await yanit.json()).data ?? [];
    const ham: Record<
      string,
      { urun: number; markalar: Record<string, number>; adaylar: typeof urunler }
    > = {};

    for (const u of urunler) {
      const kategori = u.category ?? "Diğer";
      ham[kategori] ??= { urun: 0, markalar: {}, adaylar: [] };
      ham[kategori].urun += 1;
      if (u.brand) ham[kategori].markalar[u.brand] = (ham[kategori].markalar[u.brand] ?? 0) + 1;
      if (u.in_stock) ham[kategori].adaylar.push(u);
    }

    const oneCikar = (adaylar: typeof urunler) =>
      [...adaylar]
        .sort(
          (a, b) =>
            Number(Boolean(b.is_bestseller)) - Number(Boolean(a.is_bestseller)) ||
            (b.rating_avg ?? 0) - (a.rating_avg ?? 0),
        )
        .slice(0, 4)
        .map((u) => ({
          id: u.id,
          ad: u.name,
          fiyat: Math.round((u.final_price ?? u.sale_price) * KDV_ORANI * 100) / 100,
          stokta: Boolean(u.in_stock),
        }));

    const data = Object.fromEntries(
      Object.entries(ham).map(([ad, v]) => [
        ad,
        {
          urun: v.urun,
          markalar: Object.entries(v.markalar)
            .map(([mAd, sayi]) => ({ ad: mAd, sayi }))
            .sort((a, b) => b.sayi - a.sayi || a.ad.localeCompare(b.ad, "tr")),
          oneCikanlar: oneCikar(v.adaylar),
        },
      ]),
    );

    return NextResponse.json(
      { data },
      { headers: { "Cache-Control": "public, s-maxage=120, stale-while-revalidate=600" } },
    );
  } catch {
    return NextResponse.json({ data: [] });
  }
}
