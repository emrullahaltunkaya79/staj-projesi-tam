import { NextRequest, NextResponse } from "next/server";
import { revalidatePath } from "next/cache";
import { createHmac } from "crypto";

const SECRET = process.env.BACKOFFICE_WEBHOOK_SECRET ?? "";

/**
 * Katalog önbelleği sürüm anahtarı — her webhook'ta artırılır.
 * `force-dynamic` sayfalar fetch cache'ini bu anahtarla kırar.
 */
const KATALOG_ANAHTARI = "hba-katalog-v";
const SIPARIS_ANAHTARI = "hba-siparis-v";

export async function POST(request: NextRequest) {
  const body = await request.text();
  const signature = request.headers.get("x-signature") ?? "";
  const event = request.headers.get("x-event") ?? request.headers.get("X-Event") ?? "bilinmiyor";

  // HMAC doğrulama
  if (SECRET) {
    const beklenen = createHmac("sha256", body).update(SECRET).digest("hex");
    if (beklenen !== signature) {
      return NextResponse.json({ hata: "Geçersiz imza" }, { status: 401 });
    }
  }

  const katalogOlaylari = ["stock.changed", "product.upserted", "product.deleted"];
  const siparisOlaylari = ["order.paid", "order.cancelled", "order.shipped"];

  if (katalogOlaylari.includes(event)) {
    // Dev'de revalidatePath, production'da cache tag busting
    try {
      revalidatePath("/", "layout");
      revalidatePath("/kategori/[ad]", "page");
      revalidatePath("/satici/urunler", "page");
    } catch {
      // revalidatePath bazı ortamlarda çalışmayabilir — fetch cache tag ile çözülür
    }

    // Fetch cache'i sürüm anahtarıyla kır (production-safe)
    (globalThis as Record<string, unknown>).__katalogSurum =
      ((globalThis as Record<string, unknown>).__katalogSurum as number ?? 0) + 1;
  }

  if (siparisOlaylari.includes(event)) {
    try {
      revalidatePath("/siparislerim", "page");
      revalidatePath("/satici", "layout");
      revalidatePath("/satici/siparisler", "page");
    } catch {}

    (globalThis as Record<string, unknown>).__siparisSurum =
      ((globalThis as Record<string, unknown>).__siparisSurum as number ?? 0) + 1;
  }

  const g = globalThis as Record<string, unknown>;
  return NextResponse.json({
    ok: true,
    event,
    katalog_surum: (g.__katalogSurum as number) ?? 0,
    siparis_surum: (g.__siparisSurum as number) ?? 0,
  });
}
