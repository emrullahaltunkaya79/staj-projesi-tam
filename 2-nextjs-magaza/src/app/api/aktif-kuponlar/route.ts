import { NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

type HamKupon = {
  code: string;
  name: string;
  type: string;
  value: number;
  min_order_total?: number | null;
  used_count?: number;
  usage_limit?: number | null;
  expires_at?: string | null;
};

/**
 * Müşteriye açık: yalnızca kullanılabilir (süresi geçmemiş + kotası dolmamış) kuponlar.
 * Hassas olmayan alanlar listelenir — kodu kopyalayıp checkout'ta kullanır.
 */
export async function GET() {
  try {
    const yanit = await fetch(`${BASE}/coupons`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      cache: "no-store",
    });
    const d = await yanit.json().catch(() => ({}));
    const simdi = Date.now();
    const liste = ((d.data ?? []) as HamKupon[])
      .filter((k) => !k.expires_at || new Date(k.expires_at).getTime() > simdi)
      .filter((k) => k.usage_limit == null || (k.used_count ?? 0) < k.usage_limit)
      .map((k) => ({
        code: k.code,
        name: k.name,
        etiket: k.type === "percent" ? `%${k.value} indirim` : k.type === "fixed" ? `${k.value} ₺ indirim` : "Ücretsiz kargo",
        min_order_total: Number(k.min_order_total ?? 0),
        son_gun: k.expires_at ?? null,
      }));
    return NextResponse.json({ data: liste });
  } catch {
    return NextResponse.json({ data: [] }, { status: 502 });
  }
}
