import type { Metadata, Viewport } from "next";
import { Inter } from "next/font/google";
import Script from "next/script";
import "./globals.css";
import { AuthProvider } from "@/components/AuthProvider";
import { FavoriProvider } from "@/components/FavoriProvider";
import { DilProvider } from "@/components/DilProvider";

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin"],
  display: "swap",
});

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL ?? "http://127.0.0.1:3001";

export const metadata: Metadata = {
  metadataBase: new URL(SITE_URL),
  title: {
    default: "HepsiBirarada — Teknoloji & Ofis",
    template: "%s | HepsiBirarada",
  },
  description:
    "HepsiBirarada — teknoloji ve ofis ürünlerinde güvenilir alışveriş. CSPR ile ödeme, aynı gün kargo, 14 gün koşulsuz iade.",
  keywords: ["e-ticaret", "teknoloji", "ofis", "elektronik", "CSPR", "kripto ödeme", "aynın gün kargo"],
  manifest: "/manifest.json",
  openGraph: {
    title: "HepsiBirarada — Teknoloji & Ofis",
    description: "HepsiBirarada'da teknoloji ve ofis ürünleri — CSPR ile kripto ödeme, aynı gün kargo, 14 gün koşulsuz iade.",
    type: "website",
    locale: "tr_TR",
  },
  robots: { index: true, follow: true },
};

export const viewport: Viewport = {
  themeColor: "#1a1a2e",
  width: "device-width",
  initialScale: 1,
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="tr" className={`${inter.variable} h-full antialiased`}>
      <body className="flex min-h-full flex-col font-sans">
        <AuthProvider>
          <FavoriProvider>
            <DilProvider>{children}</DilProvider>
          </FavoriProvider>
        </AuthProvider>
        {/* Service worker statik parçaları önbellekten sunar; geliştirmede hash'siz parçalar
            yüzünden kod değişiklikleri görünmez olur — bu yüzden yalnızca production'da kaydedilir */}
        {process.env.NODE_ENV === "production" ? (
          <Script id="sw-kayit" strategy="afterInteractive">
            {`if('serviceWorker' in navigator){navigator.serviceWorker.register('/sw.js').catch(function(){})}`}
          </Script>
        ) : (
          <Script id="sw-temizle" strategy="afterInteractive">
            {`if('serviceWorker' in navigator){navigator.serviceWorker.getRegistrations().then(function(r){r.forEach(function(k){k.unregister()})})}if(window.caches){caches.keys().then(function(a){a.forEach(function(n){caches.delete(n)})})}`}
          </Script>
        )}
      </body>
    </html>
  );
}
