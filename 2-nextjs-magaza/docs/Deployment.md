# 🚀 Deployment Rehberi — HepsiBirarada

Üç parçalı kurulum: **Next.js mağaza → Vercel**, **Laravel API → PHP hosting**, **MySQL → hosting**.

## 1. Domain + Hosting

- Domain al (~300₺/yıl) + cPanel'li Linux hosting (PHP **8.2+** şart, SSH tercih edilir)
- cPanel'den MySQL veritabanı + kullanıcı oluştur

## 2. Laravel API (Ön Muhasebe)

```bash
# Lokalde yedek al
mysqldump -u root staj_proje > yedek.sql
# cPanel phpMyAdmin'den içeri aktar

# Sunucuda (SSH)
git clone https://github.com/emrullahaltunkaya79/on-muhasebe-staj.git
cd on-muhasebe-staj
composer install --no-dev
cp .env.example .env
php artisan key:generate
# .env düzenle: APP_URL, DB_*, APP_ENV=production, APP_DEBUG=false
php artisan migrate --force
php artisan storage:link

# API token üret (Vercel mağazası bunu kullanacak)
php artisan tinker
> App\Models\User::where('email','admin@test.com')->first()->createToken('vercel')->plainTextToken;
```

cPanel'de **document root**'u `public/` klasörüne yönlendir + AutoSSL ile HTTPS aç.

## 3. Next.js Mağaza (Vercel)

1. `eticaret-next` klasörü için **ayrı GitHub repo** oluştur ve push et
2. Vercel → Import Project → repo'yu seç
3. **Environment Variables**:

```
BACKOFFICE_URL     = https://api.domainin.com/api/v1
BACKOFFICE_TOKEN   = (2. adımdaki token)
NEXT_PUBLIC_SITE_URL = https://magaza.domainin.com
```

`sitemap.ts` ve `robots.ts` bu değişkeni otomatik kullanır. Vercel HTTPS + otomatik deploy verir.

## 4. Livewire mağaza (opsiyonel)

Aynı hosting'e alt domain (`magaza2.domainin.com`) olarak atılabilir;
`config/backoffice.php` → `shop_url` Vercel adresini göstermelidir.

## 5. Zero-cost canlı demo alternatifi

Sunucu almadan canlı gösterim: `cloudflared tunnel --url http://127.0.0.1:8000`
→ Vercel env'de `BACKOFFICE_URL`'i verilen adrese güncelle. Bilgisayar kapalıyken site düşer.

## Kontrol listesi

- [ ] API `https://.../api/v1/products` token ile cevap veriyor
- [ ] Mağaza ana sayfası ürünleri listeliyor
- [ ] Görseller `/api/img/{id}` üzerinden geliyor
- [ ] Sepet → ödeme → sipariş akışı çalışıyor
- [ ] Sitemap `https://.../sitemap.xml` erişilebilir
