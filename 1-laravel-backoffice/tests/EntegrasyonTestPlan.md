# Entegrasyon Test Planı — Ön Muhasebe + E-Ticaret

> Tüm zincirler canlı sistemde doğrulanmıştır (2026-11-17).
> Sunucular: panel `http://127.0.0.1:8000` · mağaza `http://127.0.0.1:3001`
> Gerekli: panel oturumu (admin), mağaza sipariş API anahtarı, log mailer (`MAIL_MAILER=log`)

---

## Zincir 1 — E-Ticaret Sipariş & Stok Entegrasyonu
**Akış**: mağaza API sipariş → rezerv → ödeme onayı → fatura + stok düşümü + ciro + webhook

```bash
# 1) Sipariş oluştur (havale)
curl -s -X POST http://127.0.0.1:3001/api/orders -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"deniz@ornek.com","phone":"0555…","address":"İstanbul","items":[{"product_id":<ID>,"quantity":1}],"odemeYontemi":"havale"}'
# 2) Panele giriş → Siparişler → "Havaleyi Onayla — Faturalaştır"
#    (veya OrderService::markPaid)
# 3) Doğrula:
php artisan tinker --execute="…products/invoices/webhook_deliveries kontrolleri…"
```
**Kontrol listesi**:
- [x] Sipariş `pending` → ödeme onayı → `paid`
- [x] Satış faturası otomatik kesilir (`orders.invoice_id` dolu, `SF-…`)
- [x] `stock_quantity` satılan adet kadar düşer + `satis/cikis` stok hareketi
- [x] Dashboard "Bu Ay Ciro" sipariş tutarı kadar artar
- [x] `order.paid` + `order.shipped` webhook'ları mağazaya gider

## Zincir 2 — Ürün Görseli & Otomatik Temizlik (GC)
**Akış**: panel yükleme → optimize → webhook → mağaza önbellek temizliği → anında yansıma

```bash
# Yetim görsel taraması (kuru çalışma) ve temizlik:
php artisan gorsel:temizle          # rapor
php artisan gorsel:temizle --calistir   # 24 saatten eski yetimleri siler
```
**Kontrol listesi**:
- [x] Yükleme validasyonu: JPG/PNG/WebP, maks 4 MB (hata mesajları Türkçe)
- [x] 1600px üstü görseller otomatik küçültülür + %85 sıkıştırılır
- [x] Yükleme/silme/ana-görsel değişimi → `product.upserted` webhook'u
- [x] Mağaza `/api/img/{id}` önbelleği webhook ile düşer → **anında** yeni görsel
- [x] Görsel URL'leri `?s=gorsel_surum` taşır → tarayıcı önbelleği ürün bazında kırılır
- [x] Görsel silinince dosya diskten silinir
- [x] Ürün kalıcı silinince dosyaları yetim kalır → haftalık `gorsel:temizle --calistir` (Pazar 03:30) temizler; **referanslı dosyalara ve son 24 saatin yüklemelerine dokunmaz**

## Zincir 3 — Pazar Yeri Komisyon & Net Kâr Simülatörü
**Konum**: Ürün ekle/düzenle → "Pazar Yeri Komisyon & Net Kâr Simülatörü" kartı

**Formül**:
```
Satış(KDV hariç) = Satış ÷ (1 + KDV%)
Komisyon         = Satış × Oran%          (KDV dahil satış üzerinden)
Net Maliyet      = Alış (KDV dahil işaretliyse ÷ (1+KDV%))
Net Kâr          = Satış(KDV hariç) − Komisyon − Kargo − Net Maliyet
Marj             = Net Kâr ÷ Satış × 100
```
**Doğrulanmış matris** (Satış 3.000 ₺, Alış 1.800 ₺, Kargo 40 ₺, KDV %20):

