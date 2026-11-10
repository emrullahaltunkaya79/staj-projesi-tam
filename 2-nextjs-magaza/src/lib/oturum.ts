import { cookies } from "next/headers";
import type { NextRequest } from "next/server";
import { timingSafeEqual } from "crypto";
import { OTURUM_CEREZI, tokenKullanicisi, saticiMi } from "./kimlik";
import { siparis, type Siparis } from "./backoffice";

export async function oturumKullanicisi() {
  return tokenKullanicisi((await cookies()).get(OTURUM_CEREZI)?.value);
}

function esit(a: string, b: string): boolean {
  const x = Buffer.from(a);
  const y = Buffer.from(b);
  return x.length === y.length && timingSafeEqual(x, y);
}

/**
 * Sipariş numarası sıralı olduğu için tek başına yetki vermez. Erişim:
 * siparişe özel anahtar (onay bağlantısı / sorgulama) veya siparişin sahibi ya da satıcı oturumu.
 */
export async function siparisErisimi(no: string, anahtar?: string | null): Promise<{ siparis: Siparis | null; izinli: boolean }> {
  const s = await siparis(no);
  if (!s) return { siparis: null, izinli: false };

  if (anahtar && s.access_key && esit(anahtar, s.access_key)) return { siparis: s, izinli: true };

  const kullanici = await oturumKullanicisi();
  // E-posta eşleşmesi yalnızca doğrulanmış hesaplarda yetki verir (başkasının e-postasıyla kayıt olunamasın)
  const sahibi = !!kullanici?.email_verified && !!s.customer_email
    && kullanici.email.toLowerCase() === s.customer_email.toLowerCase();

  return { siparis: s, izinli: sahibi || saticiMi(kullanici) };
}

/** API çağrılarında anahtar başlıkta (fetch) veya sorgu parametresinde (indirme bağlantısı) gelir. */
export async function siparisYetkisi(request: NextRequest, no: string): Promise<Siparis | null> {
  const anahtar = request.headers.get("x-siparis-anahtari") ?? request.nextUrl.searchParams.get("k");
  const { siparis: s, izinli } = await siparisErisimi(no, anahtar);
  return izinli ? s : null;
}
