import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export async function DELETE(_request: NextRequest, ctx: { params: Promise<{ id: string }> }) {
  const { id } = await ctx.params;
  try {
    const yanit = await fetch(`${BASE}/coupons/${id}`, {
      method: "DELETE",
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
    });
    const d = await yanit.json().catch(() => ({}));
    return NextResponse.json(d, { status: yanit.status });
  } catch {
    return NextResponse.json({ message: "İşlem başarısız." }, { status: 502 });
  }
}
