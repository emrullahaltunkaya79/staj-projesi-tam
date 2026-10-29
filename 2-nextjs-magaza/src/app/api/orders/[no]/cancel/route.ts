import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export async function POST(_request: NextRequest, ctx: { params: Promise<{ no: string }> }) {
  const { no } = await ctx.params;

  try {
    const yanit = await fetch(`${BASE}/orders/${no}/cancel`, {
      method: "POST",
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json", "Content-Type": "application/json" },
      body: JSON.stringify({ reason: "müşteri talebi (web)" }),
    });

    const sonuc = await yanit.json().catch(() => ({}));
    if (!yanit.ok) throw new Error(sonuc.message ?? `Arka ofis hatası (${yanit.status})`);

    return NextResponse.json({ message: sonuc.message ?? "Sipariş iptal edildi." });
  } catch (e) {
    return NextResponse.json(
      { hata: e instanceof Error ? e.message : "İptal edilemedi." },
      { status: 502 },
    );
  }
}
