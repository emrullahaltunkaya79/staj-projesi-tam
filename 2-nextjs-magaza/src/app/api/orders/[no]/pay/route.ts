import { NextRequest, NextResponse } from "next/server";
import { ode } from "@/lib/backoffice";
import { siparisYetkisi } from "@/lib/oturum";

export async function POST(request: NextRequest, ctx: { params: Promise<{ no: string }> }) {
  const { no } = await ctx.params;
  if (!(await siparisYetkisi(request, no))) {
    return NextResponse.json({ hata: "Bu sipariş üzerinde işlem yetkiniz yok." }, { status: 403 });
  }

  try {
    const sonuc = await ode(no);
    return NextResponse.json({ message: sonuc.message, invoice_no: sonuc.invoice_no ?? null });
  } catch (e) {
    return NextResponse.json(
      { hata: e instanceof Error ? e.message : "Ödeme işlenemedi." },
      { status: 502 },
    );
  }
}
