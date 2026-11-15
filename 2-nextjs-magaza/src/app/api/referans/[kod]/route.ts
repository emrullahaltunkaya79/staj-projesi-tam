import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

/** Referans kodu doğrulama — checkout öncesi anında geri bildirim. */
export async function GET(request: NextRequest, ctx: { params: Promise<{ kod: string }> }) {
  const { kod } = await ctx.params;
  const eposta = request.nextUrl.searchParams.get("eposta") ?? "";
  const r = await fetch(`${BASE}/referans/${encodeURIComponent(kod)}?eposta=${encodeURIComponent(eposta)}`, {
    headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
    cache: "no-store",
  });
  const d = await r.json().catch(() => ({}));
  return NextResponse.json(d, { status: r.status });
}
