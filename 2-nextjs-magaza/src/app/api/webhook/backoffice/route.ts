import { NextRequest, NextResponse } from "next/server";
import { revalidatePath } from "next/cache";
import { createHmac } from "crypto";
import { gorselOnbellekTemizle } from "@/lib/imgOnbellek";

const SECRET = process.env.BACKOFFICE_WEBHOOK_SECRET ?? "";

export async function POST(request: NextRequest) {
  const body = await request.text();
  const signature = request.headers.get("x-signature") ?? "";
  const event = request.headers.get("x-event") ?? request.headers.get("X-Event") ?? "bilinmiyor";
  const govde = (() => { try { return JSON.parse(body) as Record<string, unknown>; } catch { return {}; } })();

  // HMAC doğrulama — PHP hash_hmac(algo, data, key) ile aynı sıra: anahtar = gizli anahtar
  if (SECRET) {
    const beklenen = createHmac("sha256", SECRET).update(body).digest("hex");
    if (beklenen !== signature) {
      return NextResponse.json({ hata: "Geçersiz imza" }, { status: 401 });
    }
  }

  const katalogOlaylari = ["stock.changed", "product.upserted", "product.deleted"];
  const siparisOlaylari = ["order.paid", "order.cancelled", "order.shipped", "order.delivered", "order.returned"];

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

    // Panelde görsel değişti: ilgili ürünün ikili görsel önbelleğini düşür —
    // /api/img yeni görseli 10 dk TTL beklemeden sunar
    // (webhook gövdesi {event, timestamp, payload:{…}} saruclıdır; payload içinden okunur)
    const urunHam = (govde.payload as Record<string, unknown> | undefined)?.product_id ?? govde.product_id;
    const urunId = Number(urunHam);
    if (event === "product.upserted" && Number.isFinite(urunId) && urunId > 0) {
      const silinen = gorselOnbellekTemizle(urunId);
      (globalThis as Record<string, unknown>).__gorselTemizlenen = silinen;
    }
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
