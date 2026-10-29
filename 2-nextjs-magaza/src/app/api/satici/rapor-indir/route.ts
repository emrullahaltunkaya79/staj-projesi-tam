import { NextRequest, NextResponse } from "next/server";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

function csv(data: Record<string, unknown>[]): string {
  if (data.length === 0) return "";
  const headers = Object.keys(data[0]);
  const rows = data.map((r) =>
    headers.map((h) => {
      const v = r[h] ?? "";
      const s = String(v).replace(/"/g, '""');
      return /[",\n;]/.test(s) ? `"${s}"` : s;
    }).join(";"),
  );
  return "\uFEFF" + headers.join(";") + "\n" + rows.join("\n");
}

export async function GET(request: NextRequest) {
  const tur = request.nextUrl.searchParams.get("tur") ?? "urunler";

  try {
    let data: Record<string, unknown>[] = [];

    if (tur === "urunler") {
      const r = await fetch(`${BASE}/products`, {
        headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
        cache: "no-store",
      });
      const d = await r.json();
      data = (d.data ?? []).map((p: Record<string, unknown>) => ({
        "Ürün Adı": p.name,
        "Kod": p.code ?? "",
        "Kategori": p.category ?? "",
        "Satış Fiyatı": p.sale_price,
        "Stok": p.stock_quantity,
        "Müsait": p.available_quantity,
        "Durum": p.in_stock ? "Stokta" : "Tükendi",
      }));
    } else if (tur === "siparisler") {
      const r = await fetch(`${BASE}/orders?limit=50`, {
        headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
        cache: "no-store",
      });
      const d = await r.json();
      data = (d.data ?? []).map((o: Record<string, unknown>) => ({
        "Sipariş No": o.order_no,
        "Müşteri": o.customer_name ?? "",
        "Fatura": o.invoice_no ?? "",
        "Durum": o.status_label ?? o.status,
        "Tutar": o.total,
        "Tarih": new Date(o.created_at as string).toLocaleDateString("tr-TR"),
      }));
    }

    const dosyaAdi = `${tur}-raporu-${new Date().toISOString().slice(0, 10)}.csv`;

    return new NextResponse(csv(data), {
      headers: {
        "Content-Type": "text/csv; charset=utf-8",
        "Content-Disposition": `attachment; filename="${dosyaAdi}"`,
      },
    });
  } catch {
    return NextResponse.json({ hata: "Rapor alınamadı." }, { status: 502 });
  }
}
