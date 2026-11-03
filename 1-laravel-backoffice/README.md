# Ön Muhasebe & Stok Takip Web Uygulaması

Laravel 11 + Tailwind CSS ile geliştirilmiş, staj projesi amaçlı sadeleştirilmiş bir ön muhasebe ve stok takip sistemi.

> 📐 **[Mimarî & Veritabanı Dokümanı (ERD)](docs/MIMARI.md)** — katmanlar, servisler, transaction desenleri, tablo şeması
> 🎬 **[Sunum Demo Senaryosu](docs/DEMO.md)** — 5 dakikalık rota + muhtemel sorular

## Özellikler

### Satış Zinciri
- **Teklif modülü:** Teklif kes → onayla → tek tıkla faturalaştır; PDF indir + e-posta ile gönder; düzenleme/silme (soft delete)
- **Satış faturası:** Dinamik kalem eklenen form, canlı toplam, otomatik fiyat (cari bazlı özel fiyat dahil), **depo seçimi**, stok kontrolü + fiyat yetkisi
- **Kısmi/Tam iade:** Kalem bazında miktar girilerek; kalan iade hakkı takip edilir
- **Alış faturası:** Tedarikçiden satın alma → stok artar, bakiye borçlanır
- **Tekrarlayan faturalar:** Aylık kira/abonelik; scheduler ile otomatik kesim
- **Tahsilat + Tedarikçi ödemesi:** Nakit/havale, FIFO dağıtım, bakiye aşım engeli

### E-ticaret Entegrasyonu 🛒
- **Rezerve stok modeli:** Sipariş geldiğinde stok rezerve edilir (müsait = fiziksel − rezerve); ödeme gelince kalıcı düşer, 30 dk gelmezse scheduler otomatik serbest bırakır
- **Sipariş yaşam döngüsü:** Bekliyor → Ödendi → Hazırlanıyor → Kargolandı → Teslim / İptal
- **REST API (Sanctum):** `GET products`, `GET stock`, `POST customers`, `POST orders`, `POST orders/{no}/pay|cancel|shipping` — detay: [docs/API.md](docs/API.md)
- **Panel:** Siparişler sayfası (özet kartlar + filtre + detay + kargolama)
- **Ürün görselleri:** Çoklu yükleme, ana görsel, API `image_url` (base64)

### Stok & Depo Yönetimi
- Ürünler + **kategoriler** + kritik stok uyarısı
- **Çoklu depo:** Depo CRUD, ürün-depo stok matrisi, **depolar arası transfer**, depo bazlı Excel, **toplu stok dağıtım aracı**
- **Stok hareket geçmişi:** Açılış, alış, satış, iade, düzeltme — **depo bilgisyle** kaydedilir, depo filtresiyle izlenir
- **Excel içe/dışa aktarma:** Ürün ve cari listeleri; **stok sayım** listesi doldur → yükle → farklar otomatik işlenir
- **Cari bazlı özel fiyat listesi:** Fatura formunda ürün seçince otomatik uygulanır

### Finans & Raporlama
- **Giderler:** Kira/fatura vb. nakit/havale kategorili gider kayıtları
- **Kasa ↔ Banka virmanları:** İki hesap arasında para transferi
- **Raporlar:** 12 aylık ciro grafiği (satış/alış/gider), en çok satan, brüt kâr, **kâr marjı**, **alacak yaşlandırma**, kasa/banka durumu
- **Ödeme Takvimi:** Ay ızgarasında vade günleri + tekrarlayan kesimler; yaklaşan ödemeler listesi
- **Dashboard:** İstatistik kartları, kritik stok, son hareketler, vadesi geçen fatura uyarısı

### Kimlik Doğrulama & Yetkilendirme
- Şirket + kullanıcı kaydı (ilk kullanıcı otomatik admin)
- E-posta doğrulama, şifre sıfırlama (oturumları geçersiz kılar), şifre değiştirme
- **Roller:** Yönetici/Kullanıcı — yönetici rolü listeden anında değiştirir; son yönetici düşürülemez
- Policy'ler, Form Request'ler, rate limiting

