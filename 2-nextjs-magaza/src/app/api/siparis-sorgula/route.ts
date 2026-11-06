import { NextRequest, NextResponse } from "next/server";
import { siparis } from "@/lib/backoffice";

/**
 * Misafir sipariş sorgulama: sipariş no + siparişteki e-posta birlikte doğruysa erişim bağlantısı verilir.
 * Hangisinin yanlış olduğu söylenmez (sipariş numarası taraması engellenir).
 */
export async function POST(request: NextRequest) {
  const govde = await request.json().catch(() => ({}));
  const no = String(govde.no ?? "").trim().toUpperCase();
  const email = String(govde.email ?? "").trim().toLowerCase();

  if (!no || !email) {
    return NextResponse.json({ hata: "Sipariş numarası ve e-posta zorunludur." }, { status: 422 });
  }

  const s = await siparis(no);
  if (!s || !s.access_key || (s.customer_email ?? "").toLowerCase() !== email) {
    return NextResponse.json({ hata: "Bu bilgilerle eşleşen bir sipariş bulunamadı." }, { status: 404 });
  }

  return NextResponse.json({ bag: `/siparis/${s.order_no}?k=${s.access_key}` });
}
