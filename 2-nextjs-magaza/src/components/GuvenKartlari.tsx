import { Lock, Zap, RotateCcw, Package } from "lucide-react";

const KARTLAR = [
  {
    ikon: Lock,
    baslik: "Token ile Korunan API",
    aciklama: "Her istek Sanctum kimlik doğrulamasından geçer",
  },
  {
    ikon: Zap,
    baslik: "CSPR ile Anında Onay",
    aciklama: "Ödeme zincirde görüldüğünde fatura otomatik",
  },
  {
    ikon: Package,
    baslik: "30 Dk Rezervasyon",
    aciklama: "Stoğunuz sipariş boyunca güvence altında",
  },
  {
    ikon: RotateCcw,
    baslik: "14 Gün İade",
    aciklama: "Gerekçesiz iade, kargo ücreti bizden",
  },
];

export function GuvenKartlari({ kompakt = false, ince = false }: { kompakt?: boolean; ince?: boolean }) {
  if (ince) {
    // Tek satır şerit: ikon + başlik, kart kutuları yerine hafif ayraçlar
    return (
      <div className="grid grid-cols-2 gap-x-4 gap-y-1 border-y border-black/10 py-3 lg:grid-cols-4">
        {KARTLAR.map(({ ikon: Ikon, baslik }) => (
          <div key={baslik} className="flex items-center gap-2">
            <Ikon className="h-4 w-4 shrink-0 text-black/60" strokeWidth={2} />
            <span className="text-[12px] font-semibold text-black/70">{baslik}</span>
          </div>
        ))}
      </div>
    );
  }
  return (
    <div className={`grid grid-cols-2 gap-3 ${kompakt ? "" : "lg:grid-cols-4"}`}>
      {KARTLAR.map(({ ikon: Ikon, baslik, aciklama }) => (
        <div key={baslik} className="flex items-start gap-3 border border-black/10 bg-[#f8f8f8] p-4">
          <span className="flex h-8 w-8 shrink-0 items-center justify-center bg-black text-white">
            <Ikon className="h-4 w-4" strokeWidth={2} />
          </span>
          <div className="min-w-0">
            <div className="text-[12px] font-bold text-black">{baslik}</div>
            <div className="mt-0.5 text-[10px] leading-snug text-black/50">{aciklama}</div>
          </div>
        </div>
      ))}
    </div>
  );
}
