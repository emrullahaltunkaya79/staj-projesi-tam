/**
 * Arka ofis (Ön Muhasebe) API istemcisi — sunucu tarafında çalışır.
 * Tüm katalog/stok/sipariş verisi tek kaynaktan (arka ofis) gelir.
 */

import { KDV_ORANI } from "./fiyat";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

export type Urun = {
  id: number;
  code: string | null;
  barcode: string | null;
  name: string;
  brand?: string | null;
  model?: string | null;
  specs?: { k: string; v: string }[] | null;
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
  varyant_adlari?: string[];
  varyant_min_stok?: number | null;
  varyantlar?: VaryantOzeti[];
};

/** API varyant özeti — ad, tür (renk | beden) ve stok durumu. */
export type VaryantOzeti = {
  ad: string;
  tur: string;
  stokta: boolean;
};

/** İndirim uygulanmış nihai fiyat (API final_price vermezse kendisi hesaplar). */
export function indirimliFiyat(u: Pick<Urun, "sale_price" | "discount_percent" | "final_price">): number {
  if (u.final_price != null) return u.final_price;
  const oran = u.discount_percent ?? 0;
  return oran > 0 ? Math.round(u.sale_price * (100 - oran)) / 100 : u.sale_price;
}

export type Siparis = {
  order_no: string;
  /** Sipariş erişim anahtarı — yalnızca sunucuda karşılaştırılır, tarayıcıya gönderilmez */
  access_key?: string;
  status: string;
  status_label?: string;
  customer_name?: string | null;
  customer_email?: string | null;
  customer_phone?: string | null;
  shipping_address?: string | null;
  fatura?: { tipi: string; unvan: string | null; vergi_dairesi: string | null; vergi_no: string | null; adres: string | null };
  subtotal?: number;
  tax_rate?: number;
  tax_amount?: number;
  total: number;
  iade_talebi?: IadeTalebi | null;
  iade_tutari?: number | null;
  created_at?: string;
  expires_at?: string | null;
  delivered_at?: string | null;
  /** Teslimden itibaren 14 günlük iade hakkının son anı */
  iade_son_tarih?: string | null;
  invoice_no?: string | null;
  tracking_no?: string | null;
  tracking_url?: string | null;
  items: { product_id: number; product_name: string; quantity: number; unit_price: number; line_total: number; variant_id?: number | null }[];
};

export type IadeTalebi = {
  neden: string;
  neden_etiketi: string;
  aciklama: string | null;
  kismi: boolean;
  kalemler: { sira: number; product_name: string; quantity: number; unit_price: number }[];
  tarih: string;
};

/** Base64 data URI'ler sayfa boyutunu ~24MB'a çıkarıyordu; görsel /api/img/{id} proxy'sinden akar. */
function gorselProxy(p: Urun): Urun {
  // API artık base64 yerine 'ok' işareti döner — görsel proxy URL'sine çevrilir
  return p.image_url ? { ...p, image_url: `/api/img/${p.id}` } : p;
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

export type AramaSonucu = {
  urunler: Urun[];
  toplam: number;
  markalar: Record<string, number>;
  kategoriler: Record<string, number>;
};

/** Sunucu tarafı arama: FULLTEXT + sayfalama + sıralama (10K+ ürün için ölçeklenir). */
export async function urunAra(
  terim: string,
  sirala: string,
  sayfa: number,
  perPage = 24,
): Promise<AramaSonucu> {
  try {
    const sonuc = await istek(
      `/products/arama?q=${encodeURIComponent(terim)}&sirala=${encodeURIComponent(sirala)}&sayfa=${sayfa}&per_page=${perPage}`,
    );
    return {
      urunler: (sonuc.data ?? []).map(gorselProxy),
      toplam: sonuc.meta?.toplam ?? 0,
      markalar: sonuc.meta?.markalar ?? {},
      kategoriler: sonuc.meta?.kategoriler ?? {},
    };
  } catch {
    return { urunler: [], toplam: 0, markalar: {}, kategoriler: {} };
  }
}

/** "Bu ürünü alanlar şunu da aldı" — gerçek fatura satırlarından ortak satın alma önerisi. */
export async function ayniAldilar(id: number): Promise<Urun[]> {
  try {
    const sonuc = await istek(`/products/${id}/also-bought`);
    return (sonuc.data ?? []).map(gorselProxy);
  } catch {
    return [];
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

export type SiparisVerisi = {
  name: string;
  email: string;
  phone: string;
  address: string;
  items: { product_id: number; quantity: number; variant_id?: number }[];
  externalRef: string;
  fatura: { tipi: "bireysel" | "kurumsal"; unvan?: string; vergiDairesi?: string; vergiNo?: string; adres?: string };
};

/** Idempotent müşteri + external_ref'li sipariş oluşturur. KDV oranı mağazada sabittir (%20). */
export async function siparisOlustur(veri: SiparisVerisi): Promise<{ orderNo: string; accessKey: string; message: string }> {
  const musteri = await istek("/customers", {
    method: "POST",
    body: JSON.stringify({ name: veri.name, email: veri.email, type: "musteri" }),
  });

  const sonuc = await istek("/orders", {
    method: "POST",
    body: JSON.stringify({
      contact_id: musteri.data.contact_id,
      tax_rate: KDV_ORANI,
      items: veri.items,
      shipping_address: veri.address,
      external_ref: veri.externalRef,
      customer_email: veri.email,
      customer_name: veri.name,
      customer_phone: veri.phone,
      fatura_tipi: veri.fatura.tipi,
      ...(veri.fatura.tipi === "kurumsal"
        ? { fatura_unvan: veri.fatura.unvan, vergi_dairesi: veri.fatura.vergiDairesi, vergi_no: veri.fatura.vergiNo }
        : {}),
      ...(veri.fatura.adres ? { fatura_adresi: veri.fatura.adres } : {}),
    }),
  });

  if (!sonuc.data?.order_no) {
    throw new Error(sonuc.message ?? "Sipariş oluşturulamadı.");
  }

  return { orderNo: sonuc.data.order_no, accessKey: sonuc.data.access_key, message: sonuc.message };
}

export async function ode(orderNo: string): Promise<{ message: string; invoice_no?: string }> {
  const sonuc = await istek(`/orders/${orderNo}/pay`, {
    method: "POST",
    body: JSON.stringify({ payment_method: "cspr" }),
  });

  return { message: sonuc.message ?? "Ödeme işlendi.", invoice_no: sonuc.data?.invoice_no };
}
