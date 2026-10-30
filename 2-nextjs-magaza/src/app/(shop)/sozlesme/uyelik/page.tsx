import { SozlesmeSayfasi } from "@/components/SozlesmeSayfasi";

export const metadata = { title: "Üyelik Sözleşmesi — HepsiBirarada" };

export default function UyelikSozlesmesi() {
  return (
    <SozlesmeSayfasi baslik="Üyelik Sözleşmesi" guncelleme="30.10.2026">
      <h2>1. Üyelik</h2>
      <p>
        Mağazamızdan alışveriş yapmak için üyelik zorunlu değildir; misafir olarak da sipariş verebilirsiniz.
        Üye olan kullanıcılar; sipariş geçmişini görüntüleme, favori listesi yönetimi ve hızlı ödeme gibi
        avantajlardan yararlanır.
      </p>

      <h2>2. Hesap Güvenliği</h2>
      <ul>
        <li>Üyelik sırasında verdiğiniz e-posta adresine bir doğrulama kodu gönderilir; hesabınız kodu girdiğinizde doğrulanır.</li>
        <li>Şifreniz şifrelenerek saklanır; kimse tarafından görüntülenemez.</li>
        <li>Şifrenizi unutmanız durumunda e-posta ile gelen 6 haneli kodla yeni şifre belirleyebilirsiniz.</li>
        <li>Hesabınızın güvenliğinden siz sorumlusunuz; şüpheli durumda derhal çıkış yapıp şifrenizi yenileyiniz.</li>
      </ul>

      <h2>3. Kişisel Verilerin İşlenmesi</h2>
      <p>
        Üyelik ve sipariş süreçlerinde toplanan kişisel verileriniz, KVKK kapsamında yalnızca sipariş
        yönetimi, faturalama ve tarafınızla iletişim amacıyla işlenir. Detaylar için{" "}
        <a href="/gizlilik" className="font-bold text-black underline underline-offset-4">KVKK Aydınlatma Metni</a>&apos;ni
        inceleyiniz.
      </p>

      <h2>4. Yükümlülükler</h2>
      <ul>
        <li>Üye, verdiği bilgilerin doğruluğundan sorumludur; yanlış bilgiden doğacak kargo gecikmelerinden Satıcı sorumlu değildir.</li>
        <li>Üye hesabı, başkasının kullanımına devredilemez.</li>
        <li>Satıcı, kanuna aykırı kullanım tespit edilen hesapları askıya alma hakkına sahiptir.</li>
      </ul>

      <h2>5. Üyelik İptali</h2>
      <p>
        Üye, dilediği zaman hesabından çıkış yaparak ve bizimle iletişime geçerek üyeliğini sonlandırabilir.
        Üyelik iptal edilse bile yasal saklama süreleri gereken fatura ve sipariş kayıtları saklanır.
      </p>
    </SozlesmeSayfasi>
  );
}
