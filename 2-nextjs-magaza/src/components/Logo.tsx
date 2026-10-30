/** HepsiBirarada logo işareti — üst üste binmiş üç kutu: "her şey bir arada". */
export function LogoIshari({ boyut = 28 }: { boyut?: number }) {
  return (
    <svg width={boyut} height={boyut} viewBox="0 0 32 32" fill="none" aria-hidden="true">
      <rect x="3" y="12" width="14" height="14" rx="3" fill="currentColor" opacity="0.35" />
      <rect x="9" y="8" width="14" height="14" rx="3" fill="currentColor" opacity="0.6" />
      <rect x="15" y="4" width="14" height="14" rx="3" fill="currentColor" />
    </svg>
  );
}

export function Logo({ koyu = false }: { koyu?: boolean }) {
  return (
    <span className={`flex items-center gap-2 ${koyu ? "text-white" : "text-black"}`}>
      <LogoIshari />
      <span className="text-lg font-black tracking-tight">
        HepsiBirarada<span className="text-[var(--marka-vurgu,#6366f1)]">.</span>
      </span>
    </span>
  );
}
