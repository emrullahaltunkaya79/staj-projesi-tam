import { NextRequest, NextResponse } from "next/server";
import { urunler, siparisOlustur } from "@/lib/backoffice";
import { kdvDahil } from "@/lib/fiyat";
import { KARGO_BOLGELERI, UCRETSIZ_KARGO_LIMITI } from "@/lib/kargo";
import { kargoBolgesi } from "@/lib/adres";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";
const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

/**
 * Sipariş fiyatlandırması GÜVENİLİR SUNUCUDA yapılır: müşteri yalnızca ürün/sepet
 * bilgisi + kupon kodu gönderir. Kalem fiyatları arka ofis verisinden hesaplanır;
 * kupon indirimi ve kargo ücreti arka ofis siparişine işlenir. İstemciden gelen
 * hiçbir fiyat kabul edilmez (fiyat manipülasyonu engellenir).
 */
export async function POST(request: NextRequest) {
  try {
    const govde = await request.json();

    const zorunlu = ["name", "email", "phone", "address", "items"] as const;
    for (const alan of zorunlu) {
      if (!govde?.[alan]) {
        return NextResponse.json({ hata: `"${alan}" alanı zorunludur.` }, { status: 422 });
      }
    }

    // 1) Ürünler arka ofisten doğrulanır — istemci fiyatları hiç okunmaz
    const katalog = await urunler();
    const harita = new Map(katalog.map((u) => [u.id, u]));

    let genelToplamDahil = 0;
    const satirlar: { product_id: number; quantity: number; variant_id?: number }[] = [];
    for (const i of govde.items as { product_id: number; quantity: number; variant_id?: number }[]) {
      const urun = harita.get(Number(i.product_id));
      if (!urun) throw new Error("Sepetinizdeki bir ürün artık mevcut değil.");
      if (!urun.in_stock) throw new Error(`"${urun.name}" tükendi.`);

      genelToplamDahil += kdvDahil(urun.final_price ?? urun.sale_price) * Number(i.quantity);
      satirlar.push({
        product_id: Number(i.product_id),
        quantity: Number(i.quantity),
        ...(i.variant_id ? { variant_id: Number(i.variant_id) } : {}),
      });
    }
    genelToplamDahil = Math.round(genelToplamDahil * 100) / 100;

    // 2) Kargo ücreti: bölge adres bilgisinden (il/ilçe) türetilir
    const bolge = KARGO_BOLGELERI.find(
      (b) => b.id === kargoBolgesi(String(govde.il ?? ""), String(govde.ilce ?? "")),
    ) ?? null;
    let kargo = bolge ? bolge.fiyat : 0;
    if (genelToplamDahil >= UCRETSIZ_KARGO_LIMITI) kargo = 0;

    // 3) Kupon: kod istemciden gelir, indirim arka ofiste doğrulanır
    const couponCode = String(govde.couponCode ?? "").trim().toUpperCase();
    let indirim = 0;
    if (couponCode) {
      const yanit = await fetch(`${BASE}/coupons/validate`, {
        method: "POST",
        headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json", "Content-Type": "application/json" },
        body: JSON.stringify({ code: couponCode, cart_total: genelToplamDahil }),
      });
      const d = await yanit.json().catch(() => ({}));
      if (!d.valid) throw new Error(d.message ?? "Kupon geçersiz.");
      indirim = Number(d.data?.discount ?? 0);
      if (d.data?.type === "free_shipping") kargo = 0;
    }

    // 4) Ödenecek tutar (KDV dahil) → kalem net fiyatları bu tutara ölçeklenir;
    //    böylece arka ofisteki sipariş/fatura tutarı müşterinin ödediğiyle birebir olur.
    const odenecek = Math.max(0, genelToplamDahil - indirim) + kargo;
    const oran = genelToplamDahil > 0 ? odenecek / genelToplamDahil : 1;
    const items = satirlar.map((s) => {
      const urun = harita.get(s.product_id)!;
      const net = urun.final_price ?? urun.sale_price;
      return { ...s, unit_price: Math.round(net * oran * 10000) / 10000 };
    });

    const notesNotlari = [
      ...(couponCode ? [`Kupon ${couponCode}: -${fmt(indirim)} TL`] : []),
      ...(kargo > 0 && bolge ? [`Kargo: ${fmt(kargo)} TL (${bolge.ad})`] : []),
    ].join(" | ");

    const sonuc = await siparisOlustur({
      name: String(govde.name),
      email: String(govde.email),
      phone: String(govde.phone),
      address: String(govde.address),
      items,
      externalRef: String(govde.externalRef ?? `NEXT-${Date.now()}`),
      notes: notesNotlari || undefined,
      fatura: {
        tipi: govde.fatura?.tipi === "kurumsal" ? "kurumsal" : "bireysel",
        ...(govde.fatura?.tipi === "kurumsal"
          ? {
              unvan: String(govde.fatura.unvan ?? ""),
              vergiDairesi: String(govde.fatura.vergiDairesi ?? ""),
              vergiNo: String(govde.fatura.vergiNo ?? ""),
            }
          : {}),
        ...(govde.fatura?.adres ? { adres: String(govde.fatura.adres) } : {}),
      },
    });

    return NextResponse.json({ orderNo: sonuc.orderNo, erisimAnahtari: sonuc.accessKey, message: sonuc.message });
  } catch (e) {
    const mesaj = e instanceof Error ? e.message : "Sipariş oluşturulamadı.";
    const dogrulama = mesaj.match(/"message":"([^"]+)"/);
    return NextResponse.json({ hata: dogrulama ? dogrulama[1] : mesaj }, { status: 502 });
  }
}
