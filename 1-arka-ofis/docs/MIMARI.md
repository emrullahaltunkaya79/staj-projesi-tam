# Mimarî ve Veritabanı Dokümanı

> Ön Muhasebe & Stok Takip Sistemi — Laravel 11 + Tailwind CSS v3 + MySQL/SQLite
> Bu doküman staj raporu ve kod incelemeleri için hazırlanmıştır.

## 1. Genel Bakış

Çok kiracılı (multi-tenant) bir ön muhasebe sistemi: her **şirket** yalnız kendi
verisini görür. Temel döngü — **Teklif → Satış Faturası → Tahsilat** ve
**Alış Faturası → Tedarikçi Ödemesi** — tamamı stok hareketi ve kasa iziyle
izlenir; iade, gider, vade/yaşlandırma ve raporlama ile tamamlanır.

**Teknolojiler:** PHP 8.4 / Laravel 11, Tailwind CSS v3 (Vite), MySQL (dev:
MariaDB) / SQLite (test), Chart.js, DomPDF, Maatwebsite Excel, spatie/laravel-backup.

## 2. Mimari Katmanlar

```
HTTP İsteği
   │
   ▼
Route'lar (routes/web.php — Türkçe resource route'lar, throttle'lu auth)
   │
   ▼
Middleware  ── auth · guest · signed · throttle:auth · throttle:password-reset
   │
   ▼
Controller (app/Http/Controllers)  ←── Form Request doğrulaması (app/Http/Requests)
   │            └── yetki kontrolleri Policy'lerle (app/Policies)
   ▼
Servis Katmanı (app/Services)  ── İŞ KURALLARI · DB::transaction · lockForUpdate
   │
   ▼
Model (app/Models) + Trait'ler ── BelongsToCompany (çok kiracılılık)
   │                              RecordsActivity (denetim izi)
   ▼                                SoftDeletes (geri alınabilir silme)
Blade View'ları + Servis tarafı yan etkiler
(stok hareketi, bakiye, e-posta, denetim kaydı)
```

**Servisler:**

