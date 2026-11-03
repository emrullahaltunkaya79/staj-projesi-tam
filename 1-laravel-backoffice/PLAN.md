# Yarının Geliştirme Planı (27 Ekim 2026)

> 26 Ekim oturumunun sonunda hazırlanıp onaylanan plan. Önerilen sıra: 1 → 2 → 4 → 7 → 8.
>
> **Durum (27 Ekim, gün sonu):** 1-6 + frontend dokunuşları tamamlandı; webhook kuyruğa taşındı, sipariş idempotency,
> ürün senkron webhook ları, API referans + Postman, boş durumlar, sipariş stepper ı, filtre çipleri, progress bar, odak tuzağı. Kalan: 7-8 (sunum paketi). E-ticaret entegrasyon provası 27.10 akşamı canlı doğrulandı.

## A. Yarım kalan / bilinen eksikler (önce bunlar — hızlı kazanımlar)

1. **Webhook teslimat geçmişi paneli**
   - `WebhookDelivery` kayıtları tutuluyor ama gösteren arayüz yok
   - Yönetim altına sayfa: gönderim listesi (olay, durum, HTTP yanıt kodu, süre), başarısız olan için tek tıkla yeniden gönder (retry)
   - `WebhookService::retry()` metodu zaten hazır

2. **Panel içi bildirim çanı**
   - Üst çubuğa zil ikonu + açılır kart: vadesi geçen fatura, kritik stok, ödeme bekleyen sipariş sayıları
   - Her madde ilgili sayfaya linkli (faturalar?low, urunler?low_stock=1, siparisler)
   - Dashboard verileriyle aynı kaynaklar (ReceivableService, Product, Order)

3. **Raporlara Excel çıktısı**
   - Faturalar/ürünler/cariler'de Excel var; kâr marjı ve kasa raporlarına da ekle
   - Mevcut Maatwebsite Excel pattern'leri kullanılır

## B. E-ticaret tarafını derinleştirme

4. **API dokümantasyonu panel sayfası**
   - `api-tokenlar` sayfasına "Örnek İstekler" sekmesi
   - Her endpoint (products, stock, customers, orders, pay, cancel, shipping) için token'lı hazır curl/PHP örneği
   - İsteğe: indirilebilir Postman koleksiyonu (JSON)

5. **Ürün barkod alanı** ✅ (27 Ekim'de tamamlandı)
   - products.barcode kolonu (unique, indeksli) + ürün formu alanı
   - Panel araması + global arama barkoddan bulur
   - API: products/products/{id} yanıtlarında barcode; `/stock?barcode=` ile POS hızlı sorgusu

6. **Sipariş kutu etiketi (PDF)** ✅ (27 Ekim'de tamamlandı)
   - `/siparisler/{order}/etiket` — gönderici/alıcı adresli, kargo firması + büyük takip no blokları
   - Sipariş detayında "Kargo Etiketi" butonu (paid/preparing/shipped/delivered)

## C. Teslim hazırlığı (staj teslimi yaklaşınca)

7. **Sıfırdan kurulum provası**
   - `php artisan migrate:fresh --seed` temiz kurulum doğrulaması
   - DemoSeeder'ın eksiksiz çalıştığının kontrolü

8. **Sunum paketi**
   - 5 dk'lık demo senaryosu: cari aç → karttan fatura kes → CSPR sipariş → kargolama → webhook
   - Güncel ekran görüntüleri + README'nin son durumla eşitlenmesi

9. **Test kapsamı artırımı**
   - Kart sisteminin AJAX akışlarına (expectsJson yanıtları) 3-4 feature test

## Bağlam (26 Ekim'de bitenler)

- Kart sistemi uygulamaya yayıldı (form + görüntüleme kartları, karttan yazdırma)
- Ctrl+K komut paleti + klavye kısayolları
- Sidebar revizyonu + Dashboard yenilemesi (kasa, nakit akışı, e-ticaret paneli)
- 101 test yeşil, tüm commit'ler push'lu (son: `eecd7b5`)
