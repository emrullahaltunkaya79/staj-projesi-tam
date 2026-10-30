import { SozlesmeSayfasi } from "@/components/SozlesmeSayfasi";

export const metadata = { title: "KVKK Aydınlatma Metni — HepsiBirarada" };

export default function GizlilikPolitikasi() {
  return (
    <SozlesmeSayfasi baslik="KVKK Aydınlatma Metni" guncelleme="30.10.2026">
      <p className="text-base font-medium text-black">
        6698 sayılı Kişisel Verilerin Korunması Kanunu (&quot;KVKK&quot;) kapsamında veri sorumlusu
        HepsiBirarada olarak sizi bilgilendiriyoruz.
      </p>

      <h2>1. İşlenen Veriler</h2>
      <ul>
        <li><strong>Kimlik:</strong> Ad soyad, e-posta adresi (üyelik ve sipariş için)</li>
        <li><strong>İşlem:</strong> Sipariş geçmişi, fatura bilgileri, ödeme kayıtları (son 4 karakter / işlem referansı)</li>
        <li><strong>Teknik:</strong> Çerezler üzerinden oturum ve sepet bilgisi (ayrıntılar için Çerez Politikası)</li>
      </ul>

      <h2>2. İşleme Amaçları</h2>
      <ul>
        <li>Üyelik hesabının oluşturulması ve doğrulanması</li>
        <li>Siparişlerin işlenmesi, fatura kesilmesi ve teslim edilmesi</li>
        <li>Yasal saklama yükümlülüklerinin yerine getirilmesi (Vergi Usul Kanunu)</li>
        <li>Şüpheli işlemlerin tespiti ve dolandırıcılıkla mücadele</li>
      </ul>

      <h2>3. Hukuki Sebep</h2>
      <p>
        Verileriniz; sözleşmenin kurulması ve ifası (m. 5/2-c), hukuki yükümlülük (m. 5/2-ç) ve meşru menfaat
        (m. 5/2-f) hukuki sebepleriyle işlenir.
      </p>

      <h2>4. Aktarım</h2>
      <p>
        Verileriniz; kargo firmalarına (teslimat için), ödeme altyapısına (ödeme onayı için) ve yasal
        talepler halinde yetkili kamu kurumlarına aktarılabilir. Yurt dışına aktarım yapılmamaktadır.
      </p>

      <h2>5. Haklarınız (KVKK m.11)</h2>
      <ul>
        <li>Verilerinizin işlenip işlenmediğini öğrenme</li>
        <li>Verilerinize erişme ve düzeltilmesini isteme</li>
        <li>Silinmesini veya anonim hale getirilmesini isteme</li>
        <li>İşlemeye itiraz etme ve zararın giderilmesini talep etme</li>
      </ul>
      <p>
        Taleplerinizi <a href="/iletisim" className="font-bold text-black underline underline-offset-4">İletişim</a> sayfasındaki
        e-posta adresine iletebilirsiniz; başvurular en geç 30 gün içinde yanıtlanır.
      </p>

      <h2>6. Saklama Süresi</h2>
      <p>
        Sipariş ve fatura kayıtları, Vergi Usul Kanunu gereği 10 yıl saklanır; üyelik verileri üyelik
        aktif olduğu sürece, pazarlama izinleri ise iptal edilene kadar saklanır.
      </p>
    </SozlesmeSayfasi>
  );
}
