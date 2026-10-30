import { SozlesmeSayfasi } from "@/components/SozlesmeSayfasi";

export const metadata = { title: "Çerez Politikası — HepsiBirarada" };

export default function CerezPolitikasiSayfasi() {
  return (
    <SozlesmeSayfasi baslik="Çerez Politikası" guncelleme="30.10.2026">
      <p>
        Mağazamız, size daha iyi bir alışveriş deneyimi sunmak için çerez (cookie) ve benzeri yerel depolama
        teknolojileri kullanır. Tarayıcınızın ayarlarından çerezleri her zaman yönetebilirsiniz.
      </p>

      <h2>1. Kullandığımız Çerezler</h2>
      <ul>
        <li><strong>Zorunlu — Sepet (hba-sepet):</strong> Sepetinizdeki ürünleri sayfa yenilendiğinde korur. Bu çerez olmadan alışveriş tamamlanamaz.</li>
        <li><strong>Zorunlu — Oturum (hba-token, hba-user):</strong> Giriş yapmış kalmanızı sağlar; kapatırsanız tekrar giriş yapmanız gerekir.</li>
        <li><strong>İşlevsel — Favoriler (hba-favoriler):</strong> Beğendiğiniz ürünleri hatırlar.</li>
        <li><strong>İşlevsel — Tema (hba-tema):</strong> Açık/koyu tema tercihinizi saklar.</li>
        <li><strong>Analitik (izinli):</strong> Onay verirseniz, ziyaret ölçümü için anonim istatistik toplar.</li>
      </ul>

      <h2>2. Çerezleri Yönetme</h2>
      <p>
        Tarayıcı ayarlarınızdan çerezleri silebilir veya engelleyebilirsiniz; zorunlu çerezleri engellemek
        sepet ve giriş işlevlerini bozar. Site içinden onayınızı &quot;Yalnızca Zorunlu&quot; seçeneğiyle her zaman
        geri alabilirsiniz — bu durumda analitik çerezler yüklenmez.
      </p>

      <h2>3. Üçüncü Taraflar</h2>
      <p>
        Ödeme sürecinde CSPR ödeme altyapısı, kendi güvenlik çerezlerini kullanabilir. Bu çerezler sözleşme
        ifası için zorunludur ve işbu politika kapsamı dışındadır.
      </p>
    </SozlesmeSayfasi>
  );
}
