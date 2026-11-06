import Link from "next/link";
import { RotateCcw, Truck, Wallet, RefreshCw, PackageCheck, ShieldCheck } from "lucide-react";
import {
  KARGO_BOLGELERI,
  UCRETSIZ_KARGO_LIMITI,
  KARGO_KESIM_SAATI,
  IADE_SURESI_GUN,
  teslimSuresi,
} from "@/lib/kargo";

export const metadata = {
  title: "İade, Değişim ve Kargo — HepsiBirarada",
  description: `${IADE_SURESI_GUN} gün içinde ücretsiz iade, kolay değişim, kargo ücretleri ve teslim süreleri — tüm koşullar tek sayfada.`,
};

const OZET = [
  { ikon: RotateCcw, baslik: `${IADE_SURESI_GUN} gün iade hakkı`, aciklama: "Teslim tarihinden itibaren, gerekçe göstermeden" },
  { ikon: Truck, baslik: "Ücretsiz iade kargosu", aciklama: "İade gönderiminin kargo ücreti bizden" },
  { ikon: Wallet, baslik: "3-5 iş günü para iadesi", aciklama: "İade onayından sonra (yasal üst sınır 14 gün)" },
  { ikon: RefreshCw, baslik: "Kolay değişim", aciklama: "Farklı beden veya renk için hızlı süreç" },
];

const IADE_ADIMLARI = [
  {
    baslik: "Talep oluşturun",
    metin: "Siparişlerim → sipariş sayfası → \"İade Talebi Oluştur\". Kalan iade süreniz ve son tarih bu sayfada yazar.",
  },
  {
    baslik: "Ürünü gönderin",
    metin: "Ürünü faturası ve tüm aksesuarlarıyla paketleyip sipariş numaranızı yazarak anlaşmalı kargomuza ücretsiz teslim edin.",
  },
  {
    baslik: "Kontrol ve onay",
    metin: "Ürün depomuza ulaştığında 1-2 iş günü içinde incelenir; onaylanınca stok ve faturanız otomatik güncellenir.",
  },
  {
    baslik: "Para iadesi",
    metin: "Onaydan sonra 3-5 iş günü içinde ödeme yönteminize iade edilir. CSPR ödemelerinde ödeme günündeki TL karşılığı esas alınır.",
  },
];

const KOSULLAR = [
  `Talep, teslim tarihinden itibaren ${IADE_SURESI_GUN} gün içinde oluşturulmalıdır.`,
  "Ürün kullanılmamış ve yeniden satılabilir durumda olmalı; fatura ve tüm aksesuarlarıyla gönderilmelidir.",
  "Ürünü incelemek için ambalajın açılması iade hakkınızı ortadan kaldırmaz.",
  "Kuponlu veya indirimli siparişlerde, ödediğiniz tutarın tamamı iade edilir.",
  "Kusurlu (ayıplı) ürünlerde yasal 2 yıllık garanti haklarınız saklıdır; kargo ve onarım masrafları bize aittir.",
];

const IADE_EDILEMEYENLER = [
  "Alıcıya özel üretilen veya kişiselleştirilen ürünler",
  "Ambalajı açılmış yazılım ve dijital içerikler (lisans kodu, hediye kartı vb.)",
  "Çabuk bozulma riski olan veya son kullanma tarihi geçen ürünler",
];

const KARGO_ADIMLARI = [
  { baslik: "Sipariş", metin: "Stok 30 dakika sizin adınıza ayrılır." },
  { baslik: "Hazırlık", metin: `${KARGO_KESIM_SAATI}:00'a kadar verilen siparişler aynı iş günü kargoya verilir.` },
  { baslik: "Kargoda", metin: "Takip numarası sipariş sayfanızda görünür." },
  { baslik: "Teslim", metin: "Bölgenize göre 1-3 iş günü içinde kapınızda." },
];

