import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

/** PATCH: yorumu göster/gizle — DELETE: kalıcı sil. */
export async function PATCH(request: NextRequest, ctx: { params: Promise<{ id: string }> }) {
  return islem("PATCH", `${BASE}/yorumlar/${(await ctx.params).id}/onay`, await request.text());
}

export async function DELETE(request: NextRequest, ctx: { params: Promise<{ id: string }> }) {
  return islem("DELETE", `${BASE}/yorumlar/${(await ctx.params).id}`, null);
}

async function islem(metot: string, url: string, govde: string | null) {
  try {
    const r = await fetch(url, {
      method: metot,
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      body: govde,
    });
    const d = await r.json().catch(() => ({}));
    return NextResponse.json(d, { status: r.status });
  } catch {
    return NextResponse.json({ message: "İşlem başarısız." }, { status: 502 });
  }
}
