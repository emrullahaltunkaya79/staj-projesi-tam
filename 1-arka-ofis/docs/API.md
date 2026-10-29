# API v1 — E-ticaret Entegrasyonu

Ön muhasebe sisteminizi bir e-ticaret sitesine bağlamak için REST API.
Kimlik doğrulama: **Laravel Sanctum** (Bearer token).

## Token Alma

1. Panele giriş yapın → **Kullanıcı dropdown → API Tokenlar**
2. Token adı girin + şifrenizi onaylayın → token değerini kopyalayın
3. Token yalnızca **bir kez** gösterilir

## Genel Kurallar

| Kural | Değer |
|---|---|
| Base URL | `http://127.0.0.1:8000/api/v1` |
| Auth header | `Authorization: Bearer <token>` |
| Accept header | `Accept: application/json` |
| Rate limit | Dakikada 60 istek |
| Hata formatı | `{ "message": "...", "errors": { alan: [mesaj] } }` |
| Şirket izolasyonu | Token sahibi kullanıcının şirketiyle sınırlı |

---

## Endpoint'ler

### GET /api/v1/products

Ürün kataloğu (e-ticaret ürün senkronizasyonu).

**Query parametreleri:** `category_id` (opsiyonel)

```json
{
  "data": [
    {
      "id": 1,
      "code": "URN-001",
      "name": "Klavye",
      "category": "Bilgisayar",
      "unit": "adet",
      "sale_price": 450.0,
      "stock_quantity": 25,
      "in_stock": true
    }
  ]
}
```

### GET /api/v1/products/{id}

Tek ürün detayı. Bulunamazsa 404.

### GET /api/v1/stock

**Canlı stok sorgusu** — ödeme anında stok teyidi için.

**Query:** `product_ids[]=1&product_ids[]=2` (opsiyonel — yoksa tümü)

```json
{
  "checked_at": "2026-10-26T12:00:00+03:00",
  "data": [
    { "id": 1, "name": "Klavye", "stock_quantity": 25, "in_stock": true }
  ]
}
```

### POST /api/v1/customers

E-ticaret müşterisini sistem carisine eşleştirir/oluşturur. **Idempotent** — aynı e-posta tekrar gönderilirse mevcut cari döner.

**Body:**
```json
{ "name": "Ahmet Yılmaz", "email": "ahmet@ornek.com", "phone": "0532...", "tax_number": "123..." }
```

**Yanıt:** `201` (yeni) veya `200` (mevcut) → `{ "data": { "contact_id": 5, "name": "..." } }`

### POST /api/v1/orders

**En önemli endpoint** — e-ticaret siparişini sisteme işler:
- Otomatik **satış faturası** kesilir (stok kontrolü + bakiye + QR kod dahil)
- Stok düşer, cari bakiyesi artar
- Stok yetersizse **422** döner ve hiçbir veri değişmez (rollback)

**Body:**
```json
{
  "contact_id": 5,
  "warehouse_id": 1,          // opsiyonel — depo stoğundan düş
  "tax_rate": 20,             // 0 | 1 | 10 | 20
  "due_date": "2026-11-30",   // opsiyonel
  "items": [
    { "product_id": 1, "quantity": 2 },
    { "product_id": 3, "quantity": 1, "unit_price": 3000 }
  ]
}
```

> `unit_price` gönderilmezse **ürünün satış fiyatı** (varsa cariye özel fiyat olsaydı o) kullanılır — panelde kesilen faturalarla aynı davranış.

**201 Yanıt:**
```json
{
  "message": "Sipariş işlendi ve fatura kesildi.",
  "data": {
    "invoice_id": 15,
    "invoice_no": "SF-000015",
    "total": 1080.0,
    "verification_url": "http://127.0.0.1:8000/fatura/dogrula/ABC123...",
    "items": [...]
  }
}
```

**422 (stok yetersiz):**
```json
{
  "message": "...",
  "errors": { "items": ["Yetersiz stok! ..."] }
}
```

### GET /api/v1/tokens

Aktif token listesi (ad, oluşturma, son kullanım).

---

## Token Yönetimi

Panele giriş → kullanıcı dropdown → **API Tokenlar**:
- Token oluştur (şifre onaylı)
- Token iptal et
- Son kullanım tarihi izleme

## Örnek: E-ticaret sipariş akışı (PHP/cURL)

```php
// 1) Stok kontrol
$stock = json_decode(file_get_contents('http://panel.local/api/v1/stock', false, stream_context_create([
    'http' => ['header' => "Authorization: Bearer TOKEN\r\nAccept: application/json"]
])), true);

// 2) Sipariş gönder
$ch = curl_init('http://panel.local/api/v1/orders');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer TOKEN',
        'Accept: application/json',
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'contact_id' => 5,
        'tax_rate' => 20,
        'items' => [['product_id' => 1, 'quantity' => 2]],
    ]),
]);
$order = json_decode(curl_exec($ch), true);
echo $order['data']['invoice_no']; // SF-000001
```
