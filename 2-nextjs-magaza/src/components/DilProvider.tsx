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
  "banner_ilk": { tr: "İlk siparişine", en: "First order" },
  "banner_indirim": { tr: "%15 indirim", en: "15% off" },
  "banner_kupon": { tr: "Kupon:", en: "Code:" },
  "banner_kargo": { tr: "2.000 ₺ üzeri ÜCRETSİZ kargo!", en: "FREE shipping over ₺2,000!" },

  // Kabuk / header
  "logo_slogan": { tr: "Teknoloji & Ofis — her şey tek yerde", en: "Tech & Office — everything in one place" },
  "ara_aria": { tr: "Ürün, marka veya kategori ara", en: "Search products, brands or categories" },
  "son_aramalar": { tr: "Son Aramalar", en: "Recent Searches" },
  "populer_aramalar": { tr: "Popüler Aramalar", en: "Popular Searches" },
  "urunler_baslik": { tr: "Ürünler", en: "Products" },
  "markalar_baslik": { tr: "Markalar", en: "Brands" },
  "kuponlarim": { tr: "Kuponlarım", en: "My Coupons" },
  "hesabim": { tr: "Hesabım", en: "My Account" },
  "karanlik_mod": { tr: "Karanlık Mod", en: "Dark Mode" },
  "bildirimler": { tr: "Bildirimler", en: "Notifications" },
  "yeni_bildirim_yok": { tr: "Yeni bildirim yok.", en: "No new notifications." },
  "karanlige_gec": { tr: "Karanlığa geç", en: "Switch to dark" },
  "aydinliga_gec": { tr: "Aydınlığa geç", en: "Switch to light" },
  "tr_gec": { tr: "Türkçe'ye geç", en: "Türkçe'ye geç" },
  "en_gec": { tr: "Switch to English", en: "Switch to English" },

  // Alt menü
  "tum_kategoriler": { tr: "Tüm Kategoriler", en: "All Categories" },
  "flas_indirimler": { tr: "⚡ Flaş İndirimler", en: "⚡ Flash Deals" },
  "outlet": { tr: "Outlet", en: "Outlet" },
  "cok_satanlar": { tr: "★ Çok Satanlar", en: "★ Best Sellers" },
  "son_firsat": { tr: "Son Fırsat", en: "Last Deal" },
  "son_kalan": { tr: "Son Kalan", en: "Last Few Left" },
  "sezon": { tr: "Sezon", en: "Season" },
  "tum_urunleri_gor": { tr: "Tüm Ürünleri Gör", en: "View All Products" },
  "kategoriyi_gor": { tr: "Kategoriyi gör →", en: "View category →" },
  "urun_sayisi": { tr: "ürün", en: "products" },
  "marka_sayisi": { tr: "marka", en: "brands" },

  // Alt bilgi
  "footer_magaza": { tr: "Mağaza", en: "Shop" },
  "footer_kurumsal": { tr: "Kurumsal", en: "Corporate" },
  "footer_haberdar": { tr: "Kampanyalardan Haberdar Ol", en: "Get Campaign Updates" },
  "footer_bilgi": { tr: "Bilgi", en: "Info" },
  "footer_yasal": { tr: "Yasal", en: "Legal" },
  "sss": { tr: "Sıkça Sorulan Sorular", en: "FAQ" },
  "iade_degisim": { tr: "İade ve Değişim", en: "Returns & Exchange" },
  "kargo_teslimat": { tr: "Kargo ve Teslimat", en: "Shipping & Delivery" },
  "en_cok_satanlar": { tr: "★ En Çok Satanlar", en: "★ Best Sellers" },
  "son_kalan_olculer": { tr: "📏 Son Kalan Ölçüler", en: "📏 Last Few Left" },
  "sezon_urunleri": { tr: "🗓️ Sezon Ürünleri", en: "🗓️ Seasonal Products" },
  "cspr_odeme": { tr: "⛓️ CSPR ile kripto ödeme", en: "⛓️ Crypto payment with CSPR" },
  "guvenli_api": { tr: "🔒 Token ile korunan güvenli API", en: "🔒 Secure API protected by tokens" },
  "iade_14_gun": { tr: "↩️ 14 gün içinde ücretsiz iade — İade ve kargo koşulları", en: "↩️ Free returns within 14 days — Return & shipping terms" },
  "mesafeli_satis": { tr: "Mesafeli Satış Sözleşmesi", en: "Distance Sales Agreement" },
  "uyelik_sozlesmesi": { tr: "Üyelik Sözleşmesi", en: "Membership Agreement" },
  "kvkk_metni": { tr: "KVKK Aydınlatma Metni", en: "KVKK Privacy Notice" },
  "cerez_politikasi": { tr: "Çerez Politikası", en: "Cookie Policy" },
  "stok_siparis": { tr: "Stok & sipariş:", en: "Stock & orders:" },
  "gercek_zamanli": { tr: "Ön Muhasebe API ile gerçek zamanlı.", en: "real-time via the Back Office API." },
  "ac_kapa": { tr: "Kapat", en: "Close" },
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

  // <html lang> seçili dille senkron kalsın
  useEffect(() => {
    document.documentElement.lang = dil;
  }, [dil]);

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
