import Link from "next/link";
import { Check } from "lucide-react";

const ADIMLAR = [
  { no: 1, ad: "Sepet", href: "/sepet" },
  { no: 2, ad: "Ödeme", href: "/odeme" },
  { no: 3, ad: "Tamamlandı", href: null },
];

/** Sepet → Ödeme → Tamamlandı adım göstergesi. */
export function OdemeAdimlari({ aktif }: { aktif: 1 | 2 | 3 }) {
  return (
    <div className="flex items-center justify-center gap-0">
      {ADIMLAR.map((adim, i) => {
        const tamam = adim.no < aktif;
        const suAn = adim.no === aktif;
        return (
          <div key={adim.no} className="flex items-center">
            {i > 0 && (
              <div className={`mx-2 h-px w-8 sm:mx-3 sm:w-14 ${tamam || suAn ? "bg-[var(--marka)]" : "bg-black/15"}`} />
            )}
            <div className="flex items-center gap-2">
              <span
                className={`flex h-7 w-7 items-center justify-center rounded-full border-2 text-xs font-black transition ${
                  tamam
                    ? "border-[var(--marka)] bg-[var(--marka)] text-white"
                    : suAn
                      ? "border-[var(--marka)] bg-white text-[var(--marka)]"
                      : "border-black/15 bg-white text-black/30"
                }`}
              >
                {tamam ? <Check className="h-3.5 w-3.5" /> : adim.no}
              </span>
              {adim.href && !suAn ? (
                <Link href={adim.href} className={`text-xs font-bold ${suAn ? "text-[var(--marka)]" : "text-black/50 hover:text-black"}`}>
                  {adim.ad}
                </Link>
              ) : (
                <span className={`text-xs font-bold ${suAn ? "text-[var(--marka)]" : tamam ? "text-black/60" : "text-black/30"}`}>
                  {adim.ad}
                </span>
              )}
            </div>
          </div>
        );
      })}
    </div>
  );
}
