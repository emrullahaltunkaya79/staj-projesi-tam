import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";

/** Google girişi tek kullanımlık kodunu token + kullanıcı ile değiştirir ve çereze yazar. */
export async function POST(request: NextRequest) {
  const govde = await request.json().catch(() => ({}));
  const kod = String(govde.code ?? "");

  if (!kod) {
    return NextResponse.json({ message: "Kod zorunludur." }, { status: 422 });
  }

  try {
    const yanit = await fetch(`${BASE}/auth/google/exchange`, {
      method: "POST",
      headers: { Accept: "application/json", "Content-Type": "application/json" },
      body: JSON.stringify({ code: kod }),
    });
    const sonuc = await yanit.json().catch(() => ({}));
    if (!yanit.ok) {
      return NextResponse.json({ message: sonuc.message ?? "Giriş tamamlanamadı." }, { status: yanit.status });
    }

    const cevap = NextResponse.json(sonuc);
    const token = sonuc?.data?.token;
    if (token) {
      cevap.cookies.set("hba_oturum", token, {
        httpOnly: true,
        sameSite: "lax",
        secure: process.env.NODE_ENV === "production",
        path: "/",
        maxAge: 60 * 60 * 24 * 30,
      });
    }
    return cevap;
  } catch {
    return NextResponse.json({ message: "Arka ofise ulaşılamıyor." }, { status: 502 });
  }
}
