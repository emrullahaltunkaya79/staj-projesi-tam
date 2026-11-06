import { NextRequest, NextResponse } from "next/server";
import { siparisYetkisi } from "@/lib/oturum";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export async function POST(request: NextRequest, ctx: { params: Promise<{ no: string }> }) {
  const { no } = await ctx.params;
  if (!(await siparisYetkisi(request, no))) {
    return NextResponse.json({ hata: "Bu sipariş üzerinde işlem yetkiniz yok." }, { status: 403 });
  }

  try {
    const yanit = await fetch(`${BASE}/orders/${encodeURIComponent(no)}/cancel`, {
      method: "POST",
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json", "Content-Type": "application/json" },
    });

    const sonuc = await yanit.json().catch(() => ({}));
    if (!yanit.ok) throw new Error(sonuc.message ?? `Arka ofis hatası (${yanit.status})`);

    return NextResponse.json({ message: sonuc.message ?? "Sipariş iptal edildi." });
  } catch (e) {
    return NextResponse.json(
      { hata: e instanceof Error ? e.message : "Sipariş iptal edilemedi." },
      { status: 502 },
    );
  }
}
