# 🎓 Staj Projesi — Ön Muhasebe & E-Ticaret Sistemi

> **Öğrenci:** Emrullah Altunkaya
> **Tarih:** Ekim 2026
> **Teknolojiler:** Laravel 11, Next.js 16, Tailwind CSS v4, MySQL, Sanctum, CSPR

---

## 📁 Proje Yapısı

```
sataj/
├── README.md                    ← Bu dosya
├── 1-arka-ofis/                 ← Laravel Ön Muhasebe (ana API)
├── 2-nextjs-magaza/             ← Next.js E-Ticaret Mağaza (ana vitrin)
├── 3-laravel-magaza/            ← Laravel Livewire Mağaza (yedek vitrin)
└── INTEGRATION.md               ← Entegrasyon rehberi
```

---

## 🏢 1-arka-ofis — Ön Muhasebe API (Laravel 11)

**Ne yapar?** Tüm sistemin beyni. Muhasebe + stok + sipariş yönetimi + e-ticaret API'si.

### Özellikler
| Modül | Açıklama |
|---|---|
| Cariler | Müşteri/tedarikçi kartları, ekstre, cari bazlı fiyat listesi |
| Ürünler | Kategori, barkod, görsel yükleme, depo bazlı stok |
| Faturalar | Satış/alış/iade, KDV hesaplama, PDF çıktı, QR doğrulama |
| Depolar | Depo arası transfer, stok matrisi, toplu dağıtım, Excel çıktı |
| Kasa | Tahsilat/ödeme, giderler, kasa-banka virmanı |
| Teklifler | Teklif → fatura dönüşümü, e-posta gönderimi |
| Tekrarlayan | Otomatik aylık faturalandırma |
| Raporlar | Kâr marjı, kasa durumu, alacak yaşlandırma |
| E-Ticaret API | REST v1: ürünler, siparişler, ödeme, webhook |
| Auth | Sanctum token, rol yönetimi (admin/kullanıcı/musteri) |

### Teknolojiler
- **Backend:** Laravel 11, PHP 8.4
- **Veritabanı:** MySQL 8.4
- **Kimlik:** Laravel Sanctum
- **E-posta:** Laravel Mail (queue'lu)
- **Ödeme:** Casper Network (CSPR)
- **Test:** 107 test / 428 assertion

### Kurulum
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8000
```

### Giriş
```
URL: http://127.0.0.1:8000
E-posta: admin@test.com
Şifre: password
```

---

## 🛒 2-nextjs-magaza — E-Ticaret Vitrin (Next.js 16)

**Ne yapar?** Müşterilerin alışveriş yaptığı ana mağaza. Minimalist, tipografi odaklı tasarım.

### Özellikler
| Özellik | Açıklama |
|---|---|
| Katalog | 75 ürün · 9 kategori · arama · sıralama · sidebar filtre |
| Ürün Detayı | Görsel galerisi + lightbox, puan + yorumlar, benzer ürünler |
| Sepet | localStorage, miktar kontrol, KDV hesaplama |
| Checkout | Kupon + kargo seçimi + CSPR QR ödeme + varyasyon |
| Sipariş | 5 adımlı durum takibi + iptal + kargo linki |
| Müşteri Hesabı | Üye ol / giriş yap / siparişlerim |
| Favoriler | Kalp ikonu + kayıtlı ürünler |
| Satıcı Paneli | Dashboard + ürün CRUD + siparişler + raporlar + auth |
| Live Chat | Otomatik yanıt + hızlı sorular |
| Dark Mode | Gece teması + otomatik sistem tercihi |
| Hero Slider | 3 kampanya, otomatik dönen |
| PWA | Offline katalog + ana ekrana ekle |
| SEO | Sitemap + robots + meta tags |
| Çoklu Dil | EN/TR toggle |

### Teknolojiler
- **Frontend:** Next.js 16 (App Router), TypeScript
- **CSS:** Tailwind CSS v4
- **İkonlar:** lucide-react
- **PWA:** Service Worker + Manifest

### Kurulum
```bash
npm install
cp .env.local.example .env.local  # BACKOFFICE_URL + BACKOFFICE_TOKEN girin
npm run dev -- -p 3001
```

---

## 🏪 3-laravel-magaza — Livewire Mağaza (Yedek)

**Ne yapar?** Next.js mağazanın Laravel versiyonu. Aynı API'ye bağlanır, farklı teknoloji.

> Bu proje geliştirme aşamasında Next.js'e devredildi. Referans olarak tutuluyor.

### Kurulum
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve --port=3000
```

---

## 🔗 Entegrasyon Mimarisi

```
┌─────────────────┐     REST API      ┌─────────────────┐
│  Next.js Mağaza │ ◄──────────────► │  Laravel API    │
│  (port 3001)    │   Bearer Token    │  (port 8000)    │
└─────────────────┘                   └────────┬────────┘
                                               │
                                        ┌──────┴──────┐
                                        │   MySQL     │
                                        │ (port 3306)  │
                                        └─────────────┘
```

### API Akışı
1. Müşteri Next.js mağazadan sipariş verir
2. Next.js → `POST /api/v1/orders` → Laravel API
3. Laravel stoğu rezerve eder + sipariş oluşturur
4. Müşteri CSPR ile öder → `POST /api/v1/orders/{no}/pay`
5. Laravel fatura keser + stok düşürür + webhook gönderir
6. Next.js webhook alır → katalog yenilenir

### Kupon Kodları
| Kod | İndirim | Koşul |
|---|---|---|
| `ILKSIPARIS` | %15 | min 200₺ |
| `KAMPANYA10` | %10 | min 500₺ |
| `FREEKARGO` | Ücretsiz kargo | — |
| `MEBULA50` | 50₺ | min 400₺ |

---

## 📊 Proje İstatistikleri

| Metrik | Değer |
|---|---|
| Toplam ürün | 75 |
| Toplam kategori | 9 |
| Test sayısı | 107 |
| Next.js sayfa sayısı | 21 |
| API endpoint sayısı | 18 |
| Kullanılan dil | TypeScript + PHP |
| Kod satırı (Next.js) | ~3.000 |
| Kod satırı (Laravel) | ~15.000 |

---

## 📝 Öğrenilen Teknolojiler

- Laravel 11 (Eloquent, Sanctum, Queue, Scheduler, Webhook, Mail)
- Next.js 16 (App Router, Server Components, Route Groups, API Routes)
- Tailwind CSS v4 (Dark Mode, Responsive, Custom Animations)
- MySQL (Migration, Seeding, Transaction, Lock)
- Git & GitHub (Branch, Push, Pull)
- REST API tasarımı
- CSPR (Casper Network) kripto ödeme entegrasyonu
- PWA (Service Worker, Manifest)
- SEO (Sitemap, robots, meta tags, OpenGraph)
- Authentication (JWT, Session, Token-based)
