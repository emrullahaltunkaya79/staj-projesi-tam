import { NextRequest, NextResponse } from "next/server";
import { OTURUM_CEREZI } from "@/lib/kimlik";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";

/** "Hesabım" uçları: profil, şifre, adresler, favori/sepet durumu — müşterinin kendi oturumuyla. */
const IZINLI = /^(profil|sifre|adresler(\/\d+)?|durum)$/;

async function ilet(request: NextRequest, yol: string[], method: string) {
  const hedef = yol.join("/");
  if (!IZINLI.test(hedef)) {
    return NextResponse.json({ message: "Bulunamadı." }, { status: 404 });
  }

  const token = request.cookies.get(OTURUM_CEREZI)?.value;
  if (!token) {
    return NextResponse.json({ message: "Bu işlem için giriş yapın." }, { status: 401 });
  }

  try {
    const yanit = await fetch(`${BASE}/hesap/${hedef}`, {
      method,
      headers: { Authorization: `Bearer ${token}`, Accept: "application/json", "Content-Type": "application/json" },
      body: method === "GET" || method === "DELETE" ? undefined : await request.text(),
      cache: "no-store",
    });
    const sonuc = await yanit.json().catch(() => ({}));
    return NextResponse.json(sonuc, { status: yanit.status });
  } catch {
    return NextResponse.json({ message: "Arka ofise ulaşılamıyor." }, { status: 502 });
  }
}

type Baglam = { params: Promise<{ yol: string[] }> };

export async function GET(request: NextRequest, ctx: Baglam) {
  return ilet(request, (await ctx.params).yol, "GET");
}
export async function POST(request: NextRequest, ctx: Baglam) {
  return ilet(request, (await ctx.params).yol, "POST");
}
export async function PUT(request: NextRequest, ctx: Baglam) {
  return ilet(request, (await ctx.params).yol, "PUT");
}
export async function DELETE(request: NextRequest, ctx: Baglam) {
  return ilet(request, (await ctx.params).yol, "DELETE");
}