const SORULAR = [
  {
    s: "İade süremin dolup dolmadığını nereden görürüm?",
    c: "Teslim edilen her siparişin sayfasında kalan iade gününüz ve son tarih gösterilir. Süre dolduğunda iade butonu kapanır; kusurlu ürünlerde garanti haklarınız devam eder.",
  },
  {
    s: "Misafir olarak verdiğim siparişi nasıl iade ederim?",
    c: "Siparişlerim sayfasında e-postanızla siparişinizi bulun ve sipariş sayfasından talep oluşturun. Üyelik gerekmez.",
  },
  {
    s: "Ödemesini yapmadığım siparişi iptal edebilir miyim?",
    c: "Evet. Bekleyen siparişler sipariş sayfasından tek tıkla iptal edilir ve ayrılan stok anında serbest kalır. Ödenmiş siparişlerde iade süreci geçerlidir.",
  },
  {
    s: "Paket hasarlı gelirse ne yapmalıyım?",
    c: "Teslim alırken kargo görevlisine hasar tespit tutanağı tutturun ve iletişim formundan bize bildirin. Ürün, iade süresi beklenmeden ücretsiz yenilenir.",
  },
];

const BOLUM_BASLIK = "mb-4 text-[13px] font-black uppercase tracking-widest text-black";

