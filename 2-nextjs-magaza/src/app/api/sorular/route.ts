import { NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

/** Satıcı: tüm ürün soruları (yanıt bekleyenler önce). */
export async function GET() {
  try {
    const yanit = await fetch(`${BASE}/sorular`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      cache: "no-store",
    });
    const sonuc = await yanit.json().catch(() => ({}));
    return NextResponse.json({ data: sonuc.data ?? [] });
  } catch {
    return NextResponse.json({ hata: "Arka ofise ulaşılamıyor." }, { status: 502 });
  }
}
