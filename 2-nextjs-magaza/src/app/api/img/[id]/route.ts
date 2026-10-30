import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

// Basit in-memory cache (dev'de yeterli) — ürün:indeks bazlı
const cache = new Map<string, { data: Buffer; type: string }>();

export async function GET(
  request: NextRequest,
  ctx: { params: Promise<{ id: string }> },
) {
  const { id } = await ctx.params;
  const productId = Number(id);
  // v=0 ana görsel, v=1.. ikincil görseller (galeri)
  const v = Number(request.nextUrl.searchParams.get("v") ?? "0") || 0;

  if (Number.isNaN(productId)) {
    return NextResponse.json({ hata: "Geçersiz ID" }, { status: 400 });
  }

  const anahtar = `${productId}:${v}`;
  const cached = cache.get(anahtar);
  if (cached) {
    return new NextResponse(new Uint8Array(cached.data), {
      headers: {
        "Content-Type": cached.type,
        "Cache-Control": "public, max-age=3600",
      },
    });
  }

  try {
    const r = await fetch(`${BASE}/products/${productId}/image/${v}`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "image/*" },
    });

    if (!r.ok) {
      // Ana görsel yoksa 1x1 şeffaf piksel; ikincil görsellerde 404
      if (v === 0) {
        const empty = Buffer.from(
          "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==",
          "base64",
        );
        return new NextResponse(new Uint8Array(empty), {
          headers: { "Content-Type": "image/png", "Cache-Control": "public, max-age=300" },
        });
      }
      return NextResponse.json({ hata: "Görsel bulunamadı" }, { status: 404 });
    }

    const type = r.headers.get("content-type") ?? "image/jpeg";
    const data = Buffer.from(await r.arrayBuffer());

    if (cache.size > 150) cache.clear();
    cache.set(anahtar, { data, type });

    return new NextResponse(new Uint8Array(data), {
      headers: {
        "Content-Type": type,
        "Cache-Control": "public, max-age=3600",
      },
    });
  } catch {
    return NextResponse.json({ hata: "Görsel alınamadı." }, { status: 502 });
  }
}
