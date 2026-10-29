"use client";

import { CartProvider } from "@/components/CartProvider";
import SiteShell from "@/components/SiteShell";

export default function ShopLayout({ children }: { children: React.ReactNode }) {
  return (
    <CartProvider>
      <SiteShell>{children}</SiteShell>
    </CartProvider>
  );
}
