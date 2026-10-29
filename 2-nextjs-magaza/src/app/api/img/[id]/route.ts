import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

// Basit in-memory cache (dev'de yeterli)
const cache = new Map<number, { data: Buffer; type: string }>();

export async function GET(
  _request: NextRequest,
  ctx: { params: Promise<{ id: string }> },
) {
  const { id } = await ctx.params;
  const productId = Number(id);

  if (Number.isNaN(productId)) {
    return NextResponse.json({ hata: "Geçersiz ID" }, { status: 400 });
  }

  // Cache'den döner
  const cached = cache.get(productId);
  if (cached) {
    return new NextResponse(new Uint8Array(cached.data), {
      headers: {
        "Content-Type": cached.type,
        "Cache-Control": "public, max-age=3600",
      },
    });
  }

  try {
    const r = await fetch(`${BASE}/products/${productId}`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
    });

    if (!r.ok) {
      return NextResponse.json({ hata: "Ürün bulunamadı" }, { status: 404 });
    }

    const d = await r.json();
    const dataUri: string = d.data?.image_url ?? "";

    if (!dataUri.startsWith("data:")) {
      // Görsel yok — 1x1 şeffaf piktel
      const empty = Buffer.from(
        "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==",
        "base64",
      );
      return new NextResponse(new Uint8Array(empty), {
        headers: { "Content-Type": "image/png", "Cache-Control": "public, max-age=300" },
      });
    }

    // data:image/png;base64,xxxx → binary
    const match = dataUri.match(/^data:(image\/[^;]+);base64,(.*)$/);
    if (!match) {
      return NextResponse.json({ hata: "Geçersiz görsel" }, { status: 500 });
    }

    const type = match[1];
    const data = Buffer.from(match[2], "base64");

    // Cache'e yaz (maks 100 görsel)
    if (cache.size > 100) cache.clear();
    cache.set(productId, { data, type });

    return new NextResponse(new Uint8Array(data), {
      headers: {
        "Content-Type": type,
        "Cache-Control": "public, max-age=3600",
      },
    });
  } catch {
    return NextResponse.json({ hata: "Görsel alınamadı" }, { status: 502 });
  }
}
