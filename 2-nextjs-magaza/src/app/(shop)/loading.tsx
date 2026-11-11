/** Mağaza sayfaları için iskelet yükleme durumu — ürün kartı düzeninde titremesiz bekleme. */
export default function Yukleniyor() {
  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-7xl animate-pulse">
        <div className="h-5 w-48 rounded bg-black/10" />
        <div className="mt-8 grid grid-cols-2 gap-6 sm:grid-cols-4 lg:grid-cols-5">
          {Array.from({ length: 10 }).map((_, i) => (
            <div key={i}>
              <div className="aspect-square rounded-lg bg-black/[.06]" />
              <div className="mt-3 h-3 w-3/4 rounded bg-black/10" />
              <div className="mt-2 h-3 w-1/2 rounded bg-black/[.06]" />
              <div className="mt-2 h-4 w-1/3 rounded bg-black/10" />
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
