import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export async function POST(request: NextRequest) {
  const govde = await request.text();
  try {
    const yanit = await fetch(`${BASE}/iletisim`, {
      method: "POST",
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json", "Content-Type": "application/json" },
      body: govde,
    });
    const d = await yanit.json().catch(() => ({}));
    return NextResponse.json({ message: d.message ?? "Mesajınız alındı." }, { status: yanit.status });
  } catch {
    return NextResponse.json({ message: "Mesaj gönderilemedi." }, { status: 502 });
  }
}

export async function GET() {
  try {
    const yanit = await fetch(`${BASE}/iletisim`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      cache: "no-store",
    });
    const d = await yanit.json().catch(() => ({}));
    return NextResponse.json({ data: d.data ?? [] });
  } catch {
    return NextResponse.json({ data: [] }, { status: 502 });
  }
}
