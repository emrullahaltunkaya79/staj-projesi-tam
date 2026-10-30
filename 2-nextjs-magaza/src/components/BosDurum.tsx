import Link from "next/link";
import type { LucideIcon } from "lucide-react";
import { ShoppingCart, Heart, SearchX } from "lucide-react";

/** Boş durum illüstrasyonu + aksiyon butonu (sepet/favori/arama). */
export function BosDurum({
  tur,
  baslik,
  aciklama,
}: {
  tur: "sepet" | "favori" | "arama";
  baslik?: string;
  aciklama?: string;
}) {
  const cerceve: Record<string, { ikon: LucideIcon; b: string; a: string; cta: string; href: string }> = {
    sepet: {
      ikon: ShoppingCart,
      b: baslik ?? "Sepetiniz henüz boş",
      a: aciklama ?? "Kataloğumuzdan ürün ekleyerek alışverişe başlayın.",
      cta: "Alışverişe Başla",
      href: "/",
    },
    favori: {
      ikon: Heart,
      b: baslik ?? "Favori listeniz boş",
      a: aciklama ?? "Beğendiğiniz ürünlere kalp ikonuna dokunarak burada toplayın.",
      cta: "Ürünlere Göz At",
      href: "/",
    },
    arama: {
      ikon: SearchX,
      b: baslik ?? "Sonuç bulunamadı",
      a: aciklama ?? "Farklı bir arama terimi deneyin veya kategorilerden göz atın.",
      cta: "Tüm Ürünler",
      href: "/",
    },
  };
  const c = cerceve[tur];
  const Ikon = c.ikon;

  return (
    <div className="flex flex-col items-center py-16 text-center">
      <svg width="140" height="110" viewBox="0 0 140 110" fill="none" aria-hidden="true">
        <ellipse cx="70" cy="98" rx="46" ry="7" fill="currentColor" opacity="0.07" />
        <rect x="24" y="18" width="92" height="66" rx="12" fill="currentColor" opacity="0.05" />
        <rect x="34" y="8" width="52" height="8" rx="4" fill="currentColor" opacity="0.12" />
        <g transform="translate(50 32) scale(1.05)" className="text-black/45">
          <Ikon className="h-10 w-10" x="10" y="4" />
        </g>
      </svg>
      <h3 className="mt-6 text-lg font-black">{c.b}</h3>
      <p className="mt-2 max-w-xs text-sm text-black/45">{c.a}</p>
      <Link
        href={c.href}
        className="mt-6 rounded-full bg-[var(--marka)] px-7 py-3 text-xs font-bold text-white transition hover:opacity-90 active:scale-95"
      >
        {c.cta} →
      </Link>
    </div>
  );
}
