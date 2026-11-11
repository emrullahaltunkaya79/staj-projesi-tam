import { NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";

/** Google girişinin gerçek OAuth mu, demo mu olduğu — giriş kartı akışı buna göre seçer. */
export async function GET() {
  try {
    const yanit = await fetch(`${BASE}/auth/google/durum`, {
      headers: { Accept: "application/json" },
      cache: "no-store",
    });
    const sonuc = await yanit.json().catch(() => ({}));
    return NextResponse.json({ yapilandirildi: Boolean(sonuc?.data?.yapilandirildi) });
  } catch {
    return NextResponse.json({ yapilandirildi: false });
  }
}
