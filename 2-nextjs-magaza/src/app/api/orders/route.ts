import { NextRequest, NextResponse } from "next/server";
import { siparisOlustur } from "@/lib/backoffice";

export async function POST(request: NextRequest) {
  try {
    const govde = await request.json();

    const zorunlu = ["name", "email", "address", "items"] as const;
    for (const alan of zorunlu) {
      if (!govde?.[alan]) {
        return NextResponse.json({ hata: `"${alan}" alanı zorunludur.` }, { status: 422 });
      }
    }

    const sonuc = await siparisOlustur({
      name: String(govde.name),
      email: String(govde.email),
      address: String(govde.address),
      taxRate: Number(govde.taxRate ?? 20),
      items: (govde.items as { product_id: number; quantity: number; variant_id?: number }[]).map((i) => ({
        product_id: Number(i.product_id),
        quantity: Number(i.quantity),
        ...(i.variant_id ? { variant_id: Number(i.variant_id) } : {}),
      })),
      externalRef: String(govde.externalRef ?? `NEXT-${Date.now()}`),
    });

    return NextResponse.json({ orderNo: sonuc.orderNo, message: sonuc.message });
  } catch (e) {
    return NextResponse.json(
      { hata: e instanceof Error ? e.message : "Sipariş oluşturulamadı." },
      { status: 502 },
    );
  }
}
