import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export async function POST(request: NextRequest) {
  try {
    const body = await request.text();
    const r = await fetch(`${BASE}/coupons/validate`, {
      method: "POST",
      headers: {
        Authorization: `Bearer ${TOKEN}`,
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body,
    });
    const d = await r.json().catch(() => ({}));
    return NextResponse.json(d, { status: r.status });
  } catch {
    return NextResponse.json({ valid: false, message: "Kupon doğrulanamadı." }, { status: 502 });
  }
}
