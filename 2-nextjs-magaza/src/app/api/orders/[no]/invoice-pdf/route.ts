import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export async function GET(_request: NextRequest, ctx: { params: Promise<{ no: string }> }) {
  const { no } = await ctx.params;

  try {
    const yanit = await fetch(`${BASE}/orders/${no}/invoice.pdf`, {
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
      },
    });
  } catch {
    return NextResponse.json({ hata: "Fatura alınamadı." }, { status: 502 });
  }
}
