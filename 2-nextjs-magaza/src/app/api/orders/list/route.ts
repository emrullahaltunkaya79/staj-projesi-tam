import { NextResponse } from "next/server";
import { oturumKullanicisi } from "@/lib/oturum";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

/** Giriş yapmış müşterinin kendi siparişleri — e-posta oturumdan alınır, istekten değil. */
export async function GET() {
  const kullanici = await oturumKullanicisi();
  if (!kullanici) {
    return NextResponse.json({ hata: "Siparişlerinizi görmek için giriş yapın." }, { status: 401 });
  }
  if (!kullanici.email_verified) {
    return NextResponse.json(
      { hata: "Siparişlerinizi listelemek için önce e-postanızı doğrulayın.", dogrulamaGerekli: true },
      { status: 403 },
    );
  }

  try {
    const yanit = await fetch(`${BASE}/orders?email=${encodeURIComponent(kullanici.email)}`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      cache: "no-store",
    });
    if (!yanit.ok) throw new Error(`Arka ofis hatası (${yanit.status})`);

    const sonuc = await yanit.json();
    // Erişim anahtarları tarayıcıya gönderilmez; sahiplik oturumla doğrulanır
    const liste = (sonuc.data ?? []).map((s: Record<string, unknown>) => {
      const kopya = { ...s };
      delete kopya.access_key;
      return kopya;
    });
    return NextResponse.json({ data: liste });
  } catch (e) {
    return NextResponse.json({ hata: e instanceof Error ? e.message : "Siparişler alınamadı." }, { status: 502 });
  }
}
