export default function NotFound() {
  return (
    <div className="flex min-h-[60vh] flex-col items-center justify-center px-5 text-center">
      <div className="relative">
        <span className="text-[120px] font-black leading-none text-black/5 sm:text-[160px]">404</span>
        <span className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-5xl">🤷</span>
      </div>
      <h1 className="mt-2 text-2xl font-black text-black sm:text-3xl">Sayfa Bulunamadı</h1>
      <p className="mt-3 max-w-sm text-sm leading-relaxed text-black/50">
        Aradığınız sayfa taşınmış, silinmiş ya da hiç var olmamış olabilir. Endişelenmeyin — mağaza hâlâ dolu!
      </p>
      <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
        <a href="/" className="flex h-11 items-center rounded-full bg-black px-6 text-sm font-bold text-white transition hover:bg-black/80 active:scale-95">
          ← Ana Sayfaya Dön
        </a>
        <a href="/kategori/Elektronik" className="flex h-11 items-center rounded-full border border-black/15 px-6 text-sm font-bold text-black/60 transition hover:border-black hover:text-black">
          Ürünlere Göz At
        </a>
      </div>
    </div>
  );
}
