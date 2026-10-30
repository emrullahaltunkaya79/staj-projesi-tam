import { SozlesmeSayfasi } from "@/components/SozlesmeSayfasi";

export const metadata = { title: "Mesafeli Satış Sözleşmesi — HepsiBirarada" };

export default function MesafeliSatisSozlesmesi() {
  return (
    <SozlesmeSayfasi baslik="Mesafeli Satış Sözleşmesi" guncelleme="30.10.2026">
      <h2>1. Taraflar</h2>
      <p>
        İşbu sözleşme, <strong>HepsiBirarada</strong> (&quot;Satıcı&quot;) ile www.hepsibirarada.com adresindeki
        mağazadan alışveriş yapan tüketici (&quot;Alıcı&quot;) arasında elektronik ortamda kurulmuştur.
        Alıcı, siparişi onaylamakla işbu sözleşmenin tüm koşullarını kabul etmiş sayılır.
      </p>

      <h2>2. Konu</h2>
      <p>
        İşbu sözleşmenin konusu, Alıcı&apos;nın mağazadan elektronik ortamda sipariş verdiği ürünlerin satışı ve
        6502 sayılı Tüketicinin Korunması Hakkında Kanun ile Mesafeli Sözleşmeler Yönetmeliği hükümleri
        çerçevesinde tarafların hak ve yükümlülüklerinin belirlenmesidir.
      </p>

      <h2>3. Ürün ve Teslimat</h2>
      <ul>
        <li>Ürünler, ödemenin onaylanmasından sonra en geç 3 iş günü içinde kargoya verilir.</li>
        <li>Termosifon edilemeyen ürünler için Alıcı&apos;ya bilgi verilir ve ödeme iade edilir.</li>
        <li>Teslimat, Alıcı&apos;nın checkout sırasında belirttiği adrese yapılır; kargo süresi teslimat adresine göre 1-5 iş günüdür.</li>
        <li>2.000 ₺ üzeri siparişlerde kargo ücretsizdir.</li>
      </ul>

      <h2>4. Fiyat ve Ödeme</h2>
      <ul>
        <li>Ürün fiyatları Türk Lirası (₺) cinsindendir ve KDV dahildir.</li>
        <li>Kampanya ve indirimli fiyatlar, ürün sayfasında belirtilen süreyle sınırlıdır.</li>
        <li>Ödeme; CSPR (Casper Network) kripto ödeme yöntemiyle anında onaylanır.</li>
      </ul>

      <h2>5. Cayma Hakkı (14 Gün)</h2>
      <ul>
        <li>Alıcı, ürünü teslim aldığı tarihten itibaren <strong>14 gün içinde hiçbir gerekçe göstermeksizin cayma hakkına sahiptir.</strong></li>
        <li>Cayma hakkının kullanılması için ürünün kullanılmamış, ambalajının açılmamış ve yeniden satılabilir durumda olması gerekir.</li>
        <li>İade talebi, &quot;Siparişlerim&quot; sayfasından oluşturulabilir. İade onayından sonra ürün bedeli 14 gün içinde Alıcı&apos;nın ödeme yöntemine iade edilir.</li>
        <li>Kripto ödemelerde iade, ödeme tarihindeki TL karşılığı üzerinden yapılır.</li>
      </ul>

      <h2>6. Cayma Hakkının Kullanılamayacağı Durumlar</h2>
      <ul>
        <li>Alıcıya özel üretilen veya kişiselleştirilen ürünler.</li>
        <li>Ambalajı açılmış yazılım ve dijital içerikler (Steam hediye kartı vb.).</li>
        <li>Çabuk bozulma riski olan veya son kullanma tarihi geçen ürünler.</li>
      </ul>

      <h2>7. Uyuşmazlık Çözümü</h2>
      <p>
        İşbu sözleşmeden doğacak uyuşmazlıklarda Tüketicinin Korunması Hakkında Kanun ve ilgili mevzuat
        hükümleri uygulanır. Alıcı, Tüketici Hakem Heyetleri ve Tüketici Mahkemeleri&apos;ne başvurma hakkına
        sahiptir.
      </p>
    </SozlesmeSayfasi>
  );
}
