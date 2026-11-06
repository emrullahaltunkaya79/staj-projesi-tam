import { NextRequest, NextResponse } from "next/server";
import { kdvDahil } from "@/lib/fiyat";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

/** Arama kutusu canlı önerisi — ürün, marka ve kategori eşleşmeleri (60 sn cache). */
export async function GET(request: NextRequest) {
  const q = (request.nextUrl.searchParams.get("q") ?? "").trim();
  if (q.length < 2) return NextResponse.json({ data: { urunler: [], markalar: [], kategoriler: {} } });

  try {
    const yanit = await fetch(
      `${BASE}/products/arama?q=${encodeURIComponent(q)}&per_page=6`,
      {
        headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
        next: { revalidate: 60 },
      },
    );
    const d = await yanit.json().catch(() => ({}));

    const urunler = (d.data ?? []).map(
      (u: { id: number; name: string; brand?: string; final_price?: number; sale_price: number; image_url?: string }) => ({
        id: u.id,
        name: u.name,
        brand: u.brand ?? null,
        fiyat: kdvDahil(u.final_price ?? u.sale_price),
        image: u.image_url ? `/api/img/${u.id}` : null,
      }),
    );

    const markalar = d.meta?.markalar ? Object.keys(d.meta.markalar).slice(0, 4) : [];
    const kategoriler = Object.fromEntries(
      Object.entries((d.meta?.kategoriler ?? {}) as Record<string, number>).slice(0, 4),
    );

    return NextResponse.json(
      { data: { urunler, markalar, kategoriler } },
      { headers: { "Cache-Control": "public, s-maxage=60, stale-while-revalidate=300" } },
    );
  } catch {
    return NextResponse.json({ data: { urunler: [], markalar: [], kategoriler: {} } });
  }
}
