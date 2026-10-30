import type { ReactNode } from "react";

/** Yasal metin sayfaları için ortak düzen. */
export function SozlesmeSayfasi({
  baslik,
  guncelleme,
  children,
}: {
  baslik: string;
  guncelleme?: string;
  children: ReactNode;
}) {
  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-3xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[36px] font-black leading-none tracking-[-0.03em] sm:text-[54px]">{baslik}</h1>
          {guncelleme && (
            <p className="mt-3 text-xs font-medium text-black/40">Son güncelleme: {guncelleme}</p>
          )}
        </div>
        <div className="mt-10 space-y-8 text-[15px] leading-relaxed text-black/70 [&_h2]:mb-3 [&_h2]:text-xl [&_h2]:font-black [&_h2]:text-black [&_li]:mb-1.5 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-6">
          {children}
        </div>
      </div>
    </div>
  );
}
