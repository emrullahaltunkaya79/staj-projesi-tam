import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export async function POST(request: NextRequest) {
  const govde = await request.text();

  try {
    const yanit = await fetch(`${BASE}/newsletter`, {
      method: "POST",
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json", "Content-Type": "application/json" },
      body: govde,
    });

    const sonuc = await yanit.json().catch(() => ({}));
    if (!yanit.ok) throw new Error(sonuc.message ?? "Kayıt oluşturulamadı.");

    return NextResponse.json({ message: sonuc.message ?? "Abone oldunuz." });
  } catch (e) {
    return NextResponse.json(
      { hata: e instanceof Error ? e.message : "Kayıt oluşturulamadı." },
      { status: 502 },
    );
  }
}
