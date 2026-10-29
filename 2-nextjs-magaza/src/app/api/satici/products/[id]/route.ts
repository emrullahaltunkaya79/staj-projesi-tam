import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

type Params = { params: Promise<{ id: string }> };

export async function PUT(request: NextRequest, ctx: Params) {
  const { id } = await ctx.params;
  const body = await request.text();
  const r = await fetch(`${BASE}/products/${id}`, {
    method: "PUT",
    headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json", "Content-Type": "application/json" },
    body,
  });
  const d = await r.json().catch(() => ({}));
  return NextResponse.json(d, { status: r.status });
}

export async function DELETE(_request: NextRequest, ctx: Params) {
  const { id } = await ctx.params;
  const r = await fetch(`${BASE}/products/${id}`, {
    method: "DELETE",
    headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
  });
  const d = await r.json().catch(() => ({}));
  return NextResponse.json(d, { status: r.status });
}
