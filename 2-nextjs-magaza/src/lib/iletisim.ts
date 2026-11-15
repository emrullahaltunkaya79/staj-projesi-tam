/** Destek kanalları — WhatsApp köprüsü. */
export const WHATSAPP_NUMARASI = "905321234567";

export const waLink = (metin: string) =>
  `https://wa.me/${WHATSAPP_NUMARASI}?text=${encodeURIComponent(metin)}`;
