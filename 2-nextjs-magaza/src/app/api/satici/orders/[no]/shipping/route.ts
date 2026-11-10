import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

type Baglam = { params: Promise<{ no: string }> };

/** Satıcı: kargo firması + takip numarası girer; durum "shipped" olur ve müşteriye webhook gider. */
export async function POST(request: NextRequest, ctx: Baglam) {
  const { no } = await ctx.params;
  const govde = await request.json().catch(() => ({}));

  try {
    const yanit = await fetch(`${BASE}/orders/${encodeURIComponent(no)}/shipping`, {
      method: "POST",
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json", "Content-Type": "application/json" },
      body: JSON.stringify({
        carrier: govde.carrier,
        tracking_no: govde.tracking_no,
        status: "shipped",
      }),
    });

    const sonuc = await yanit.json().catch(() => ({}));
    if (!yanit.ok) {
      const mesaj = sonuc.message ?? (sonuc.errors ? Object.values(sonuc.errors)[0] : null) ?? `Arka ofis hatası (${yanit.status})`;
      return NextResponse.json({ hata: Array.isArray(mesaj) ? mesaj[0] : mesaj }, { status: yanit.status });
    }

    return NextResponse.json({ message: sonuc.message ?? "Kargo bilgileri kaydedildi.", data: sonuc.data ?? null });
  } catch {
    return NextResponse.json({ hata: "Arka ofise ulaşılamıyor." }, { status: 502 });
  }
}
