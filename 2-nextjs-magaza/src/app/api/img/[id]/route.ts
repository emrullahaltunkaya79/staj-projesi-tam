import { NextRequest, NextResponse } from "next/server";
import { onbellegeYaz, onbellektenGetir } from "@/lib/imgOnbellek";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export async function GET(
  request: NextRequest,
  ctx: { params: Promise<{ id: string }> },
) {
  const { id } = await ctx.params;
  const productId = Number(id);
  // v=0 ana görsel, v=1.. ikincil görseller (galeri); s=görsel sürümü —
  // panelde görsel değişince sürüm değişir ve tarayıcı önbelleği kırılır
  // (sunucu tarafı tazeleme webhook ile gorselOnbellekTemizle üzerinden yapılır)
  const v = Number(request.nextUrl.searchParams.get("v") ?? "0") || 0;

  if (Number.isNaN(productId)) {
    return NextResponse.json({ hata: "Geçersiz ID" }, { status: 400 });
  }

  const anahtar = `${productId}:${v}`;
  const kayitli = onbellektenGetir(anahtar);
  if (kayitli) {
    return new NextResponse(new Uint8Array(kayitli.data), {
      headers: {
        "Content-Type": kayitli.type,
        "Cache-Control": "public, max-age=600, must-revalidate",
      },
    });
  }

  try {
    const r = await fetch(`${BASE}/products/${productId}/image/${v}`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "image/*" },
      // İkili görseli Next veri önbelleğine DEĞİL yalnız kendi ikili önbelleğimize alırız;
      // aksi halde silinen/değişen görsel Next'in fetch cache'inden bayat servis edilir
      cache: "no-store",
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
    onbellegeYaz(anahtar, data, type);

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
