# Entegrasyon Rehberi

> Bu rehber, ön muhasebe (Laravel) ile e-ticaret mağazasını (Next.js) bağlamayı açıklar.

## 1. Ön Koşullar

- PHP 8.4 + Composer
- Node.js 20+ + npm
- MySQL 8.4 (XAMPP)
- Git

## 2. Arka Ofis Kurulumu (port 8000)

```bash
cd 1-arka-ofis

composer install
cp .env.example .env
php artisan key:generate

# MySQL ayarları .env dosyasına girin:
# DB_CONNECTION=mysql
# DB_DATABASE=staj_proje

php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

### Zamanlayıcı + Kuyruk (ayrı terminallerde)
```bash
php artisan schedule:work
php artisan queue:work database --sleep=1 --tries=3
```

### API Token Oluşturma
```bash
php artisan tinker
```
```php
User::where('email','admin@test.com')->first()
    ->createToken('eticaret', ['ecommerce'])
    ->plainTextToken;
```

## 3. Next.js Mağaza Kurulumu (port 3001)

```bash
cd 2-nextjs-magaza

npm install

# .env.local dosyası oluşturun:
echo 'BACKOFFICE_URL=http://127.0.0.1:8000/api/v1' > .env.local
echo 'BACKOFFICE_TOKEN=<token-degerini-buraya-yapistirin>' >> .env.local
echo 'BACKOFFICE_WEBHOOK_SECRET=<arka-ofis-secret>' >> .env.local

npm run dev -- -p 3001
```

## 4. Webhook Ayarı

Arka ofis panelinde:
1. http://127.0.0.1:8000 → Ayarlar → Entegrasyon
2. Webhook URL: `http://127.0.0.1:3001/api/webhook/backoffice`
3. Webhook Secret: Next.js `.env.local`'daki ile aynı olmalı

## 5. CSPR Ödeme Ayarı

1. Arka ofis → Ayarlar → Entegrasyon
2. CSPR alıcı adresinizi girin (Casper Wallet adresi)
3. Kur: 1 CSPR = X ₺

## 6. Test Akışı

```
1. http://127.0.0.1:3001 → ürün seç → sepete ekle
2. Checkout → e-posta/adres gir → "Siparişi Onayla"
3. Sipariş sayfası → "CSPR ile Öde" → QR kod görünecek
4. Ödeme sonrası fatura otomatik kesilir
5. Arka ofiste http://127.0.0.1:8000/faturalar → faturayı görün
```

## 7. Satıcı Paneli

```
URL: http://127.0.0.1:3001/satici
Giriş: admin@test.com / password
Özellikler: Dashboard + Ürün CRUD + Siparişler + Raporlar
```
