import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

/** Arama kutusu canlı önerisi — ürün adı/marka eşleşmeleri (60 sn cache). */
export async function GET(request: NextRequest) {
  const q = (request.nextUrl.searchParams.get("q") ?? "").trim().toLocaleLowerCase("tr");
  if (q.length < 2) return NextResponse.json({ data: { urunler: [], markalar: [] } });

  try {
    const yanit = await fetch(`${BASE}/products`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      next: { revalidate: 60 },
    });
    const urunler = (await yanit.json()).data ?? [];

    const urunEslesen = urunler
      .filter(
        (u: { name: string; brand?: string }) =>
          u.name.toLocaleLowerCase("tr").includes(q) ||
          (u.brand ?? "").toLocaleLowerCase("tr").includes(q),
      )
      .slice(0, 6)
      .map((u: { id: number; name: string; brand?: string; final_price?: number; sale_price: number; image_url?: string }) => ({
        id: u.id,
        name: u.name,
        brand: u.brand ?? null,
        fiyat: u.final_price ?? u.sale_price,
        image: u.image_url ? `/api/img/${u.id}` : null,
      }));

    const markalar = [
      ...new Set(
        urunler
          .filter((u: { brand?: string }) => (u.brand ?? "").toLocaleLowerCase("tr").includes(q))
          .map((u: { brand?: string }) => u.brand),
      ),
    ].slice(0, 4);

    return NextResponse.json(
      { data: { urunler: urunEslesen, markalar } },
      { headers: { "Cache-Control": "public, s-maxage=60, stale-while-revalidate=300" } },
    );
  } catch {
    return NextResponse.json({ data: { urunler: [], markalar: [] } });
  }
}
