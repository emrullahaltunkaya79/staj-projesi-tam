/**
 * Arka ofis (Ön Muhasebe) API istemcisi — sunucu tarafında çalışır.
 * Tüm katalog/stok/sipariş verisi tek kaynaktan (arka ofis) gelir.
 */

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export type Urun = {
  id: number;
  code: string | null;
  barcode: string | null;
  name: string;
  category: string | null;
  unit: string;
  sale_price: number;
  discount_percent?: number | null;
  final_price?: number;
  stock_quantity: number;
  reserved_quantity: number;
  available_quantity: number;
  in_stock: boolean;
  image_url: string | null;
  image_count?: number;
  rating_avg?: number | null;
  review_count?: number;
  is_bestseller?: boolean;
};

/** İndirim uygulanmış nihai fiyat (API final_price vermezse kendisi hesaplar). */
export function indirimliFiyat(u: Pick<Urun, "sale_price" | "discount_percent" | "final_price">): number {
  if (u.final_price != null) return u.final_price;
  const oran = u.discount_percent ?? 0;
  return oran > 0 ? Math.round(u.sale_price * (100 - oran)) / 100 : u.sale_price;
}

export type Siparis = {
  order_no: string;
  status: string;
  status_label?: string;
  total: number;
  expires_at?: string | null;
  invoice_no?: string | null;
  tracking_no?: string | null;
  tracking_url?: string | null;
  items: { product_id: number; product_name: string; quantity: number; unit_price: number; line_total: number }[];
};

/** Base64 data URI'ler sayfa boyutunu ~24MB'a çıkarıyordu; görsel /api/img/{id} proxy'sinden akar. */
function gorselProxy(p: Urun): Urun {
  return p.image_url?.startsWith("data:") ? { ...p, image_url: `/api/img/${p.id}` } : p;
}

async function istek(path: string, init?: RequestInit) {
  const yanit = await fetch(`${BASE}${path}`, {
    ...init,
    headers: {
      Authorization: `Bearer ${TOKEN}`,
      Accept: "application/json",
      "Content-Type": "application/json",
      ...(init?.headers ?? {}),
    },
    cache: "no-store",
  });

  if (!yanit.ok) {
    const metin = await yanit.text().catch(() => "");
    throw new Error(`Arka ofis hatası (${yanit.status}): ${metin.slice(0, 200)}`);
  }

  return yanit.json();
}

export async function urunler(): Promise<Urun[]> {
  const sonuc = await istek("/products");
  return (sonuc.data ?? []).map(gorselProxy);
}

export async function urun(id: number): Promise<Urun | null> {
  try {
    const sonuc = await istek(`/products/${id}`);
    return sonuc.data ? gorselProxy(sonuc.data) : null;
  } catch {
    return null;
  }
}

export async function siparis(orderNo: string): Promise<Siparis | null> {
  try {
    const sonuc = await istek(`/orders/${orderNo}`);
    return sonuc.data ?? null;
  } catch {
    return null;
  }
}

/** Idempotent müşteri + external_ref'li sipariş oluşturur. */
export async function siparisOlustur(veri: {
  name: string;
  email: string;
  address: string;
  taxRate: number;
  items: { product_id: number; quantity: number; variant_id?: number }[];
  externalRef: string;
}): Promise<{ orderNo: string; message: string }> {
  const musteri = await istek("/customers", {
    method: "POST",
    body: JSON.stringify({ name: veri.name, email: veri.email, type: "musteri" }),
  });

  const sonuc = await istek("/orders", {
    method: "POST",
    body: JSON.stringify({
      contact_id: musteri.data.contact_id,
      tax_rate: veri.taxRate,
      items: veri.items,
      shipping_address: veri.address,
      external_ref: veri.externalRef,
      customer_email: veri.email,
      customer_name: veri.name,
    }),
  });

  if (!sonuc.data?.order_no) {
    throw new Error(sonuc.message ?? "Sipariş oluşturulamadı.");
  }

  return { orderNo: sonuc.data.order_no, message: sonuc.message };
}

export async function ode(orderNo: string): Promise<{ message: string; invoice_no?: string }> {
  const sonuc = await istek(`/orders/${orderNo}/pay`, {
    method: "POST",
    body: JSON.stringify({ payment_method: "cspr" }),
  });

  return { message: sonuc.message ?? "Ödeme işlendi.", invoice_no: sonuc.data?.invoice_no };
}
