import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
// BASE "http://127.0.0.1:8000/api/v1" → depo kökü "http://127.0.0.1:8000"
const KOK = BASE.replace(/\/api\/v1$/, "");

/** Yorum fotoğrafları: backoffice public storage'ından akıtılır. */
export async function GET(_request: NextRequest, ctx: { params: Promise<{ dosya: string }> }) {
  const { dosya } = await ctx.params;
  if (!/^[a-zA-Z0-9._-]+$/.test(dosya)) {
    return NextResponse.json({ hata: "Geçersiz dosya adı" }, { status: 400 });
  }

  try {
    const r = await fetch(`${KOK}/storage/reviews/${dosya}`);
    if (!r.ok) return NextResponse.json({ hata: "Görsel bulunamadı" }, { status: 404 });
    const tip = r.headers.get("content-type") ?? "image/jpeg";
    const tampon = Buffer.from(await r.arrayBuffer());
    return new NextResponse(new Uint8Array(tampon), {
      headers: { "Content-Type": tip, "Cache-Control": "public, max-age=86400" },
    });
  } catch {
    return NextResponse.json({ hata: "Görsel alınamadı" }, { status: 502 });
  }
}
