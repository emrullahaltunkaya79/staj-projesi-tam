import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

// İç bellek önbelleği — ürün:indeks bazlı, 10 dakika ömürlü (görsel değişince en geç
// 10 dk içinde yansır; tarayıcı tarafında da 10 dk sonra yeniden doğrulanır).
// Katalog sayfaları 100+ görsel istediğinden 150 girdide tüm haritayı silmek yerine
// en eski girdiler budanır — toplu temizleme önbellek boşalmasına yol açıyordu.
const cache = new Map<string, { data: Buffer; type: string; zaman: number }>();
const ONBELLEK_SURESI = 10 * 60 * 1000;
const ONBELLEK_KAPASITESI = 500;

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
  const kayitli = cache.get(anahtar);
  if (kayitli && Date.now() - kayitli.zaman < ONBELLEK_SURESI) {
    return new NextResponse(new Uint8Array(kayitli.data), {
      headers: {
        "Content-Type": kayitli.type,
        "Cache-Control": "public, max-age=600, must-revalidate",
      },
    });
  }
  cache.delete(anahtar);

  try {
    const r = await fetch(`${BASE}/products/${productId}/image/${v}`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "image/*" },
    });

    if (!r.ok) {
      // Ana görsel yoksa 1x1 şeffaf piksel — KESİNLIKE önbelleğe alınmaz (tarayıcıya
      // da yalnız 60 sn verilir ki görsel yüklenince hemen görünsün)
      if (v === 0) {
        const empty = Buffer.from(
          "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==",
          "base64",
        );
        return new NextResponse(new Uint8Array(empty), {
          headers: { "Content-Type": "image/png", "Cache-Control": "public, max-age=60" },
        });
      }
      return NextResponse.json({ hata: "Görsel bulunamadı" }, { status: 404 });
    }

    const type = r.headers.get("content-type") ?? "image/jpeg";
    const data = Buffer.from(await r.arrayBuffer());

    if (cache.size >= ONBELLEK_KAPASITESI) {
      // En eski dörtte birini budur — tüm haritayı boşaltmak her seferinde
      // backoffice'e görsel patlaması yaratıyordu
      const silinecek = [...cache.entries()]
        .sort((a, b) => a[1].zaman - b[1].zaman)
        .slice(0, Math.ceil(ONBELLEK_KAPASITESI / 4));
      for (const [anahtar] of silinecek) cache.delete(anahtar);
    }
    cache.set(anahtar, { data, type, zaman: Date.now() });

    return new NextResponse(new Uint8Array(data), {
      headers: {
        "Content-Type": type,
        "Cache-Control": "public, max-age=600, must-revalidate",
      },
    });
  } catch {
    return NextResponse.json({ hata: "Görsel alınamadı." }, { status: 502 });
  }
}
