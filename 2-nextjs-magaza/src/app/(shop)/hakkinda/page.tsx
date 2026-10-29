export const metadata = { title: "Hakkında — HepsiBirarada" };

export default function Hakkinda() {
  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-3xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[56px] font-black leading-none tracking-[-0.03em] sm:text-[84px]">Hakkında</h1>
        </div>

        <div className="mt-10 space-y-8 text-[15px] leading-relaxed text-black/70">
          <p className="text-lg font-medium text-black">
            Sizlerin daha parlak bir yaşam sürmeniz için sade ve zamansız ürünler tasarlıyorum.
          </p>

          <p>
            HepsiBirarada, 2018&apos;den beri Pittsburgh&apos;daki atölyemde küçük ölçekli üretim yapan bir tasarım
            markasıdır. Her ürün el yapımı prototiplerle başlar, günlük kullanımda test edilir ve yalnızca
            gerçekten işe yaradığında üretime alınır.
          </p>

          <p>
            <strong className="text-black">Neden sade?</strong> Çünkü iyi tasarım bağırarak değil, sessizce
            çalışarak kendini belli eder. Aydınlatma ürünlerimiz bir odanın karakterini değiştirir ama asla
            bakışları üzerlerine çekmez. Ofis aksesuarlarımız işinizi kolaylaştırır, masanızda yer kaplamaz.
          </p>

          <p>
            <strong className="text-black">Teknoloji altyapısı.</strong> Bu mağaza bir staj projesinin
            ürünüdür — stok, sipariş ve faturalama altyapısı ayrı bir Ön Muhasebe API&apos;sinden gelir.
            Sipariş verdiğinizde ürünler arka ofiste anında rezerve edilir, ödemeniz onaylandığında faturanız
            otomatik kesilir. Casper Network (CSPR) ile kripto ödeme kabul ediyoruz.
          </p>

          <div className="border-l-4 border-black bg-[#f8f8f8] px-6 py-5">
            <p className="text-sm font-bold text-black">HepsiBirarada Ekibi</p>
            <p className="mt-1 text-xs text-black/50">Kurucu & Tasarımcı</p>
          </div>
        </div>
      </div>
    </div>
  );
}