export default function IadeDegisimSayfasi() {
  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-4xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[15px] font-black uppercase tracking-wide text-black sm:text-[17px]">İade, Değişim ve Kargo</h1>
          <p className="mt-2 text-sm text-black/50">
            Tüm koşullar tek sayfada. Yasal çerçeve için{" "}
            <Link href="/sozlesme/mesafeli-satis" className="font-semibold text-black underline underline-offset-2">Mesafeli Satış Sözleşmesi</Link>
            {" "}geçerlidir.
          </p>
        </div>

        <div className="mt-8 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {OZET.map(({ ikon: Ikon, baslik, aciklama }) => (
            <div key={baslik} className="rounded-xl border border-black/10 bg-[#f8f8f8] p-4">
              <Ikon className="h-5 w-5 text-black/70" />
              <p className="mt-3 text-[13px] font-black text-black">{baslik}</p>
              <p className="mt-1 text-[12px] leading-relaxed text-black/50">{aciklama}</p>
            </div>
          ))}
        </div>

        <section id="iade" className="mt-14 scroll-mt-40">
          <h2 className={BOLUM_BASLIK}>İade nasıl yapılır?</h2>
          <ol className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {IADE_ADIMLARI.map((a, i) => (
              <li key={a.baslik} className="flex gap-3">
                <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-black text-[12px] font-bold text-white">
                  {i + 1}
                </span>
                <div>
                  <p className="text-[14px] font-bold text-black">{a.baslik}</p>
                  <p className="mt-0.5 text-[13px] leading-relaxed text-black/60">{a.metin}</p>
                </div>
              </li>
            ))}
          </ol>

          <div className="mt-8 grid grid-cols-1 gap-6 md:grid-cols-2">
            <div className="rounded-xl border border-black/10 p-5">
              <p className="flex items-center gap-2 text-[13px] font-black text-black">
                <PackageCheck className="h-4 w-4" /> İade koşulları
              </p>
              <ul className="mt-3 space-y-2 text-[13px] leading-relaxed text-black/65">
                {KOSULLAR.map((k) => (
                  <li key={k} className="flex gap-2"><span className="text-black/30">•</span>{k}</li>
                ))}
              </ul>
            </div>
            <div className="rounded-xl border border-black/10 p-5">
              <p className="flex items-center gap-2 text-[13px] font-black text-black">
                <ShieldCheck className="h-4 w-4" /> İade edilemeyen ürünler
              </p>
              <ul className="mt-3 space-y-2 text-[13px] leading-relaxed text-black/65">
                {IADE_EDILEMEYENLER.map((k) => (
                  <li key={k} className="flex gap-2"><span className="text-black/30">•</span>{k}</li>
                ))}
              </ul>
              <p className="mt-3 text-[11px] text-black/40">Mesafeli Satış Sözleşmesi, madde 6.</p>
            </div>
          </div>
        </section>

        <section id="degisim" className="mt-14 scroll-mt-40">
          <h2 className={BOLUM_BASLIK}>Değişim</h2>
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div className="rounded-xl border border-black/10 p-5">
              <p className="text-[14px] font-bold text-black">Farklı beden veya renk</p>
              <p className="mt-1.5 text-[13px] leading-relaxed text-black/60">
                Mevcut ürün için iade talebi oluşturun, istediğiniz seçeneği hemen yeni sipariş olarak verin — stok 30 dakika
                sizin adınıza ayrılır. İade onaylandığında ilk ödemeniz iade edilir; böylece yeni ürününüzü beklemeden alırsınız.
              </p>
            </div>
            <div className="rounded-xl border border-black/10 p-5">
              <p className="text-[14px] font-bold text-black">Kusurlu veya hasarlı ürün</p>
              <p className="mt-1.5 text-[13px] leading-relaxed text-black/60">
                <Link href="/iletisim" className="font-semibold text-black underline underline-offset-2">İletişim formundan</Link>
                {" "}bildirin; ürünü aynısıyla değiştirir ya da ücretini iade ederiz. Kargo ücreti her iki yönde de bizdendir.
              </p>
            </div>
          </div>
        </section>

        <section id="kargo" className="mt-14 scroll-mt-40">
          <h2 className={BOLUM_BASLIK}>Kargo ve teslimat</h2>
          <ol className="grid grid-cols-2 gap-4 md:grid-cols-4">
            {KARGO_ADIMLARI.map((a, i) => (
              <li key={a.baslik} className="border-t-2 border-black pt-3">
                <p className="text-[11px] font-bold text-black/40">{i + 1}. ADIM</p>
                <p className="mt-0.5 text-[14px] font-bold text-black">{a.baslik}</p>
                <p className="mt-1 text-[12px] leading-relaxed text-black/55">{a.metin}</p>
              </li>
            ))}
          </ol>

          <div className="mt-8 overflow-hidden rounded-xl border border-black/10">
            <table className="w-full text-left text-[13px]">
              <thead className="bg-[#f8f8f8] text-[11px] font-bold uppercase tracking-wider text-black/45">
                <tr>
                  <th className="px-4 py-3">Teslimat bölgesi</th>
                  <th className="px-4 py-3">Kargo ücreti</th>
                  <th className="px-4 py-3">Tahmini teslim</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-black/10">
                {KARGO_BOLGELERI.map((b) => (
                  <tr key={b.id}>
                    <td className="px-4 py-2.5 font-medium text-black">{b.ad}</td>
                    <td className="px-4 py-2.5 tabular-nums text-black/70">{b.fiyat} ₺</td>
                    <td className="px-4 py-2.5 text-black/70">{teslimSuresi(b.gun)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
            <p className="border-t border-black/10 bg-emerald-50 px-4 py-3 text-[12px] font-semibold text-emerald-800">
              {UCRETSIZ_KARGO_LIMITI.toLocaleString("tr-TR")} ₺ ve üzeri siparişlerde tüm bölgelere kargo ücretsiz. FREEKARGO kuponu ile her siparişte ücretsiz.
            </p>
          </div>

          <ul className="mt-6 space-y-2 text-[13px] leading-relaxed text-black/60">
            <li className="flex gap-2"><span className="text-black/30">•</span>Hafta sonu ve resmi tatillerde kargo çıkışı yapılmaz; süreler iş günü üzerinden hesaplanır.</li>
            <li className="flex gap-2"><span className="text-black/30">•</span>Siparişinizi Siparişlerim sayfasından veya sayfanın altındaki Kargo Takip kutusundan sipariş numaranızla izleyebilirsiniz.</li>
          </ul>
        </section>

        <section className="mt-14">
          <h2 className={BOLUM_BASLIK}>Sık sorulanlar</h2>
          <div className="divide-y divide-black/10 border-y border-black/10">
            {SORULAR.map((q) => (
              <details key={q.s} className="group py-4">
                <summary className="flex cursor-pointer list-none items-center justify-between gap-4 text-[14px] font-bold text-black">
                  {q.s}
                  <span className="text-black/30 transition group-open:rotate-45">+</span>
                </summary>
                <p className="mt-2 text-[13px] leading-relaxed text-black/60">{q.c}</p>
              </details>
            ))}
          </div>
        </section>

        <div className="mt-12 flex flex-wrap items-center gap-3">
          <Link href="/siparislerim" className="flex h-11 items-center rounded-full bg-black px-6 text-sm font-bold text-white transition hover:bg-black/80">
            Siparişlerime git
          </Link>
          <Link href="/iletisim" className="flex h-11 items-center rounded-full border border-black/15 px-6 text-sm font-bold text-black/70 transition hover:border-black hover:text-black">
            Bize ulaşın
          </Link>
        </div>
      </div>
    </div>
  );
}
