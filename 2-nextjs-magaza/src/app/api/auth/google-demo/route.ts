import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";

/** Google girişi DEMO modu — yalnızca OAuth kimliği yapılandırılmamışken arka ofis izin verir. */
export async function POST(request: NextRequest) {
  const govde = await request.json().catch(() => ({}));

  try {
    const yanit = await fetch(`${BASE}/auth/google/demo-login`, {
      method: "POST",
      headers: { Accept: "application/json", "Content-Type": "application/json" },
      body: JSON.stringify(govde),
    });

    const sonuc = await yanit.json().catch(() => ({}));
    if (!yanit.ok) {
      return NextResponse.json({ message: sonuc.message ?? "Demo giriş başarısız." }, { status: yanit.status });
    }

    return NextResponse.json(sonuc);
  } catch {
    return NextResponse.json({ message: "Sunucuya ulaşılamadı." }, { status: 502 });
  }
}
