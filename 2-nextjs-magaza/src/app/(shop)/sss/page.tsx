export const metadata = { title: "Sıkça Sorulan Sorular" };

const SORULAR = [
  {
    kategori: "Sipariş & Teslimat",
    maddeler: [
      {
        s: "Siparişim ne zaman kargoya verilir?",
        c: "14:00'a kadar verilen siparişler aynı gün kargoya verilir. 14:00 sonrası verilen siparişler bir sonraki iş günü ilk saatlerde gönderilir. Kargo süresi bölgenize göre 1-3 iş günüdür.",
      },
      {
        s: "Kargo ücreti ne kadar?",
        c: "Kargo ücreti bölgenize göre değişir (29₺ - 55₺). 2.000₺ ve üzeri siparişlerde kargo ücretsizdir. Ayrıca FREEKARGO kupon kodu ile her siparişte ücretsiz kargo alabilirsiniz.",
      },
      {
        s: "Siparişimi nasıl takip ederim?",
        c: "Sipariş verdikten sonra size özel takip bağlantısı oluşturulur. Siparişlerim sayfasından tüm siparişlerinizi görebilir, kargoya verildiğinde takip numarası ile kargo firmanın web sitesinden izleyebilirsiniz.",
      },
      {
        s: "Siparişimi iptal edebilir miyim?",
        c: "Evet! Ödeme yapılmamış (bekleyen) siparişleri sipariş sayfanızdan tek tıkla iptal edebilirsiniz — rezerve stok anında serbest bırakılır. Ödenmiş siparişler için 14 gün içinde iade hakkınız vardır.",
      },
    ],
  },
  {
    kategori: "Ödeme",
    maddeler: [
      {
        s: "CSPR ile nasıl ödeme yaparım?",
        c: "Sipariş verdikten sonra 'CSPR ile Öde' butonuna tıklayın. Açılan ekranda QR kod, alıcı adresi ve MEMO kodu gösterilir. Casper Wallet ile QR'ı okutun veya manuel gönderim yapın. Ödeme zincirde görüldüğünde siparişiniz otomatik onaylanır ve faturanız kesilir.",
      },
      {
        s: "Hangi kupon kodları aktif?",
        c: "Aktif kuponlar: ILKSIPARIS (%15, min 200₺), KAMPANYA10 (%10, min 500₺), FREEKARGO (ücretsiz kargo), MEBULA50 (50₺, min 400₺). Ödeme sayfasındaki kupon kutusuna kodu girip uygulayın.",
      },
      {
        s: "Ödemem yapılmasa ne olur?",
        c: "Siparişiniz 30 dakika boyunca stokları rezerve tutar. Bu süre içinde ödeme yapılmazsa sipariş otomatik iptal edilir ve stoklar serbest bırakılır. Herhangi bir ücret ödemezsiniz.",
      },
    ],
  },
  {
    kategori: "İade & Değişim",
    maddeler: [
      {
        s: "İade koşulları nelerdir?",
        c: "14 gün içinde koşulsuz iade hakkınız vardır. Ürünün kullanılmamış ve orijinal ambalajında olması yeterlidir. İade sürecinde kargo ücreti tarafımıza aittir.",
      },
      {
        s: "İade süreci nasıl işler?",
        c: "Sipariş sayfanızdan iade talebi oluşturun. İade faturası kesilir, ürün stoğa geri alınır ve ödemeniz CSPR adresinize iade edilir. Süreç genellikle 3-5 iş günü tamamlanır.",
      },
    ],
  },
  {
    kategori: "Hesap",
    maddeler: [
      {
        s: "Üye olmadan alışveriş yapabilir miyim?",
        c: "Evet! Üye olmadan da sipariş verebilirsiniz. Ancak üye olarak sipariş geçmişinizi görebilir, favori listeniz saklanır ve gelecekte hızlı checkout avantajı elde edersiniz.",
      },
      {
        s: "Şifremi unuttum, ne yapmalıyım?",
        c: "Giriş yapma ekranındaki 'Şifremi unuttum' bağlantısına tıklayın. E-posta adresinize sıfırlama bağlantısı gönderilir.",
      },
    ],
  },
];

export default function SSS() {
  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-3xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[48px] font-black leading-none tracking-[-0.03em] sm:text-[64px]">SSS</h1>
          <p className="mt-2 text-sm text-black/40">Sıkça sorulan sorular ve yanıtları</p>
        </div>

        <div className="mt-10 space-y-10">
          {SORULAR.map((grup) => (
            <div key={grup.kategori}>
              <h2 className="mb-4 text-xl font-black tracking-tight text-black">{grup.kategori}</h2>
              <div className="space-y-2">
                {grup.maddeler.map((m) => (
                  <details key={m.s} className="group border border-black/10 rounded-xl bg-white">
                    <summary className="flex cursor-pointer items-center justify-between px-5 py-4 text-[15px] font-bold text-black transition hover:text-indigo-600 [&::-webkit-details-marker]:hidden">
                      {m.s}
                      <span className="ml-3 shrink-0 text-2xl font-light text-black/20 transition-transform duration-300 group-open:rotate-45">+</span>
                    </summary>
                    <p className="border-t border-black/5 px-5 py-4 text-sm leading-relaxed text-black/60">{m.c}</p>
                  </details>
                ))}
              </div>
            </div>
          ))}
        </div>

        <div className="mt-16 rounded-2xl border border-black/10 bg-[#f8f8f8] p-6 text-center">
          <p className="text-sm font-bold text-black">Sorunuzun yanıtını bulamadınız mı?</p>
          <p className="mt-1 text-xs text-black/40">Sağ alttaki destek köpüğünden bize yazın veya <a href="/iletisim" className="font-bold text-indigo-600 underline underline-offset-4">iletişim sayfamızı</a> ziyaret edin.</p>
        </div>
      </div>
    </div>
  );
}
