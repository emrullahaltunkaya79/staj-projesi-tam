/**
 * Oturum kimliği — httpOnly çerezdeki müşteri/satıcı token'ı arka ofiste doğrulanır.
 * next/headers içermez; proxy.ts tarafından da kullanılır.
 */

const BASE = process.env.BACKOFFICE_URL ?? "http://127.0.0.1:8000/api/v1";

export const OTURUM_CEREZI = "hba_oturum";
export const OTURUM_SURESI = 60 * 60 * 24 * 30;

export type OturumKullanicisi = {
  id: number;
  name: string;
  email: string;
  role: string;
  phone?: string | null;
  email_verified?: boolean;
};

export async function tokenKullanicisi(token: string | null | undefined): Promise<OturumKullanicisi | null> {
  if (!token) return null;
  try {
    const yanit = await fetch(`${BASE}/auth/me`, {
      headers: { Authorization: `Bearer ${token}`, Accept: "application/json" },
      cache: "no-store",
    });
    if (!yanit.ok) return null;
    return ((await yanit.json()).data as OturumKullanicisi) ?? null;
  } catch {
    return null;
  }
}

/** Mağaza müşterileri dışındaki hesaplar (yönetici/personel) satıcı panelini kullanır. */
export function saticiMi(kullanici: OturumKullanicisi | null): boolean {
  return !!kullanici && kullanici.role !== "musteri";
}

export const cerezAyarlari = {
  httpOnly: true,
  sameSite: "lax" as const,
  secure: process.env.NODE_ENV === "production",
  path: "/",
  maxAge: OTURUM_SURESI,
};
