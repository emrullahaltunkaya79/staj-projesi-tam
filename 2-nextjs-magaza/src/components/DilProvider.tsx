"use client";

import { createContext, useContext, useEffect, useState } from "react";

export type Dil = "tr" | "en";

const CEVIRILER: Record<string, Record<Dil, string>> = {
  // Header / Nav
  "urun_ara": { tr: "Ürün ara…", en: "Search products…" },
  "giris_yap": { tr: "Giriş Yap", en: "Sign In" },
  "cikis_yap": { tr: "Çıkış Yap", en: "Sign Out" },
  "sepet": { tr: "Sepet", en: "Cart" },
  "favoriler": { tr: "Favorilerim", en: "Wishlist" },
  "siparislerim": { tr: "Siparişlerim", en: "My Orders" },
  "magaza": { tr: "Mağaza", en: "Shop" },
  "hakkinda": { tr: "Hakkında", en: "About" },
  "iletisim": { tr: "İletişim", en: "Contact" },

  // Ana sayfa
  "tum_urunler": { tr: "Tüm Ürünler", en: "All Products" },
  "urun": { tr: "ürün", en: "products" },
  "onerilen": { tr: "Önerilen", en: "Recommended" },
  "fiyat_artan": { tr: "₺ ↑", en: "₺ ↑" },
  "fiyat_azalan": { tr: "₺ ↓", en: "₺ ↓" },
  "yeni": { tr: "Yeni", en: "New" },
  "sadece_stokta": { tr: "Sadece stokta", en: "In stock only" },
  "filtreler": { tr: "Filtreler", en: "Filters" },
  "temizle": { tr: "Temizle", en: "Clear" },
  "kategoriler": { tr: "Kategoriler", en: "Categories" },
  "fiyat_araligi": { tr: "Fiyat Aralığı (₺)", en: "Price Range (₺)" },
  "stok_durumu": { tr: "Stok Durumu", en: "Stock Status" },
  "sadece_stokta_olanlar": { tr: "Sadece stokta olanlar", en: "In stock only" },

  // Ürün detay
  "sepete_ekle": { tr: "Sepete Ekle", en: "Add to Cart" },
  "tukendi": { tr: "Tükendi", en: "Sold Out" },
  "stokta": { tr: "Stokta", en: "In Stock" },
  "musait": { tr: "müsait", en: "available" },
  "degerlendirmeler": { tr: "Değerlendirmeler", en: "Reviews" },
  "bunlarda_begen": { tr: "Bunları da beğenebilirsiniz", en: "You may also like" },

  // Sepet/Checkout
  "sepetim": { tr: "Sepetim", en: "My Cart" },
  "sepetiniz_bos": { tr: "Sepetiniz boş.", en: "Your cart is empty." },
  "odeme": { tr: "Ödeme", en: "Checkout" },
  "odeme_gec": { tr: "Ödemeye Geç →", en: "Proceed to Checkout →" },
  "ara_toplam": { tr: "Ara Toplam", en: "Subtotal" },
  "kdv": { tr: "KDV", en: "VAT" },
  "genel_toplam": { tr: "Genel Toplam", en: "Grand Total" },
  "indirim_kuponu": { tr: "İndirim Kuponu", en: "Discount Coupon" },
  "uygula": { tr: "Uygula", en: "Apply" },
  "kaldir": { tr: "Kaldır", en: "Remove" },
  "kargo_bolgesi": { tr: "Kargo Bölgesi", en: "Shipping Region" },
  "ucretsiz_kargo": { tr: "Ücretsiz kargo", en: "Free shipping" },
  "siparisi_onayla": { tr: "Siparişi Onayla", en: "Place Order" },

  // Kampanya
  "banner": { tr: "İlk siparişine %15 indirim — Kupon: ILKSIPARIS · 2.000 ₺ üzeri ÜCRETSİZ kargo!", en: "15% off your first order — Code: ILKSIPARIS · FREE shipping over ₺2,000!" },
};

type DilBaglami = {
  dil: Dil;
  dilDegistir: (d: Dil) => void;
  cevir: (anahtar: string) => string;
};

const Baglam = createContext<DilBaglami | null>(null);
const ANAHTAR = "hba-dil";

export function DilProvider({ children }: { children: React.ReactNode }) {
  const [dil, setDil] = useState<Dil>("tr");

  useEffect(() => {
    const kayitli = localStorage.getItem(ANAHTAR) as Dil | null;
    if (kayitli === "tr" || kayitli === "en") setDil(kayitli);
  }, []);

  const dilDegistir = (d: Dil) => {
    setDil(d);
    localStorage.setItem(ANAHTAR, d);
  };

  const cevir = (anahtar: string): string => {
    return CEVIRILER[anahtar]?.[dil] ?? anahtar;
  };

  return (
    <Baglam.Provider value={{ dil, dilDegistir, cevir }}>
      {children}
    </Baglam.Provider>
  );
}

export function dil() {
  const baglam = useContext(Baglam);
  if (!baglam) throw new Error("dil() ancak DilProvider içinde kullanılabilir");
  return baglam;
}

export { CEVIRILER };
