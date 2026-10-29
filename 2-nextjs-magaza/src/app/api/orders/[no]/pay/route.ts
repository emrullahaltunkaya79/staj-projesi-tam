import { NextRequest, NextResponse } from "next/server";
import { ode } from "@/lib/backoffice";

export async function POST(_request: NextRequest, ctx: { params: Promise<{ no: string }> }) {
  const { no } = await ctx.params;

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
