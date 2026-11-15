import { NextResponse } from "next/server";
import { oturumKullanicisi } from "@/lib/oturum";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";

/**
 * Mağaza bildirim merkezi — giriş yapmış kullanıcı için son sipariş durumları.
 * Okunmadı sayısı istemcide, son görme zamanına göre hesaplanır.
 */
export async function GET() {
  const kullanici = await oturumKullanicisi();
  if (!kullanici?.email_verified) {
    return NextResponse.json({ data: { bildirimler: [] } });
  }

  const bildirimler: { baslik: string; metin: string; link: string; zaman: string }[] = [];

  try {
    const yanit = await fetch(`${BASE}/orders?email=${encodeURIComponent(kullanici.email)}&limit=6`, {
      headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      cache: "no-store",
    });
    if (yanit.ok) {
      const sonuc = await yanit.json();
      const liste: { order_no: string; status: string; status_label?: string; total: number; created_at: string }[] = sonuc.data ?? [];

      const ikonlar: Record<string, string> = {
        pending: "📦 Siparişiniz alındı", paid: "✅ Ödemeniz alındı", preparing: "📦 Hazırlanıyor",
        shipped: "🚚 Kargoya verildi", delivered: "📬 Teslim edildi", cancelled: "⛔ İptal edildi",
        return_requested: "↩️ İade talebiniz alındı", returned: "↩️ İade tamamlandı",
      };

      for (const s of liste) {
        bildirimler.push({
          baslik: `${ikonlar[s.status] ?? "📦"} ${s.order_no}`,
          metin: `${Number(s.total).toFixed(2)} ₺ · ${new Date(s.created_at).toLocaleDateString("tr-TR")}`,
          link: `/siparis/${s.order_no}`,
          zaman: s.created_at,
        });
      }
    }
  } catch {}

  bildirimler.sort((a, b) => (a.zaman < b.zaman ? 1 : -1));

  return NextResponse.json({ data: { bildirimler } });
}
