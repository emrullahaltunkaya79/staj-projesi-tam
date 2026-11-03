import { NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

/**
 * Mega menü verisi — kategori başına ürün sayısı + marka dağılımı (kompakt).
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
    const ham: Record<string, { urun: number; markalar: Record<string, number> }> = {};

    for (const u of urunler) {
      const kategori = u.category ?? "Diğer";
      ham[kategori] ??= { urun: 0, markalar: {} };
      ham[kategori].urun += 1;
      if (u.brand) ham[kategori].markalar[u.brand] = (ham[kategori].markalar[u.brand] ?? 0) + 1;
    }

    const data = Object.fromEntries(
      Object.entries(ham).map(([ad, v]) => [
        ad,
        {
          urun: v.urun,
          markalar: Object.entries(v.markalar)
            .map(([mAd, sayi]) => ({ ad: mAd, sayi }))
            .sort((a, b) => b.sayi - a.sayi || a.ad.localeCompare(b.ad, "tr")),
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