| Servis | Sorumluluk |
|---|---|
| `InvoiceService` | Satış/alış faturası kesme (stok kontrolü + fiyat yetkisi), kısmi/tam iade — hepsi tek `DB::transaction` içinde satır kilidiyle |
| `PaymentService` | Müşteri tahsilatı ve tedarikçi ödemesi (bakiye aşımı engelli, rollback'li) |
| `QuoteService` | Teklif oluştur/düzenle/durum değiştir; onaylı teklifi faturaya dönüştür |
| `ReceivableService` | Tahsilatların faturalara FIFO dağıtımı, kalan tutar, gecikme günü, yaşlandırma kovaları |

## 3. Veritabanı Şeması (ERD)

```mermaid
erDiagram
    COMPANIES ||--o{ USERS : "çalışanlar"
    COMPANIES ||--o{ CONTACTS : "cariler"
    COMPANIES ||--o{ PRODUCTS : "ürünler"
    COMPANIES ||--o{ INVOICES : "faturalar"
    COMPANIES ||--o{ PAYMENTS : "tahsilat/ödeme"
    COMPANIES ||--o{ QUOTES : "teklifler"
    COMPANIES ||--o{ EXPENSES : "giderler"
    COMPANIES ||--o{ RECURRING_INVOICES : "tekrarlayan"
    COMPANIES ||--o{ STOCK_MOVEMENTS : "stok hareketi"
    COMPANIES ||--o{ ACTIVITY_LOGS : "denetim"

    CONTACTS ||--o{ INVOICES : "kesilen"
    CONTACTS ||--o{ PAYMENTS : "tahsilat/ödeme"
    CONTACTS ||--o{ QUOTES : "teklifler"

    INVOICES ||--o{ INVOICE_ITEMS : "kalemler"
    PRODUCTS |o--o{ INVOICE_ITEMS : "ürün (snapshot isim)"
    INVOICES |o--o{ INVOICES : "iade (refunded_invoice_id)"
    QUOTES |o--o| INVOICES : "dönüşen fatura"
    QUOTES ||--o{ QUOTE_ITEMS : "kalemler"
    PRODUCTS |o--o{ QUOTE_ITEMS : "ürün"

    PRODUCTS |o--o{ STOCK_MOVEMENTS : "hareketler"
    INVOICES |o--o{ STOCK_MOVEMENTS : "kaynak fatura"
    CONTACTS ||--o{ RECURRING_INVOICES : "kural carisi"

    USERS |o--o{ ACTIVITY_LOGS : "kim yaptı"

    COMPANIES {
        bigint id PK
        string name
        string logo_path "nullable"
        string tax_number
    }
    USERS {
        bigint id PK
        bigint company_id FK
        enum role "admin|kullanici"
        string email UK
        timestamp email_verified_at
        datetime deleted_at "soft delete"
    }
    CONTACTS {
        bigint id PK
        bigint company_id FK
        enum type "musteri|tedarikci"
        string name
        decimal balance "pozitif=alacak"
        datetime deleted_at
    }
    PRODUCTS {
        bigint id PK
        bigint company_id FK
        string code name
        decimal purchase_price sale_price
        int stock_quantity
        datetime deleted_at
    }
    INVOICES {
        bigint id PK
        bigint company_id FK
        bigint contact_id FK
        enum type "satis|alis|iade"
        string invoice_no UK "SF|AF|IF-000001"
        date issue_date due_date
        bigint refunded_invoice_id FK "nullable"
        decimal subtotal tax_amount total
    }
    INVOICE_ITEMS {
        bigint id PK
        bigint invoice_id FK
        bigint product_id FK "nullable"
        string product_name "snapshot"
        decimal quantity unit_price line_total
    }
    PAYMENTS {
        bigint id PK
        bigint company_id contact_id FK
        decimal amount
        enum payment_type "nakit|havale"
        enum direction "tahsilat|odeme"
    }
    QUOTES {
        bigint id PK
        bigint company_id contact_id FK
        string quote_no UK "TK-000001"
        enum status "acik|onaylandi|reddedildi|faturalandi"
        bigint converted_invoice_id FK "nullable"
        datetime deleted_at
    }
    QUOTE_ITEMS {
        bigint id PK
        bigint quote_id FK
        bigint product_id FK "nullable"
        decimal quantity unit_price line_total
    }
    STOCK_MOVEMENTS {
        bigint id PK
        bigint company_id product_id FK
        enum type "acilis|alis|satis|iade|duzeltme"
        enum direction "giris|cikis"
        decimal quantity stock_after
        bigint invoice_id FK "nullable"
    }
    EXPENSES {
        bigint id PK
        bigint company_id FK
        string category
        decimal amount
        date expense_date
        enum payment_type
        datetime deleted_at
    }
    RECURRING_INVOICES {
        bigint id PK
        bigint company_id contact_id FK
        string title
        tinyint day_of_month "1-28"
        json items
        boolean is_active
        date last_run_at
    }
    ACTIVITY_LOGS {
        bigint id PK
        bigint company_id user_id FK "nullable"
        string action "created|updated|deleted|restored"
        string model_type model_id
        json changes
        string ip
    }
```

> Laravel'in sistem tabloları (`sessions`, `cache`, `jobs`, `password_reset_tokens`)
> şemadan bağımsız çalışır; `jobs` tablosu e-posta kuyruğunu taşır.

## 4. Temel Tasarım Kararları

### 4.1 Çok kiracılılık — `BelongsToCompany` trait'i
Global scope, giriş yapan kullanıcının `company_id`'si dışındaki tüm kayıtları
sorgularda otomatik filtreler; `creating` olayı yeni kayıtlara şirketi otomatik
yazar. Seeder/CLI'de (oturum yok) etkisizdir — servisler bu durumda şirket ID'sini
parametreyle alır.

### 4.2 Transaction + satır kilidi deseni — `InvoiceService`
Fatura kesmek tek bir veritabanı işlemidir:
1. `DB::beginTransaction()`
2. Cari ve ürünler `lockForUpdate()` ile kilitlenir (eşzamanlı kesimde stok yarışı olamaz)
3. Stok yeterliliği + (admin dışı için) fiyat aralığı kontrolü — hata varsa `ValidationException` → **otomatik rollback**, hiçbir veri değişmez
4. Fatura + kalemler + stok hareketleri yazılır; stok düşürülür/artırılır; cari bakiyesi güncellenir
5. `DB::commit()` — herhangi bir adım patlarsa `DB::rollBack()`

Aynı desen `PaymentService` (bakiye aşımı) ve `QuoteService::convertToInvoice`
(stok yetersizse dönüşüm geri alınır) için de kullanılır.

### 4.3 FIFO tahsilat dağıtımı — `ReceivableService`
Tahsilatlar en eski faturadan başlayarak dağıtılır; her fatura için **kalan
tutar** ve **gecikme günü** hesaplanır. Yaşlandırma (0-30/31-60/60+ gün) ve
"vadesi geçti" dashboard uyarısı bu servisten beslenir. İade edilmiş faturalar
kalan alacak bırakmaz.

### 4.4 Denetim izi + geri alınabilir silme
`RecordsActivity` trait'i tüm model olaylarını (değişen alanlarla birlikte)
`activity_logs`'a yazar; cari/ürün/gider/teklif/kullanıcı silmeleri **soft
delete**'tir ve Geri Dönüşüm sayfasından `restore` edilir.

### 4.5 Yetkilendirme ve doğrulama
- Roller: `admin` (kullanıcı yönetimi, silme yetkisi, sınırsız fiyat) / `kullanici`
- Model bazlı yetkiler Laravel **Policy**'lerinde (`ContactPolicy`, `ProductPolicy`,
  `ExpensePolicy`, `QuotePolicy`, `UserPolicy`, `RecurringInvoicePolicy`)
