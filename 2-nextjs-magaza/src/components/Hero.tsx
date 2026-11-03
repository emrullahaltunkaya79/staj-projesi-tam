import Link from "next/link";
import { HeroSlider } from "@/components/HeroSlider";

/** Hepsiburada düzeni: büyük banner (solda) + yan sütunda mini kampanya tile'ları. */
export default function Hero() {
  return (
    <section className="px-5 pb-2 pt-2 sm:px-10 sm:pb-3 sm:pt-3">
      <div className="mx-auto grid max-w-7xl gap-3 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <HeroSlider />
        </div>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
          <Link
            href="/outlet"
            className="group flex flex-col justify-between rounded-xl p-4 text-white transition hover:opacity-95"
            style={{ background: "linear-gradient(120deg, var(--marka) 0%, #0f766e 100%)" }}
          >
            <p className="text-[10px] font-black uppercase tracking-[0.18em] opacity-80">Outlet Kampanyası</p>
            <p className="mt-1 text-lg font-black leading-tight">%40'a kadar indirim</p>
            <p className="mt-0.5 text-xs opacity-80">Seçili ürünlerde →</p>
          </Link>
          <Link
            href="/sss"
            className="group flex flex-col justify-between rounded-xl p-4 text-white transition hover:opacity-95"
            style={{ background: "linear-gradient(120deg, #475569 0%, #334155 100%)" }}
          >
            <p className="text-[10px] font-black uppercase tracking-[0.18em] opacity-80">⛓️ Kripto ile Ödeme</p>
            <p className="mt-1 text-lg font-black leading-tight">CSPR ile Öde, Anında Onayla</p>
            <p className="mt-0.5 text-xs opacity-80">Otomatik fatura + aynı gün kargo →</p>
          </Link>
        </div>
      </div>
    </section>
  );
}
