import Link from "next/link";
import {
  Store, Ruler, Hourglass, Leaf, Sun, Snowflake, Flower2, ChevronRight,
  type LucideIcon,
} from "lucide-react";
import { MEVSIMLER, mevsimBul, type Mevsim } from "@/lib/sezonlar";

const MEVSIM_IKONLARI: Record<Mevsim, LucideIcon> = {
  ilkbahar: Flower2,
  yaz: Sun,
  sonbahar: Leaf,
  kis: Snowflake,
};

/** Kampanya koleksiyonları — Outlet, Son Kalan Ölçüler, Son Fırsat ve sezon vitrini için mini giriş kartları. */
export function KoleksiyonSeridi({
  outletSayisi,
  sonKalanSayisi,
  sonFirsatSayisi,
  sezonSayisi,
}: {
  outletSayisi: number;
  sonKalanSayisi: number;
  sonFirsatSayisi: number;
  sezonSayisi: number;
}) {
  const mevsim = mevsimBul();
  const SezonIkon = MEVSIM_IKONLARI[mevsim];

  const kartlar = [
    { href: "/outlet", ikon: Store, baslik: "Outlet", not: `${outletSayisi} ürün · %25+ indirim`, kirmizi: true },
    { href: "/son-kalan", ikon: Ruler, baslik: "Son Kalan Ölçüler", not: `${sonKalanSayisi} ürün · tükenen bedenler` },
    { href: "/son-firsat", ikon: Hourglass, baslik: "Son Fırsat", not: `${sonFirsatSayisi} ürün · son adetler` },
    { href: "/sezon", ikon: SezonIkon, baslik: `${MEVSIMLER[mevsim].ad} Ürünleri`, not: `${sezonSayisi} ürün · ${MEVSIMLER[mevsim].aciklama}` },
  ];

  return (
    <section className="px-5 pb-6 sm:px-10">
      <div className="mx-auto max-w-7xl">
        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
          {kartlar.map(({ href, ikon: Ikon, baslik, not, kirmizi }) => (
            <Link
              key={href}
              href={href}
              className={`group flex items-center gap-3 rounded-2xl border border-black/10 bg-white px-4 py-3 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg ${
                kirmizi ? "hover:border-red-600" : "hover:border-[var(--marka)]"
              }`}
            >
              <span
                className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-colors ${
                  kirmizi
                    ? "bg-red-50 text-red-600 group-hover:bg-red-600 group-hover:text-white"
                    : "bg-black/[.04] text-black/70 group-hover:bg-[var(--marka)] group-hover:text-white"
                }`}
              >
                <Ikon className="h-5 w-5" />
              </span>
              <span className="min-w-0">
                <span className={`block truncate text-[13px] font-bold ${kirmizi ? "text-red-600" : "text-black/75"}`}>{baslik}</span>
                <span className="block truncate text-[11px] font-medium text-black/35">{not}</span>
              </span>
              <ChevronRight className="ml-auto h-4 w-4 shrink-0 text-black/20 transition group-hover:translate-x-0.5 group-hover:text-[var(--marka)]" />
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
}
