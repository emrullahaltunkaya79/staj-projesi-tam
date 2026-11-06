import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export async function POST(request: NextRequest) {
  const govde = await request.json().catch(() => ({}));

  try {
    const yanit = await fetch(`${BASE}/price-alerts`, {
      method: "POST",
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json", "Content-Type": "application/json" },
      body: JSON.stringify({ email: govde.email, product_id: Number(govde.product_id) }),
    });
    const sonuc = await yanit.json().catch(() => ({}));
    if (!yanit.ok) {
      return NextResponse.json({ hata: sonuc.message ?? "Alarm kurulamadı." }, { status: yanit.status === 422 ? 422 : 502 });
    }
    return NextResponse.json({ message: sonuc.message });
  } catch {
    return NextResponse.json({ hata: "Arka ofise ulaşılamıyor." }, { status: 502 });
  }
}
