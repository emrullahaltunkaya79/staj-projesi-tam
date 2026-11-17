"use client";

import type { ReactNode } from "react";

/**
 * Sonsuz marquee şerit — tüm yatay ürün listeleme alanlarının ortak omurgası.
 *
 * Nasıl çalışır:
 * - `ogeler` kopyaSayisi kadar tekrarlanır; iz `translateX(0 → -50%)` ile akar.
 *   -%50 noktası tam kopya sınırına denk geldiğinden (kopya sayısı her zaman
 *   çifttir) döngü boşluksuz sarar.
 * - Kartlar arası boşluk track'te değil HER KARTIN sağ kenar boşluğunda
 *   (örn. `mr-3`) taşınır — aksi halde döngü noktasında yarım boşluk kayar.
 * - Süre varsayılanı 26–30 sn bandında ürün sayısına göre ölçeklenir; `sure`
 *   ile geçersiz kılınabilir.
 * - Hover'da tüm şerit durur (globals.css: .marquee-alan:hover kuralı).
 * - Kenarlarda gradient maske; prefers-reduced-motion'da animasyon kapalı,
 *   normal yatay kaydırmaya döner.
 *
 * `kart` işlevi ikinci parametre olarak kopya indeksini alır; 0'dan büyük
 * kopyalarda odaklanabilirliği kapatmak için (tabIndex={-1}) kullanılır.
 */
export function MarqueeSerit<T>({
  ogeler,
  kart,
  sure,
  etiket,
}: {
  ogeler: T[];
  kart: (oge: T, kopya: number) => ReactNode;
  /** Döngü süresi (sn). Varsayılan: ürün sayısına göre 26–30 sn bandı. */
  sure?: number;
  /** Erişilebilirlik etiketi (şerit içeriğini özetleyen metin). */
  etiket?: string;
}) {
  if (ogeler.length === 0) return null;

  const kopyaSayisi = ogeler.length >= 7 ? 2 : 4;
  const dahiliSure = sure ?? Math.round(Math.min(30, Math.max(26, ogeler.length * 4)));
  const kopyalar = Array.from({ length: kopyaSayisi }, (_, i) => i);

  return (
    <div
      className="marquee-alan relative overflow-hidden motion-reduce:overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden
                 [mask-image:linear-gradient(to_right,transparent,black_5%,black_95%,transparent)]
                 [-webkit-mask-image:linear-gradient(to_right,transparent,black_5%,black_95%,transparent)]"
      role="region"
      aria-label={etiket}
    >
      <div className="marquee-iz flex w-max items-stretch" style={{ "--marquee-sure": `${dahiliSure}s` } as React.CSSProperties}>
        {kopyalar.map((kopya) => (
          <div key={kopya} aria-hidden={kopya > 0} className="flex items-stretch">
            {ogeler.map((oge) => kart(oge, kopya))}
          </div>
        ))}
      </div>
    </div>
  );
}
