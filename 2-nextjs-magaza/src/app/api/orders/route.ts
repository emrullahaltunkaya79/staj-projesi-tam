import { NextRequest, NextResponse } from "next/server";
import { siparisOlustur } from "@/lib/backoffice";

export async function POST(request: NextRequest) {
  try {
    const govde = await request.json();

    const zorunlu = ["name", "email", "phone", "address", "items"] as const;
    for (const alan of zorunlu) {
      if (!govde?.[alan]) {
        return NextResponse.json({ hata: `"${alan}" alanı zorunludur.` }, { status: 422 });
      }
    }

    const kurumsal = govde.fatura?.tipi === "kurumsal";

    const sonuc = await siparisOlustur({
      name: String(govde.name),
      email: String(govde.email),
      phone: String(govde.phone),
      address: String(govde.address),
      items: (govde.items as { product_id: number; quantity: number; variant_id?: number }[]).map((i) => ({
        product_id: Number(i.product_id),
        quantity: Number(i.quantity),
        ...(i.variant_id ? { variant_id: Number(i.variant_id) } : {}),
      })),
      externalRef: String(govde.externalRef ?? `NEXT-${Date.now()}`),
      fatura: {
        tipi: kurumsal ? "kurumsal" : "bireysel",
        ...(kurumsal
          ? { unvan: String(govde.fatura.unvan ?? ""), vergiDairesi: String(govde.fatura.vergiDairesi ?? ""), vergiNo: String(govde.fatura.vergiNo ?? "") }
          : {}),
        ...(govde.fatura?.adres ? { adres: String(govde.fatura.adres) } : {}),
      },
    });

    return NextResponse.json({ orderNo: sonuc.orderNo, erisimAnahtari: sonuc.accessKey, message: sonuc.message });
  } catch (e) {
    const mesaj = e instanceof Error ? e.message : "Sipariş oluşturulamadı.";
    // Arka ofis doğrulama hatalarını (422) okunur hale getir
    const dogrulama = mesaj.match(/"message":"([^"]+)"/);
    return NextResponse.json({ hata: dogrulama ? dogrulama[1] : mesaj }, { status: 502 });
  }
}
