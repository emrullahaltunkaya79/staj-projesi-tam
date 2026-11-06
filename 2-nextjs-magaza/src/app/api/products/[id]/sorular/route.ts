import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

type Baglam = { params: Promise<{ id: string }> };

/** Ürün soru-cevap: cevaplanmış sorular (GET) ve yeni soru (POST). */
export async function GET(_request: NextRequest, ctx: Baglam) {
  const { id } = await ctx.params;
  try {
    const yanit = await fetch(`${BASE}/products/${Number(id)}/sorular`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      cache: "no-store",
    });
    const sonuc = await yanit.json().catch(() => ({}));
    return NextResponse.json({ data: sonuc.data ?? [] });
  } catch {
    return NextResponse.json({ data: [] });
  }
}

export async function POST(request: NextRequest, ctx: Baglam) {
  const { id } = await ctx.params;
  const govde = await request.json().catch(() => ({}));
  try {
    const yanit = await fetch(`${BASE}/products/${Number(id)}/sorular`, {
      method: "POST",
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json", "Content-Type": "application/json" },
      body: JSON.stringify({ ad: govde.ad, email: govde.email, soru: govde.soru }),
    });
    const sonuc = await yanit.json().catch(() => ({}));
    if (!yanit.ok) {
      return NextResponse.json({ hata: sonuc.message ?? "Soru gönderilemedi." }, { status: yanit.status === 422 ? 422 : 502 });
    }
    return NextResponse.json({ message: sonuc.message }, { status: 201 });
  } catch {
    return NextResponse.json({ hata: "Arka ofise ulaşılamıyor." }, { status: 502 });
  }
}
