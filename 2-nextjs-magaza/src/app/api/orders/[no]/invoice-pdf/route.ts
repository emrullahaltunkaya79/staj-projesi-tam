import { NextRequest, NextResponse } from "next/server";
import { siparisYetkisi } from "@/lib/oturum";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export async function GET(request: NextRequest, ctx: { params: Promise<{ no: string }> }) {
  const { no } = await ctx.params;
  if (!(await siparisYetkisi(request, no))) {
    return NextResponse.json({ hata: "Bu faturayı görüntüleme yetkiniz yok." }, { status: 403 });
  }

  try {
    const yanit = await fetch(`${BASE}/orders/${encodeURIComponent(no)}/invoice.pdf`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/pdf" },
    });

    if (!yanit.ok) {
      const sonuc = await yanit.json().catch(() => ({}));
      return NextResponse.json(
        { hata: sonuc.message ?? "Fatura bulunamadı." },
        { status: yanit.status === 404 ? 404 : 502 },
      );
    }

    const pdf = Buffer.from(await yanit.arrayBuffer());
    return new NextResponse(new Uint8Array(pdf), {
      headers: {
        "Content-Type": "application/pdf",
        "Content-Disposition": `attachment; filename="${no}-fatura.pdf"`,
        "Cache-Control": "private, no-store",
        "Referrer-Policy": "no-referrer",
      },
    });
  } catch {
    return NextResponse.json({ hata: "Fatura alınamadı." }, { status: 502 });
  }
}
