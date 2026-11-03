import Link from "next/link";
import { HeroSlider } from "@/components/HeroSlider";

/** Hepsiburada düzeni: büyük banner (solda) + yan sütunda mini kampanya tile'ları. */
export default function Hero() {
  return (
    <section className="px-5 pb-2 pt-2 sm:px-10 sm:pb-3 sm:pt-3">
      <div className="mx-auto grid max-w-7xl gap-3 lg:grid-cols-3 lg:h-[198px]">
        <div className="lg:col-span-2 lg:h-full">
          <HeroSlider />
        </div>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 lg:grid-rows-2 lg:h-full">
          {/* Kampanya tile'ı — sayfadaki tek sıcak vurgu */}
          <Link
            href="/outlet"
            className="group flex h-full flex-col justify-between rounded-xl p-4 text-white transition hover:opacity-95"
            style={{ background: "linear-gradient(120deg, #f97316 0%, #c2410c 100%)" }}
          >
            <p className="text-[10px] font-black uppercase tracking-[0.18em] text-white/75">Outlet Kampanyası</p>
            <p className="mt-1 text-lg font-black leading-tight">%40'a kadar indirim</p>
            <p className="mt-0.5 text-xs font-bold text-white/85">
              Seçili ürünlerde <span className="inline-block transition-transform group-hover:translate-x-1">→</span>
            </p>
          </Link>
          {/* Minimal bilgi kartı */}
          <Link
            href="/sss"
            className="group flex h-full flex-col justify-between rounded-xl border border-black/10 bg-white p-4 transition hover:border-black/25"
          >
            <p className="text-[10px] font-black uppercase tracking-[0.18em] text-black/40">⛓️ Kripto ile Ödeme</p>
            <p className="mt-1 text-lg font-black leading-tight text-black">CSPR ile Öde</p>
            <p className="mt-0.5 text-xs font-bold text-black/45">
              Anında onay + otomatik fatura <span className="inline-block transition-transform group-hover:translate-x-1">→</span>
            </p>
          </Link>
        </div>
      </div>
    </section>
  );
}
