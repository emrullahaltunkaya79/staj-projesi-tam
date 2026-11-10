import { NextRequest, NextResponse } from "next/server";
import { cookies } from "next/headers";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";

/** Yorum "faydalı" oyu — müşterinin kendi oturumuyla (oy kişiye bağlıdır). */
export async function POST(request: NextRequest, ctx: { params: Promise<{ id: string }> }) {
  const { id } = await ctx.params;
  const token = (await cookies()).get("hba_oturum")?.value ?? request.headers.get("authorization")?.replace(/^Bearer\s+/i, "");

  if (!token) {
    return NextResponse.json({ hata: "Oy vermek için giriş yapın.", girisGerekli: true }, { status: 401 });
  }

  try {
    const yanit = await fetch(`${BASE}/reviews/${Number(id)}/faydali`, {
      method: "POST",
      headers: { Authorization: `Bearer ${token}`, Accept: "application/json" },
    });
    const sonuc = await yanit.json().catch(() => ({}));
    if (!yanit.ok) {
      return NextResponse.json({ hata: sonuc.message ?? "Oy kaydedilemedi." }, { status: yanit.status });
    }
    return NextResponse.json(sonuc);
  } catch {
    return NextResponse.json({ hata: "Arka ofise ulaşılamıyor." }, { status: 502 });
  }
}