| Pazar Yeri | Komisyon | Net Kâr | Marj |
|---|---|---|---|
| Trendyol %15 | −450,00 ₺ | 210,00 ₺ | %7,0 (🟠 ince) |
| Hepsiburada %14 | −420,00 ₺ | 240,00 ₺ | %8,0 (🟠 ince) |
| Amazon %12 | −360,00 ₺ | 300,00 ₺ | %10,0 (🟢 kârlı) |
| Özel %8,5 (maliyet KDV dahil) | −255,00 ₺ | 685,00 ₺ | %22,8 (🟢) |
| Satış 1.500 ile | — | −825,00 ₺ | 🔴 ZARAR |

- [x] 4 senaryo beklenen değerlerle birebir eşleşti (browser DOM ölçümü)
- [x] Rozetler: zarar kırmızı, marj<%10 turuncu, kârlı yeşil
- [x] Kaydetme: `pazaryeri` + `pazaryeri_komisyon_orani` + `kargo_maliyeti` products tablosuna kalıcı

## Zincir 4 — Kritik Stok & E-Posta Bildirimi
**Akış**: stok ≤ `min_stock_level` → tek seferlik mail → stok toparlanınca damga sıfırlanır

```bash
# Mail gönderimini canlı doğrula (log mailer):
grep -c "^To: emrullahaltunkaya79" storage/logs/laravel.log
# Tetikleme: ürün stoğunu eşik altına indir (panel düzenleme veya mağaza satışı)
```
**Kontrol listesi**:
- [x] Eşik altına düşüş → `KritikStokMail` → alıcı: `KRITIK_STOK_MAIL_ALICI` (emrullahaltunkaya79)
- [x] E-posta tablosu: ürün adı, SKU, kalan stok, eşik, önerilen sipariş adedi, tedarikçi
- [x] **Anti-spam**: aynı üründe tekrar düşüş → mail atılmaz (`low_stock_notified_at` damgası)
- [x] Toparlanma (stok > eşik) → damga sıfırlanır → yeni düşüşte yeniden mail
- [x] Tetikleyici kapsamı: mağaza siparişi ödemesi (InvoiceService) + panel stok düzeltmesi (Product updated)
- [x] Mail gönderimi başarısızsa stok akışı bozulmaz (try-catch + log warning)

## Zincir 5 — Sidebar Akordiyon (hover + mouseleave)
**Konum**: panel yan menüsü (layouts/app.blade.php)

**Kontrol listesi**:
- [x] Grup başlığına `mouseenter` → o grup açılır, **diğerleri otomatik kapanır** (exclusive)
- [x] Chevron'lar: açık ↓ / kapalı → `-90°` dönüşü
- [x] `nav.sidebar-scroll` `mouseleave` → 250 ms sonra tüm gruplar yumuşakça kapanır
- [x] Kenar titremesi: geri girilirse bekleyen kapatma iptal edilir
- [x] Tıklama yedeği (klavye/dokunmatik) + tercih `nav-acik-grup` ile kalıcı
- [x] Sayfa açılışında kayıtlı durum animasyonsuz uygulanır (`#sidebar.grup-hazirlan`)

---

## Genel Sağlık Kontrolleri
```bash
php artisan test                      # 202 test / 828 assertion
php artisan gorsel:temizle            # yetim görsel raporu
grep -c "^To: emrullahaltunkaya79" storage/logs/laravel.log   # kritik stok maili sayacı
tail storage/logs/laravel.log         # çalışma zamanı hataları
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8000/panel   # panel
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:3001/       # mağaza
```

## Bilinen Sınırlar
- Trendyol/HB gerçek API entegrasyonu yok — kanal/komisyon alanları manuel/demo;
  API anahtarları geldiğinde `orders.kanal` + `pazaryeri_komisyonu` alanları hazır.
- Pazar yeri simülatörü **advisory**'dir (siparişleri etkilemez); komisyon kalemleri
  sipariş entegrasyonunda `orders.pazaryeri_komisyonu` üzerinden net kâra girer.
- Panel görsel yükleme API ucunda (satıcı paneli) optimize yok — panel formu optimizasyonu ana yoldur.
