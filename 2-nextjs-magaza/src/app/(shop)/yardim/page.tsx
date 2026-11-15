"use client";

import { useState } from "react";
import Link from "next/link";
import { ChevronDown } from "lucide-react";
import { waLink } from "@/lib/iletisim";

export const metadataBaslik = "Yardım Merkezi";

const BOLUMLER: { baslik: string; sorular: { s: string; c: string }[] }[] = [
  {
    baslik: "Siparişler",
    sorular: [
      {
        s: "Siparişimi nereden takip ederim?",
        c: "Siparişlerim sayfasından tüm siparişlerinizi görüntüleyebilirsiniz. Kargoya verilen siparişlerde takip numarası ve kargo firması bağlantısı otomatik görünür.",
      },
      {
        s: "Siparişimi iptal edebilir miyim?",
        c: "Ödemesi henüz yapılmamış (Bekliyor durumundaki) siparişleri tek tıkla iptal edebilirsiniz. Ödenmiş ve kargolanmış siparişler için iade akışını kullanın.",
      },
      {
        s: "Sipariş numaramı nerede bulurum?",
        c: "Onay e-postasında ve Siparişlerim sayfasında ORD-XXXXXX biçiminde listelenir. Sipariş Sorgula sayfasından numara + e-posta ile de erişebilirsiniz.",
      },
    ],
  },
  {
    baslik: "Kargo & Teslimat",
    sorular: [
      {
        s: "Kargonuz ne zaman elime ulaşır?",
        c: "Ürün sayfasındaki tahmini teslimat aralığı, kargo kesim saati (14:00) öncesinde verilen siparişler için geçerlidir. Kargoya verildiğinde takip bağlantısı size iletilir.",
      },
      {
        s: "Ücretsiz kargo limiti nedir?",
        c: "2.000 ₺ ve üzeri siparişlerde kargo ücretsizdir. HepsiBirarada Plus üyeleri tutardan bağımsız ücretsiz kargo kazanır.",
      },
      {
        s: "Kapıda ödeme var mı?",
        c: "Evet — +25 ₺ hizmet bedeli ile kapıda nakit veya kredi kartıyla ödeme yapabilirsiniz.",
      },
    ],
  },
  {
    baslik: "İade & Değişim",
    sorular: [
      {
        s: "İade süresi ne kadar?",
        c: "Teslimden itibaren 14 gün içinde koşulsuz iade hakkınız var. Sipariş detayından iade talebi oluşturun; onaylandığında size özel iade kargo kodu verilir.",
      },
      {
        s: "İade kargo kodu nasıl kullanılır?",
        c: "Talebiniz onaylandığında sipariş sayfanızda IADE-XXXXXX biçiminde kod görünür. Paketi kodla birlikte anlaşmalı kargo şubesine teslim edin — ücret sizden alınmaz.",
      },
      {
        s: "İade param ne zaman yapılır?",
        c: "Kargo bize ulaştıktan sonra kontrol edilir ve 3-5 iş günü içinde ödeme yönteminize iade edilir.",
      },
    ],
  },
  {
    baslik: "Ödeme & Kupon",
    sorular: [
      {
        s: "Hangi ödeme yöntemlerini kullanabilirim?",
        c: "CSPR ile kripto ödeme, havale/EFT (%2 indirim) ve kapıda ödeme. Kuponlarınızı Kuponlarım sayfasından görüntüleyip checkout'ta uygulayabilirsiniz.",
      },
      {
        s: "Sadakat puanı nasıl çalışır?",
        c: "Her 10 ₺ ödemede 1 puan kazanırsınız (Plus üyeler 2 puan). Checkout'ta 1 puan = 1 ₺, ara toplamın yarısına kadar harcanabilir.",
      },
      {
        s: "Referans kodum var, nasıl kullanırım?",
        c: "Checkout'taki 'Arkadaşını Getir' alanına kodu girin — ilk siparişinizde 50 ₺ düşer, arkadaşınıza 500 puan eklenir.",
      },
    ],
  },
  {
    baslik: "Hesap & Güvenlik",
    sorular: [
      {
        s: "İki adımlı doğrulamayı nasıl açarım?",
        c: "Hesabım > İki Adımlı Doğrulama anahtarını açın. Sonraki girişte şifrenizden sonra e-postanıza gelen 6 haneli kodu girmeniz istenir.",
      },
      {
        s: "Hesabımı silebilir miyim?",
        c: "Hesabım sayfasının en altındaki 'Hesabı sil' akışıyla KVKK kapsamındaki unutulma hakkı çerçevesinde hesabınızı ve kişisel verilerinizi silebilirsiniz.",
      },
    ],
  },
];

export default function YardimMerkezi() {
  const [acik, setAcik] = useState<string | null>("Siparişler");

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10">
      <div className="mx-auto max-w-3xl">
        <div className="border-b border-black/20 pb-5">
          <h1 className="text-[15px] font-black uppercase tracking-wide text-black sm:text-[17px]">Yardım Merkezi</h1>
          <p className="mt-2 text-sm text-black/50">
            Aradığınızı bulamadınız mı?{" "}
            <Link href="/iletisim" className="font-bold text-black underline underline-offset-2">Bize yazın</Link> ya da{" "}
            <a href={waLink("Merhaba, yardıma ihtiyacım var.")} target="_blank" rel="noopener" className="font-bold text-black underline underline-offset-2">
              WhatsApp&apos;tan ulaşın
            </a>
            .
          </p>
        </div>

        <div className="mt-8 space-y-6">
          {BOLUMLER.map((bolum) => (
            <div key={bolum.baslik}>
              <h2 className="mb-2 text-[13px] font-black uppercase tracking-widest text-black">{bolum.baslik}</h2>
              <div className="divide-y divide-black/10 rounded-2xl border border-black/10 bg-white">
                {bolum.sorular.map((soru) => {
                  const anahtar = `${bolum.baslik}:${soru.s}`;
                  const acikMi = acik === anahtar;
                  return (
                    <div key={soru.s}>
                      <button
                        type="button"
                        onClick={() => setAcik(acikMi ? null : anahtar)}
                        className="flex w-full items-center justify-between gap-3 px-5 py-4 text-left text-[14px] font-bold text-black transition hover:bg-black/[.02]"
                      >
                        {soru.s}
                        <ChevronDown className={`h-4 w-4 shrink-0 text-black/35 transition-transform ${acikMi ? "rotate-180" : ""}`} />
                      </button>
                      {acikMi && <p className="px-5 pb-4 text-[13px] leading-relaxed text-black/65">{soru.c}</p>}
                    </div>
                  );
                })}
              </div>
            </div>
          ))}

          {/* Siparişe özel yardım — sipariş sayfasından geliyorsa numara hazır gelir */}
          <div className="rounded-2xl border border-black/10 bg-[#f8f8f8] p-5">
            <p className="text-sm font-bold text-black">Siparişinle ilgili bir sorun mu var?</p>
            <p className="mt-1 text-xs text-black/50">Sipariş numaranla birlikte bize ulaş — ekibimiz hızlıca yardımcı olur.</p>
            <Link href="/iletisim" className="mt-3 inline-flex h-10 items-center rounded-full bg-black px-5 text-xs font-bold text-white transition hover:bg-black/80">
              Siparişe özel destek talebi oluştur →
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
