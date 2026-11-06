import { NextRequest, NextResponse } from "next/server";
import { OTURUM_CEREZI, cerezAyarlari } from "@/lib/kimlik";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";

const IZINLI = new Set([
  "login", "register", "me", "logout",
  "forgot-password", "reset-password", "verify-email", "resend-verification",
]);

/**
 * Müşteri kimlik işlemleri. Giriş/kayıtta token httpOnly çereze de yazılır: sunucu sayfaları
 * (sipariş, satıcı paneli) kimliği buradan doğrular.
 */
async function ilet(request: NextRequest, action: string, method: string) {
  if (!IZINLI.has(action)) {
    return NextResponse.json({ message: "Bulunamadı." }, { status: 404 });
  }

  const baslik = request.headers.get("authorization") ?? "";
  const token = baslik.replace(/^Bearer\s+/i, "") || request.cookies.get(OTURUM_CEREZI)?.value || "";

  try {
    const yanit = await fetch(`${BASE}/auth/${action}`, {
      method,
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: method === "POST" ? await request.text() : undefined,
    });
    const sonuc = await yanit.json().catch(() => ({}));
    const cevap = NextResponse.json(sonuc, { status: yanit.status });

    if (yanit.ok && (action === "login" || action === "register") && sonuc?.data?.token) {
      cevap.cookies.set(OTURUM_CEREZI, sonuc.data.token, cerezAyarlari);
    } else if (yanit.ok && action === "me" && token) {
      // Çerezden önce açılmış oturumlar ilk kontrolde çereze taşınır
      cevap.cookies.set(OTURUM_CEREZI, token, cerezAyarlari);
    }
    if (action === "logout" || (action === "me" && yanit.status === 401)) {
      cevap.cookies.delete(OTURUM_CEREZI);
    }

    return cevap;
  } catch {
    return NextResponse.json({ message: "Arka ofise ulaşılamıyor." }, { status: 502 });
  }
}

export async function POST(request: NextRequest, ctx: { params: Promise<{ action: string }> }) {
  const { action } = await ctx.params;
  return ilet(request, action, "POST");
}

export async function GET(request: NextRequest, ctx: { params: Promise<{ action: string }> }) {
  const { action } = await ctx.params;
  return ilet(request, action, "GET");
}
