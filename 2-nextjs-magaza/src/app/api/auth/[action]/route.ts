import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";

async function proxy(request: NextRequest, path: string, method: string) {
  const authHeader = request.headers.get("authorization") ?? "";
  try {
    const yanit = await fetch(`${BASE}${path}`, {
      method,
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        ...(authHeader ? { Authorization: authHeader } : {}),
      },
      body: method !== "GET" && method !== "HEAD" ? await request.text() : undefined,
    });
    const sonuc = await yanit.json().catch(() => ({}));
    return NextResponse.json(sonuc, { status: yanit.status });
  } catch (e) {
    return NextResponse.json({ message: "Arka ofise ulaşılamıyor." }, { status: 502 });
  }
}

export async function POST(request: NextRequest, ctx: { params: Promise<{ action: string }> }) {
  const { action } = await ctx.params;
  return proxy(request, `/auth/${action}`, "POST");
}

export async function GET(request: NextRequest, ctx: { params: Promise<{ action: string }> }) {
  const { action } = await ctx.params;
  return proxy(request, `/auth/${action}`, "GET");
}
