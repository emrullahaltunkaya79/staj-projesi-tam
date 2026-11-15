import { NextRequest, NextResponse } from "next/server";
import { urunlerTam, siparisOlustur } from "@/lib/backoffice";
import { kdvDahil } from "@/lib/fiyat";
import { KARGO_BOLGELERI, UCRETSIZ_KARGO_LIMITI } from "@/lib/kargo";
import { kargoBolgesi } from "@/lib/adres";

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";
const TOKEN = process.env.BACKOFFICE_TOKEN ?? "";
const fmt = (v: number) => new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(v);

/** Ödeme yöntemi kuralları — yalnızca sunucu uygular (istemciden gelen tutar okunmaz) */
const KAPIDA_BEDEL = 25; // Kapıda ödeme hizmet bedeli (₺, KDV dahil)
const HAVALE_INDIRIM_ORANI = 0.02; // Havale/EFT iskonto oranı

/**
 * Sipariş fiyatlandırması GÜVENİLİR SUNUCUDA yapılır: müşteri yalnızca ürün/sepet
 * bilgisi + kupon kodu gönderir. Kalem fiyatları arka ofis verisinden hesaplanır;
 * kupon indirimi ve kargo ücreti arka ofis siparişine işlenir. İstemciden gelen
 * hiçbir fiyat kabul edilmez (fiyat manipülasyonu engellenir).
 */
