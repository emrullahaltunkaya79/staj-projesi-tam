import { NextRequest, NextResponse } from "next/server";
import { OTURUM_CEREZI } from "@/lib/kimlik";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";

/** DELETE /api/hesap — KVKK "unutulma hakkı": hesabı sil/anonimleştir. */
export async function DELETE(request: NextRequest) {
  const token = request.cookies.get(OTURUM_CEREZI)?.value;
  if (!token) {
    return NextResponse.json({ message: "Bu işlem için giriş yapın." }, { status: 401 });
  }
  try {
    const yanit = await fetch(`${BASE}/hesap`, {
      method: "DELETE",
      headers: { Authorization: `Bearer ${token}`, Accept: "application/json" },
      cache: "no-store",
    });
    const sonuc = await yanit.json().catch(() => ({}));
    return NextResponse.json(sonuc, { status: yanit.status });
  } catch {
    return NextResponse.json({ message: "Arka ofise ulaşılamıyor." }, { status: 502 });
  }
}
