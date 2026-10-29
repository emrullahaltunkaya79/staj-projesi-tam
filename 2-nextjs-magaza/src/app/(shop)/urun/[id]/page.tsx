import { urun, urunler } from "@/lib/backoffice";
import { SepeteEkleButonu } from "@/components/SepeteEkleButonu";
import { GuvenKartlari } from "@/components/GuvenKartlari";
import { UrunKarti } from "@/components/Catalog";
import { Yorumlar } from "@/components/Yorumlar";
import { GorselGaleri } from "@/components/GorselGaleri";
import { StokAciliyet } from "@/components/StokAciliyet";
import { KalpButonu } from "@/components/KalpButonu";
import { UrunDetayOzellikleri } from "@/components/UrunDetayOzellikleri";

const ACIKLAMALAR: Record<string, string> = {
  Bilgisayar: "Günlük iş akışınızı hızlandıran, ergonomik ve dayanıklı bilgisayar donanımları. İki yıl garanti dahil.",
  Elektronik: "Ses ve görüntü deneyiminizi yükselten, pil ömrü uzun, kompakt elektronik ürünler. Şarj kablosu kutu içeriğindedir.",
  Kırtasiye: "Ofis masanızın vazgeçilmezi, kaliteli malzemeden üretilmiş kırtasiye ürünleri. Toplu alımlarda stok fiyatı uygulanır.",
  Ofis: "Uzun çalışma saatlerini konforlu hale getiren, sağlam gövdeli ofis ürünleri. Kolay montaj kılavuzu kutudan çıkar.",
};

export default async function UrunSayfasi({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const u = await urun(Number(id));

  if (!u) {
    return (
      <div className="flex min-h-[40vh] items-center justify-center">
        <p className="text-sm text-black/50">Ürün bulunamadı.</p>
      </div>
    );
  }

  const aciklama = ACIKLAMALAR[u.category ?? ""] ?? "Sade ve zamansız tasarım, kaliteli malzeme.";
  const fiyat = new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2 }).format(u.sale_price);
  const puan = 4.2 + ((u.id * 7) % 8) / 10;
  const yorum = 37 + ((u.id * 13) % 480);
  const tumUrunler = await urunler();
  const benzerler = tumUrunler
    .filter((x) => x.category === u.category && x.id !== u.id)
    .slice(0, 3);

  return (
    <div className="px-5 pb-24 pt-10 sm:px-10"><div className="mx-auto max-w-7xl">
      <div className="px-5 pb-6 pt-8 sm:px-10">
        <div className="mx-auto max-w-7xl text-[13px] font-medium text-black/50">
          <a href="/" className="hover:underline underline-offset-4">Mağaza</a>
          <span className="mx-2">/</span>
          <span>{u.category ?? "Ürünler"}</span>
        </div>
      </div>

      <section className="px-5 pb-24 sm:px-10">
        <div className="mx-auto grid max-w-7xl grid-cols-1 gap-12 md:grid-cols-2">
          <UrunDetayOzellikleri urun={u} />

          <div className="flex flex-col">
            <div className="mt-2 flex items-center gap-2 text-[13px] text-black/40">
              <span className="flex text-amber-500">{"★".repeat(Math.round(puan))}<span className="text-black/15">{"★".repeat(5 - Math.round(puan))}</span></span>
              <span className="font-bold text-black">{puan.toFixed(1)}</span>
              <span>({yorum} değerlendirme)</span>
            </div>

            <p className="mt-8 max-w-md text-[15px] leading-relaxed text-black/70">{aciklama}</p>
            <div className="mt-auto space-y-5">
              <div className="space-y-3 border-t border-black/15 pt-6 text-[13px] leading-relaxed text-black/60">
                <p>Sipariş verdiğizde stok arka ofiste 30 dakika boyunca sizin adınıza rezerve edilir.</p>
                <p>Ödeme onayında faturanız otomatik kesilir; kargoya verildiğinde takip bağlantısı sipariş sayfanızda görünür.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Güven kartları */}
      <div className="px-5 sm:px-10">
        <div className="mx-auto max-w-7xl">
          <GuvenKartlari />
        </div>
      </div>
      {/* Gerçek değerlendirmeler */}
      <Yorumlar urunId={u.id} />

      {/* Benzer ürünler */}
      {benzerler.length > 0 && (
        <section className="px-5 pb-24 pt-16 sm:px-10">
          <div className="mx-auto max-w-7xl">
            <div className="flex items-end justify-between border-b border-black/20 pb-5">
              <h2 className="text-[32px] font-black leading-none tracking-[-0.03em] sm:text-[48px]">Bunları da beğenebilirsiniz</h2>
            </div>
            <div className="mt-8 grid grid-cols-1 gap-6 md:grid-cols-3">
              {benzerler.map((b) => (
                <UrunKarti key={b.id} urun={b} />
              ))}
            </div>
          </div>
        </section>
      )}
    </div></div>
  );
}