export async function POST(request: NextRequest) {
  try {
    const govde = await request.json();

    const zorunlu = ["name", "email", "phone", "address", "items"] as const;
    for (const alan of zorunlu) {
      if (!govde?.[alan]) {
        return NextResponse.json({ hata: `"${alan}" alanı zorunludur.` }, { status: 422 });
      }
    }

    // 1) Ürünler arka ofisten doğrulanır — istemci fiyatları hiç okunmaz.
    //    Tam katalog kullanılır: katalogda gizli ürünler (Plus üyeliği) de çözümlenir.
    const katalog = await urunlerTam();
    const harita = new Map(katalog.map((u) => [u.id, u]));

    let genelToplamDahil = 0;
    const satirlar: { product_id: number; quantity: number; variant_id?: number }[] = [];
    for (const i of govde.items as { product_id: number; quantity: number; variant_id?: number }[]) {
      const urun = harita.get(Number(i.product_id));
      if (!urun) throw new Error("Sepetinizdeki bir ürün artık mevcut değil.");
      // Ön siparişe açık ürün stok girişi bekler — tükendi sayılmaz
      if (!urun.in_stock && !urun.pre_order) throw new Error(`"${urun.name}" tükendi.`);

      genelToplamDahil += kdvDahil(urun.final_price ?? urun.sale_price) * Number(i.quantity);
      satirlar.push({
        product_id: Number(i.product_id),
        quantity: Number(i.quantity),
        ...(i.variant_id ? { variant_id: Number(i.variant_id) } : {}),
      });
    }
    genelToplamDahil = Math.round(genelToplamDahil * 100) / 100;

    // 2) Kargo ücreti: bölge adres bilgisinden (il/ilçe) türetilir
    const bolge = KARGO_BOLGELERI.find(
      (b) => b.id === kargoBolgesi(String(govde.il ?? ""), String(govde.ilce ?? "")),
    ) ?? null;
    let kargo = bolge ? bolge.fiyat : 0;
    if (genelToplamDahil >= UCRETSIZ_KARGO_LIMITI) kargo = 0;

    // Üyelik paketi (Plus) indirim dışıdır: kupon/kampanya tabanı bu kalemleri içermez
    const uyelikTutari = satirlar.reduce((t, s) => {
      const urun = harita.get(s.product_id)!;
      return urun.code === "PLUS-1YIL" ? t + (urun.final_price ?? urun.sale_price) * s.quantity : t;
    }, 0);
    const uyelikmi = (s: { product_id: number }) => harita.get(s.product_id)!.code === "PLUS-1YIL";

    // 3) Kupon: kod istemciden gelir, indirim arka ofiste doğrulanır — üyelik tutarı tabana dahil değil
    const couponCode = String(govde.couponCode ?? "").trim().toUpperCase();
    let indirim = 0;
    if (couponCode) {
      const kuponTabani = Math.max(0, genelToplamDahil - uyelikTutari);
      const yanit = await fetch(`${BASE}/coupons/validate`, {
        method: "POST",
        headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json", "Content-Type": "application/json" },
        body: JSON.stringify({ code: couponCode, cart_total: kuponTabani }),
      });
      const d = await yanit.json().catch(() => ({}));
      if (!d.valid) throw new Error(d.message ?? "Kupon geçersiz.");
      indirim = Number(d.data?.discount ?? 0);
      if (d.data?.type === "free_shipping") kargo = 0;
    }

    // 3b) Referans kodu: davet programı — geçerliyse 50 ₺ indirim (davet edenin kendi kodu geçersiz)
    const referansKodu = String(govde.referansKodu ?? "").trim().toUpperCase();
    let referansIndirimi = 0;
    if (referansKodu) {
      const yanit = await fetch(`${BASE}/referans/${encodeURIComponent(referansKodu)}?eposta=${encodeURIComponent(String(govde.email))}`, {
        headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      });
      const d = await yanit.json().catch(() => ({}));
      if (d.data?.gecerli && !d.data.kendi_kodu) {
        referansIndirimi = Math.min(50, Math.max(0, genelToplamDahil - indirim));
      }
    }

    // 3c) Paket kampanyaları: "X Al Y Öde" + "Eşik üstü yarı fiyat".
    //     Sepet-geneli (urun_id null) modda farklı ürünlerin adetleri TOPLANIR;
    //     bedava birimler en ucuzdan seçilir. Üyelik paketi kampanya dışıdır.
    let kampanyaIndirimi = 0;
    try {
      const kampanyaYanit = await fetch(`${BASE}/campaigns`, {
        headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      });
      const kampanyaData = await kampanyaYanit.json().catch(() => ({}));
      type Kampanya = { tur: string; urun_id: number | null; al_adet: number; ode_adet: number; esik: number };
      const aktifler: Kampanya[] = kampanyaData.data ?? [];
      for (const k of aktifler) {
        if (k.tur === "x_al_y_ode") {
          const al = Math.max(2, Number(k.al_adet) || 3);
          const ode = Math.max(1, Math.min(al - 1, Number(k.ode_adet) || al - 1));
          // birimleri topla: [fiyat, fiyat, ...] (adet kadar tekrar)
          const birimler: number[] = [];
          for (const s of satirlar) {
            if (uyelikmi(s)) continue;
            if (k.urun_id && s.product_id !== k.urun_id) continue;
            const urun = harita.get(s.product_id)!;
            const birim = urun.final_price ?? urun.sale_price;
            for (let x = 0; x < Number(s.quantity); x++) birimler.push(birim);
          }
          const toplamAdet = birimler.length;
          const ucretsiz = Math.floor(toplamAdet / al) * (al - ode);
          if (ucretsiz > 0) {
            birimler.sort((a, b) => a - b); // en ucuz birimler bedava
            kampanyaIndirimi += birimler.slice(0, ucretsiz).reduce((t, f) => t + f, 0);
          }
        } else if (k.tur === "esik_yarim" && k.urun_id) {
          const ara = satirlar.reduce((t, s) => {
            const urun = harita.get(s.product_id)!;
            return t + (urun.final_price ?? urun.sale_price) * s.quantity;
          }, 0);
          if (ara - indirim >= Number(k.esik || 0)) {
            for (const s of satirlar) {
              if (s.product_id !== k.urun_id || uyelikmi(s)) continue;
              const urun = harita.get(s.product_id)!;
              kampanyaIndirimi += (urun.final_price ?? urun.sale_price) * s.quantity * 0.5;
            }
          }
        }
      }
      kampanyaIndirimi = Math.round(kampanyaIndirimi * 100) / 100;
    } catch {}

    // 3d) Plus üyeliği: aktifse kargo her koşulda ücretsiz (arka ofisten doğrulanır)
    try {
      const plusYanit = await fetch(`${BASE}/plus-durum?email=${encodeURIComponent(String(govde.email))}`, {
        headers: { Authorization: `Bearer ${TOKEN}`, Accept: "application/json" },
      });
      const plusData = await plusYanit.json().catch(() => ({}));
      if (plusData.data?.aktif) kargo = 0;
    } catch {}

    // 4) Ödeme yöntemi: kapıda ödeme +hizmet bedeli, havale/EFT −%2 indirim (sunucu uygular)
    const yontem = ["cspr", "kapida", "havale"].includes(String(govde.odemeYontemi)) ? String(govde.odemeYontemi) : "cspr";

    // 5) Ödenecek tutar (KDV dahil) → kalem net fiyatları bu tutara ölçeklenir;
    //    böylece arka ofisteki sipariş/fatura tutarı müşterinin ödediğiyle birebir olur.
    const havaleIndirimi = yontem === "havale" ? Math.round((genelToplamDahil - indirim - referansIndirimi - kampanyaIndirimi) * HAVALE_INDIRIM_ORANI * 100) / 100 : 0;
    const odenecek = Math.max(0, genelToplamDahil - indirim - referansIndirimi - kampanyaIndirimi - havaleIndirimi) + kargo + (yontem === "kapida" ? KAPIDA_BEDEL : 0);
    const oran = genelToplamDahil > 0 ? odenecek / genelToplamDahil : 1;
    const items = satirlar.map((s) => {
      const urun = harita.get(s.product_id)!;
      const net = urun.final_price ?? urun.sale_price;
      return { ...s, unit_price: Math.round(net * oran * 10000) / 10000 };
    });

    const notesNotlari = [
      ...(referansKodu && referansIndirimi > 0 ? [`Referans: ${referansKodu} (-${fmt(referansIndirimi)} TL)`] : []),
      ...(kampanyaIndirimi > 0 ? [`Paket kampanyası: -${fmt(kampanyaIndirimi)} TL`] : []),
      ...(couponCode ? [`Kupon ${couponCode}: -${fmt(indirim)} TL`] : []),
      ...(havaleIndirimi > 0 ? [`Havale indirimi: -${fmt(havaleIndirimi)} TL`] : []),
      ...(yontem === "kapida" ? [`Kapıda ödeme bedeli: +${fmt(KAPIDA_BEDEL)} TL`] : []),
      ...(kargo > 0 && bolge ? [`Kargo: ${fmt(kargo)} TL (${bolge.ad})`] : []),
    ].join(" | ");

    const sonuc = await siparisOlustur({
      name: String(govde.name),
      email: String(govde.email),
      phone: String(govde.phone),
      address: String(govde.address),
      items,
      externalRef: String(govde.externalRef ?? `NEXT-${Date.now()}`),
      notes: notesNotlari || undefined,
      hediyePaket: Boolean(govde.hediyePaket),
      hediyeNotu: govde.hediyeNotu ? String(govde.hediyeNotu).slice(0, 300) : null,
      odemeYontemi: yontem as "cspr" | "kapida" | "havale",
      puanKullan: Boolean(govde.puanKullan),
      fatura: {
        tipi: govde.fatura?.tipi === "kurumsal" ? "kurumsal" : "bireysel",
        ...(govde.fatura?.tipi === "kurumsal"
          ? {
              unvan: String(govde.fatura.unvan ?? ""),
              vergiDairesi: String(govde.fatura.vergiDairesi ?? ""),
              vergiNo: String(govde.fatura.vergiNo ?? ""),
            }
          : {}),
        ...(govde.fatura?.adres ? { adres: String(govde.fatura.adres) } : {}),
      },
    });

    return NextResponse.json({ orderNo: sonuc.orderNo, erisimAnahtari: sonuc.accessKey, message: sonuc.message });
  } catch (e) {
    const mesaj = e instanceof Error ? e.message : "Sipariş oluşturulamadı.";
    const dogrulama = mesaj.match(/"message":"([^"]+)"/);
    return NextResponse.json({ hata: dogrulama ? dogrulama[1] : mesaj }, { status: 502 });
  }
}
