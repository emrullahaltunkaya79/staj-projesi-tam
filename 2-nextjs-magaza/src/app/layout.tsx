import type { Metadata, Viewport } from "next";
import { Inter } from "next/font/google";
import "./globals.css";
import { AuthProvider } from "@/components/AuthProvider";
import { FavoriProvider } from "@/components/FavoriProvider";
import { DilProvider } from "@/components/DilProvider";

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin"],
  display: "swap",
});

export const metadata: Metadata = {
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
    description: "74 ürün · 9 kategori · CSPR ile ödeme · aynı gün kargo",
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
        <script
          dangerouslySetInnerHTML={{
            __html: `if('serviceWorker' in navigator){window.addEventListener('load',()=>{navigator.serviceWorker.register('/sw.js').catch(()=>{})})}`,
          }}
        />
      </body>
    </html>
  );
}