- Doğrulama **Form Request** sınıflarında (Türkçe mesajlı)
- Girişte ve şifre sıfırlamada **rate limiting** (5/dk, 3/dk)

### 4.6 Zamanlanmış görevler ve kuyruk (`routes/console.php` + cron)
| Görev | Zaman | İş |
|---|---|---|
| `bildirim:gundelik-ozet` | her gün 08:00 | kritik stok + vadesi geçen/yaklaşan faturalar yöneticilere e-posta |
| `tekrarlayan:faturalar-kes` | her gün 00:05 | ayın günü gelen kurallar için otomatik satış faturası |
| `backup:run --only-db` | her hafta Pazartesi 03:00 | veritabanı zip yedeği |

E-postalar `ShouldQueue` ile `jobs` tablosuna kuyruklanır (`queue:work` ile işlenir).

## 5. Güvenlik Önlemleri

- CSRF (tüm formlar), oturum yenileme giriş/çıkışta, şifre `hashed` cast
- Şifre sıfırlama tüm oturumları geçersiz kılar; "Şifre Değiştir" diğer cihazları düşürür
- Signed URL ile e-posta doğrulama bağlantısı
- Sunucu tarafı fiyat yetkisi (admin dışı alış-satış aralığına sıkıştırılmış)
- Şirketler arası erişim global scope + servislerde elle şirket kontrolüyle çifte katmanlı
- Çift gönderim koruması (istemci) + idempotentlık-kritik akışlarda transaction (sunucu)

## 6. Test Stratejisi

**67 PHPUnit testi / 272 doğrulama** (`php artisan test`, SQLite bellek-içi).
Kapsam: fatura akışları (stok/bakiye/rollback), kısmi iade, tedarikçi ödemesi,
teklif→fatura dönüşümü (stok yetersizlik dahil), çok kiracılılık sızıntıları,
rol/fiyat yetkileri, rate limiting, şifre akışları, Excel içe/dışa aktarma,
denetim/geri dönüşüm, bildirim komutları, PDF üretimi.

CI: `.github/workflows/ci.yml` — her push/PR'da PHPUnit + Vite build (GitHub Actions).

## 7. Klasör Haritası (özet)

```
app/
 ├ Console/Commands/        bildirim:gundelik-ozet, tekrarlayan:faturalar-kes
 ├ Exports/                 Excel dışa aktarmalar + içe aktarma şablonları
 ├ Http/
 │   ├ Controllers/         15 denetleyici
 │   ├ Middleware/          EnsureUserIsAdmin
 │   └ Requests/            Form Request doğrulamaları
 ├ Mail/                    DailySummaryMail, InvoiceMail (kuyruklu)
 ├ Models/                  13 model
 ├ Policies/                6 policy
 ├ Services/                4 servis (iş kuralları)
 ├ Support/                 Money (TL yazıyla)
 └ Traits/                  BelongsToCompany, RecordsActivity
docs/                       MIMARI.md, DEMO.md
public/js/                  invoice-form.js, global-search.js
resources/views/            20+ Blade görünümü
tests/Feature/              12 test dosyası
```
