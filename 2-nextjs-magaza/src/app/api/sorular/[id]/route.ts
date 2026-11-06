import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

type Baglam = { params: Promise<{ id: string }> };

/** Satıcı: soruyu yanıtla (mağazada yayınlanır + müşteriye e-posta gider). */
export async function PATCH(request: NextRequest, ctx: Baglam) {
  const { id } = await ctx.params;
  const govde = await request.json().catch(() => ({}));

  try {
    const yanit = await fetch(`${BASE}/sorular/${Number(id)}/cevap`, {
      method: "PATCH",
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json", "Content-Type": "application/json" },
      body: JSON.stringify({ cevap: govde.cevap }),
    });
    const sonuc = await yanit.json().catch(() => ({}));
    return NextResponse.json(sonuc, { status: yanit.status });
  } catch {
    return NextResponse.json({ hata: "Arka ofise ulaşılamıyor." }, { status: 502 });
  }
}

/** Satıcı: soruyu sil. */
export async function DELETE(_request: NextRequest, ctx: Baglam) {
  const { id } = await ctx.params;
  try {
    const yanit = await fetch(`${BASE}/sorular/${Number(id)}`, {
      method: "DELETE",
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
    });
    const sonuc = await yanit.json().catch(() => ({}));
    return NextResponse.json(sonuc, { status: yanit.status });
  } catch {
    return NextResponse.json({ hata: "Arka ofise ulaşılamıyor." }, { status: 502 });
  }
}
