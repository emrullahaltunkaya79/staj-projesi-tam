import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export async function POST(request: NextRequest, ctx: { params: Promise<{ id: string }> }) {
  const { id } = await ctx.params;
  const formData = await request.formData();

  const fd = new FormData();
  formData.forEach((value, key) => fd.append(key, value));

  const r = await fetch(`${BASE}/products/${id}/images`, {
    method: "POST",
    headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
    body: fd,
  });

  const d = await r.json().catch(() => ({}));
  return NextResponse.json(d, { status: r.status });
}