### Araçlar & Kalite
- **PDF:** Fatura + teklif PDF'i (DomPDF, Türkçe, logo, "Yalnız ... TL" yazıyla)
- **QR kod:** Her faturada tekil doğrulama kodu; PDF'e basılı; okutan herkes web'den doğrulayabilir
- **E-posta:** Fatura/teklif PDF ekli gönderim; günlük özet (scheduler, kuyrukta)
- **Dark mode:** Toggle butonu, kalıcı tercih, tüm bileşenlerde koyu tema
- **Global arama:** Üst bardan cari/ürün/fatura araması (canlı açılır sonuçlar)
- **Etkinlik günlüğü:** Kim, neyi, ne zaman — değişen alanlar ve IP ile
- **Geri dönüşüm kutusu:** Soft delete + geri yükleme
- **Yedekleme:** spatie/laravel-backup (elle + haftalık Schedule)
- **Çift gönderim koruması** + sunucu tarafı fiyat yetkisi
- **Çok kiracılılık:** Her şirket yalnız kendi verisini görür (global scope)
- **CI:** GitHub Actions — her push/PR'da PHPUnit + Vite build

## Kurulum

Gereksinimler: PHP ≥ 8.2 (SQLite eklentisiyle), Composer, Node.js + npm.

```bash
# 1) Bağımlılıklar
composer install
npm install

# 2) Ortam dosyası ve anahtar
cp .env.example .env
php artisan key:generate

# 3) MySQL veritabanı oluşturun ve .env'de ayarlayın:
#    DB_CONNECTION=mysql, DB_DATABASE=staj_proje, DB_USERNAME=root

# 4) Tablolar + 4 aylık zengin demo verisi
php artisan migrate --seed

# 5) Asset'leri derle
npm run build

# 6) Sunucuyu başlat
php artisan serve
```

Uygulama: http://127.0.0.1:8000

**Demo giriş:** `admin@test.com` / `password` (yönetici) · `personel@test.com` / `password` (standart kullanıcı)

> Not: Composer'ın paket güvenlik uyarısı denetimi yerel geliştirme için kapatılmıştır. MySQL için `mysqldump` yolunun `config/database.php`'de tanımlı olduğundan emin olun.

## Testler

```bash
php artisan test
```

**83 PHPUnit testi / 334 doğrulama** — SQLite bellek-içi.

Kapsam: fatura akışları (stok/bakiye/rollback), kısmi iade, tekrarlayan fatura, teklif→fatura dönüşümü, çok kiracılık sızıntıları, rol/fiyat yetkileri, rate limiting, şifre akışları, Excel içe/dışa, QR doğrulama, denetim/geri dönüşüm, bildirim komutları, PDF üretimi.

## Önemli Dosyalar

| Dosya | Amaç |
|---|---|
| `app/Services/InvoiceService.php` | Fatura iş mantığı: transaction + satır kilidi + stok/bakiye + QR kod üretimi |
| `app/Services/PaymentService.php` | Tahsilat/ödeme: transaction + bakiye aşım kontrolü |
| `app/Services/QuoteService.php` | Teklif oluştur/düzenle/durum/faturalaştır |
| `app/Services/ReceivableService.php` | FIFO dağıtım, gecikme günü, yaşlandırma |
| `app/Http/Controllers/` | 20+ denetleyici |
| `app/Policies/` | 7 policy (rol bazlı yetkilendirme) |
| `app/Http/Requests/` | 8 Form Request (Türkçe mesajlı doğrulama) |
| `resources/views/` | 25+ Blade görünümü (Tailwind, dark mode) |
| `public/js/invoice-form.js` | Fatura/teklif formu: dinamik kalem, özel fiyat çözümleme |
| `tests/Feature/` | 15 test dosyası |
| `.github/workflows/ci.yml` | GitHub Actions CI |

## İş Kuralları

- **Bakiye:** Pozitif = cari size borçlu (alacak). Satış faturası bakiyeyi artırır; tahsilat azaltır.
- **Stok:** Satışta düşer, alışta artar. Eşzamanlı işlemde `lockForUpdate` ile stok yarışı engellenir.
- **KDV:** Fatura bazında %0/1/10/20; kalemler nettir.
- **Fatura no:** `SF-/AF-/IF-/TK-` + 6 hane (tekil).
- **Yaşlandırma:** Tahsilatlar FIFO dağıtılır; kalan tutar ve gecikme günü buna göre hesaplanır.
- **QR doğrulama:** Her faturanın 24 karakterlik kodu vardır; `/fatura/dogrula/{code}` herkese açıktır.
