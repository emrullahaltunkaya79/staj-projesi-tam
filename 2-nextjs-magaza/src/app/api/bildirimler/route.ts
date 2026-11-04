import { NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export async function GET() {
  try {
    const yanit = await fetch(`${BASE}/bildirimler`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      cache: "no-store",
    });
    const d = await yanit.json().catch(() => ({}));
    return NextResponse.json({ data: d.data ?? { bulten: [], stok: [] } });
  } catch {
    return NextResponse.json({ data: { bulten: [], stok: [] } }, { status: 502 });
  }
}
