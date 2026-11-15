import { NextRequest, NextResponse } from "next/server";
import { siparisYetkisi } from "@/lib/oturum";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

/** Kart (3D Secure) ödeme başlatma — arka ofis PSP form adresini üretir. */
export async function POST(request: NextRequest, ctx: { params: Promise<{ no: string }> }) {
  const { no } = await ctx.params;
  if (!(await siparisYetkisi(request, no))) {
    return NextResponse.json({ hata: "Bu sipariş üzerinde işlem yetkiniz yok." }, { status: 403 });
  }

  try {
    const yanit = await fetch(`${BASE}/orders/${encodeURIComponent(no)}/odeme-baslat`, {
      method: "POST",
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
    });
    const d = await yanit.json().catch(() => ({}));
    if (!yanit.ok) {
      return NextResponse.json({ hata: d.message ?? "Ödeme başlatılamadı." }, { status: yanit.status });
    }
    return NextResponse.json(d, { status: 200 });
  } catch {
    return NextResponse.json({ hata: "Arka ofise ulaşılamıyor." }, { status: 502 });
  }
}
