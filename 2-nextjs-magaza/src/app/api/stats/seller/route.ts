import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export async function GET() {
  try {
    const yanit = await fetch(`${BASE}/stats/seller`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      cache: "no-store",
    });
    if (!yanit.ok) throw new Error(`Arka ofis hatası (${yanit.status})`);
    const sonuc = await yanit.json();
    return NextResponse.json(sonuc);
  } catch (e) {
    return NextResponse.json(
      { hata: e instanceof Error ? e.message : "İstatistik alınamadı." },
      { status: 502 },
    );
  }
}
