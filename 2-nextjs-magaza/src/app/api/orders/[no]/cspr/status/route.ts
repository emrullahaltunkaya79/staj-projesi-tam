import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export async function GET(request: NextRequest, ctx: { params: Promise<{ no: string }> }) {
  const { no } = await ctx.params;
  const memo = request.nextUrl.searchParams.get("memo") ?? "";

  if (!memo) return NextResponse.json({ status: "unknown", paid: false });

  try {
    const yanit = await fetch(`${BASE}/cspr-payments/${encodeURIComponent(memo)}`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      cache: "no-store",
    });

    if (!yanit.ok) return NextResponse.json({ status: "unknown", paid: false });

    const sonuc = await yanit.json();
    const durum = sonuc.data?.status ?? "unknown";

    return NextResponse.json({
      status: durum,
      paid: ["confirmed", "paid"].includes(durum),
    });
  } catch {
    return NextResponse.json({ status: "unknown", paid: false });
  }
}
