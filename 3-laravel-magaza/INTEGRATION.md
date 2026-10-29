# E-Ticaret Sitesi — Ön Muhasebe Entegrasyon Rehberi

> Bu klasör e-ticaret siteniz içindir. Arka ofis (ön muhasebe) uygulaması
> `C:\Users\Asus\OneDrive\Desktop\ss` klasöründedir — GitHub:
> `emrullahaltunkaya79/on-muhasebe-staj`

## Arka ofisi başlatma (entegrasyon testlerinde)

```bash
cd C:\Users\Asus\OneDrive\Desktop\ss
C:\Users\Asus\.config\herd\bin\php84\php.exe artisan serve --host=127.0.0.1 --port=8000
# ayrı terminalde (webhook/rezerv otomasyonları için):
C:\Users\Asus\.config\herd\bin\php84\php.exe artisan schedule:work
C:\Users\Asus\.config\herd\bin\php84\php.exe artisan queue:work database
```

Panel: http://127.0.0.1:8000 — giriş: `admin@test.com` / `password`
**Bu site başka bir port kullanmalı** (ör. 3000, 5173) — 8000 arka ofiste.

## API Kimlik Doğrulama

1. Panel → API Tokenlar → "Yeni API Token" (şifre onayı ister; değer **bir kez** gösterilir)
2. Her istekte: `Authorization: Bearer <TOKEN>` + `Accept: application/json`
3. Hız limiti: 60 istek/dk · Tüm uçlar: panel → API Tokenlar → "API Referansı" (curl örnekleri + Postman koleksiyonu indirilebilir)

## Temel Akış (siparişten kargoya)

| Adım | İstek | Not |
|---|---|---|
| Katalog | `GET /api/v1/products` | fiyat/stok/barkod/görsel; `?category_id=` filtresi |
| Stok | `GET /api/v1/stock?product_ids[]=1` veya `?barcode=869...` | rezerve + müsait ayrı döner |
| Müşteri | `POST /api/v1/customers` | **idempotent** (aynı e-posta = aynı kayıt) |
| Sipariş | `POST /api/v1/orders` | stok **rezerve** olur, 30 dk geçerli; `external_ref` gönder → çift gönderim korumalı |
| Ödeme | `POST /api/v1/orders/{no}/pay` | fatura otomatik kesilir, stok düşer, webhook gider |
| Kargo | `POST /api/v1/orders/{no}/shipping` | `carrier` + `tracking_no` → takip URL'si döner |
| İptal | `POST /api/v1/orders/{no}/cancel` | rezerv/fatura geri alınır |

`tax_rate`: 0/1/10/20 · Sipariş gövdesi: `{contact_id, tax_rate, items:[{product_id, quantity}], shipping_address?, external_ref?}`

## Webhook'lar (arka ofis → bu site)

Ayarlar → Entegrasyon'da bu sitenin dinleyici URL'si girilecek (şu an test dinleyicisi kayıtlı — değiştirin).
Olaylar: `order.paid`, `order.cancelled`, `order.shipped`, `stock.changed`, `product.upserted`, `product.deleted`
İmza: `X-Signature` = HMAC-SHA256(gövde, secret) · Olay adı: `X-Event`
Başarısız teslimatlar panel → Webhooklar'dan tek tıkla yeniden gönderilir.

## CSPR (kripto ödeme)

Uç müşteriden CSPR alınacaksa: Ayarlar → Entegrasyon'a **alıcı cüzdan adresi** girilmeli;
ödeme talimatı (adres + memo + tutar) için arka ofise `cspr` ödeme bilgisi ucu eklenmesi planlanıyor.
Siteniz ödemeyi kendi yöntemiyle alıyorsa `POST /pay` yeterli.
