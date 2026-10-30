import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

/** Quick view için hafif ürün özeti — base64 görsel yerine proxy URL döner. */
export async function GET(_request: NextRequest, ctx: { params: Promise<{ id: string }> }) {
  const { id } = await ctx.params;

  try {
    const yanit = await fetch(`${BASE}/products/${id}`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      cache: "no-store",
    });

    if (!yanit.ok) {
      return NextResponse.json({ hata: "Ürün bulunamadı." }, { status: 404 });
    }

    const d = (await yanit.json()).data;
    const ozet = {
      id: d.id,
      name: d.name,
      category: d.category,
      sale_price: d.sale_price,
      discount_percent: d.discount_percent ?? null,
      final_price: d.final_price ?? d.sale_price,
      available_quantity: d.available_quantity,
      in_stock: d.in_stock,
      image_url: d.id ? `/api/img/${d.id}` : null,
      image_count: d.image_count ?? 1,
      variants: d.variants ?? [],
      rating_avg: d.rating_avg ?? null,
      review_count: d.review_count ?? 0,
    };

    return NextResponse.json({ data: ozet });
  } catch {
    return NextResponse.json({ hata: "Ürün alınamadı." }, { status: 502 });
  }
}
