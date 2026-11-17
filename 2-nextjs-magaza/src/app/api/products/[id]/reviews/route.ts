import { NextRequest, NextResponse } from "next/server";
import { cookies } from "next/headers";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

/** Oturumlu müşterinin kendi token'ı — yoksa mağaza servis token'ı (misafir yorumu). */
async function oturumTokeni(): Promise<string | null> {
  return (await cookies()).get("hba_oturum")?.value ?? null;
}

export async function GET(_request: NextRequest, ctx: { params: Promise<{ id: string }> }) {
  const { id } = await ctx.params;
  const r = await fetch(`${BASE}/products/${id}/reviews`, {
    headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
    cache: "no-store",
  });
  const d = await r.json().catch(() => ({}));
  return NextResponse.json(d, { status: r.status });
}

export async function POST(request: NextRequest, ctx: { params: Promise<{ id: string }> }) {
  const { id } = await ctx.params;
  const icerikTuru = request.headers.get("content-type") ?? "";
  // Girişli müşterinin yorumu kendi adına yazılır; misafirde servis token'ı
  // ile gelir ve formdaki user_name alanı yazar olarak kullanılır.
  const token = (await oturumTokeni()) ?? TOKEN;
  // Fotoğraflı yorum: multipart formlar sınır başlığıyla aynen aktarılır
  if (icerikTuru.includes("multipart/form-data")) {
    const form = await request.formData();
    const r = await fetch(`${BASE}/products/${id}/reviews`, {
      method: "POST",
      headers: { Authorization: `Bearer ${token}`, Accept: "application/json" },
      body: form,
    });
    const d = await r.json().catch(() => ({}));
    return NextResponse.json(d, { status: r.status });
  }
  const body = await request.text();
  const r = await fetch(`${BASE}/products/${id}/reviews`, {
    method: "POST",
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
      "Content-Type": "application/json",
    },
    body,
  });
  const d = await r.json().catch(() => ({}));
  return NextResponse.json(d, { status: r.status });
}
