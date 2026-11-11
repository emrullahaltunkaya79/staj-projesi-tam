import { CerezAyarlariDugmesi } from "@/components/CerezOnayi";
import { SozlesmeSayfasi } from "@/components/SozlesmeSayfasi";

export const metadata = { title: "Çerez Politikası — HepsiBirarada" };

export default function CerezPolitikasiSayfasi() {
  return (
    <SozlesmeSayfasi baslik="Çerez Politikası" guncelleme="11.11.2026">
      <p>
        Mağazamız, size daha iyi bir alışveriş deneyimi sunmak için çerez (cookie) ve benzeri yerel depolama
        teknolojilerini dört kategoride kullanır. Zorunlu olanlar dışındaki her kategori <strong>yalnızca sizin
        onayınızla</strong> çalışır; tercihinizi ilk ziyaretteki banttan veya aşağıdaki düğmeden her an
        değiştirebilirsiniz.
      </p>

      <h2>1. Kategori 1 — Zorunlu Çerezler (her zaman açık)</h2>
      <ul>
        <li><strong>Sepet (hba-sepet):</strong> Sepetinizdeki ürünleri sayfa yenilendiğinde korur. Bu çerez olmadan alışveriş tamamlanamaz.</li>
        <li><strong>Oturum (hba-token, hba-user):</strong> Giriş yapmış kalmanızı sağlar; kapatırsanız tekrar giriş yapmanız gerekir.</li>
        <li><strong>Güvenlik:</strong> CSRF koruması ve ödeme güvenliği için teknik çerezler.</li>
      </ul>
      <p>Bu çerezler KVKK m.5/2-c (sözleşmenin ifası) ve m.5/2-f (meşru menfaat) dayanağıyla, onay aranmadan kullanılır.</p>

      <h2>2. Kategori 2 — Fonksiyonel Çerezler (onaylı)</h2>
      <ul>
        <li><strong>Favoriler (hba-favoriler):</strong> Beğendiğiniz ürünleri hatırlar; girişli hesabınızda sunucuda da saklanır.</li>
        <li><strong>Tema (hba-dark):</strong> Açık/koyu tema tercihinizi saklar.</li>
        <li><strong>Son İncelenenler (hba-son-goruntulenen):</strong> Son baktığınız ürünleri listeler.</li>
        <li><strong>Arama Geçmişi (hba-son-aramalar):</strong> Arama kutusunda son aramalarınızı önerir.</li>
        <li><strong>Karşılaştırma (hba-karsilastir):</strong> Yan yana karşılaştırdığınız ürünleri tutar.</li>
      </ul>
      <p>
        Bu kategoriye izin vermezseniz alışverişe devam edebilirsiniz; yalnızca bu kolaylıklar tercihinizi
        hatırlamaz (tema gibi bazıları o oturum için geçerli kalır).
      </p>

      <h2>3. Kategori 3 — Analitik / Performans Çerezleri (onaylı)</h2>
      <p>
        Google Analytics 4 ile hangi sayfaların gezildiğini, hangi adımda terk edildiğini <strong>anonim ve IP
        anonimleştirilmiş</strong> şekilde ölçeriz (_ga, _ga_* çerezleri). Veriler kimliğinizle eşleştirilmez.
        Onay vermediğinizde ölçüm betiği hiç yüklenmez.
      </p>

      <h2>4. Kategori 4 — Hedefleme / Reklam Çerezleri (onaylı)</h2>
      <p>
        İlgi alanınıza uygun ürün reklamları göstermek ve kampanya etkisini ölçmek için reklam ortaklarının
        çerezleridir. <strong>Şu anda aktif bir reklam ağı kullanmıyoruz;</strong> ileride eklendiğinde bu
        kategorideki onayınız geçerli olacak, eklenmeden hiçbir reklam betiği çalışmayacaktır.
      </p>

      <h2>5. Çerezleri Yönetme</h2>
      <p>
        Tercihinizi aşağıdaki düğmeyle veya sitenin alt bilgisindeki <strong>Çerez Ayarları</strong> bağlantısıyla
        her an değiştirebilir, geri alabilirsiniz. Tarayıcı ayarlarınızdan çerezleri tamamen silmeniz de mümkündür;
        zorunlu çerezleri engellemek sepet ve giriş işlevlerini bozar.
      </p>
      <p>
        <CerezAyarlariDugmesi sinif="inline-flex h-11 items-center rounded-full bg-black px-6 text-sm font-bold text-white transition hover:bg-black/80" />
      </p>

      <h2>6. Üçüncü Taraflar</h2>
      <p>
        Ödeme sürecinde CSPR ödeme altyapısı, kendi güvenlik çerezlerini kullanabilir. Bu çerezler sözleşme
        ifası için zorunludur ve işbu politika kapsamı dışındadır.
      </p>
    </SozlesmeSayfasi>
  );
}
