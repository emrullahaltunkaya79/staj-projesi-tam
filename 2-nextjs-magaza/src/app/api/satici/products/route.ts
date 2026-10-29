import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export async function POST(request: NextRequest) {
  const body = await request.text();
  const r = await fetch(`${BASE}/products`, {
    method: "POST",
    headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json", "Content-Type": "application/json" },
    body,
  });
  const d = await r.json().catch(() => ({}));
  return NextResponse.json(d, { status: r.status });
}
