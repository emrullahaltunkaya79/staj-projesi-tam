const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export type SellerStats = {
  ozet: {
    toplam_ciro: number;
    bu_ay_ciro: number;
    fatura_sayisi: number;
    siparis_sayisi: number;
    bekleyen_siparis: number;
    kargolanacak: number;
    urun_sayisi: number;
    kritik_stok: number;
    tukenen: number;
    stok_degeri: number;
  };
  kritik_urunler: {
    id: number;
    name: string;
    category: string | null;
    stock_quantity: number;
    sale_price: number;
  }[];
  son_siparisler: {
    order_no: string;
    customer: string | null;
    status: string;
    status_label: string;
    total: number;
    created_at: string;
  }[];
  kategori_dagilimi: {
    kategori: string;
    urun_sayisi: number;
    stok_degeri: number;
  }[];
};

async function api<T>(path: string): Promise<T | null> {
  try {
    const r = await fetch(`${BASE}${path}`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      cache: "no-store",
    });
    if (!r.ok) return null;
    const d = await r.json();
    return d.data ?? d;
  } catch {
    return null;
  }
}

export async function statsSeller(): Promise<SellerStats | null> {
  return api<SellerStats>("/stats/seller");
}

export type SellerUrun = {
  id: number;
  name: string;
  category: string | null;
  sale_price: number;
  purchase_price: number;
  discount_percent?: number | null;
  stock_quantity: number;
  available_quantity: number;
  in_stock: boolean;
  image_url: string | null;
  code: string | null;
  unit: string;
};

export async function sellerUrunler(): Promise<SellerUrun[]> {
  const list = (await api<SellerUrun[]>("/products")) ?? [];
  // Base64 data URI HTML'e gömülmesin — görsel /api/img/{id} proxy'sinden akar
  return list.map((u) => (u.image_url?.startsWith("data:") ? { ...u, image_url: `/api/img/${u.id}` } : u));
}

export async function sellerSiparisler(): Promise<
  { order_no: string; status: string; status_label: string; total: number; customer_name: string | null; invoice_no: string | null; created_at: string }[]
> {
  return (await api<{ order_no: string; status: string; status_label: string; total: number; customer_name: string | null; invoice_no: string | null; created_at: string }[]>("/orders?limit=50")) ?? [];
}
