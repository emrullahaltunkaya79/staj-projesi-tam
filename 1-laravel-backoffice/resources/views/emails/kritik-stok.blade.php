<x-mail::message>
# ⚠ Kritik Stok Uyarısı

**{{ $urun->name }}** ürününün stoğu kritik seviyenin altına düştü. Lütfen tedarik süreçlerini başlatın.

<x-mail::table>
| Ürün | Detay |
|------|-------|
| **Ürün Adı** | {{ $urun->name }} |
| **Stok Kodu (SKU)** | {{ $urun->code ?: '—' }} |
| **Kalan Güncel Stok** | **{{ $urun->stock_quantity }} adet** |
| **Kritik Stok Eşiği** | {{ $urun->min_stock_level }} adet |
| **Önerilen Sipariş Miktarı** | **{{ $onerilen }} adet** |
| **Tedarikçi** | {{ $tedarikci ?: 'Tanımlı değil' }} |
| **Tespit Zamanı** | {{ now()->format('d.m.Y H:i') }} |
</x-mail::table>

@slot('footer')
Bu uyarı, stoğun kritik eşitin ({{ $urun->min_stock_level }} adet) altına düştüğü anda otomatik gönderildi.
Stok yenilenene kadar aynı ürün için tekrar e-posta gönderilmez.
@endslot
</x-mail::message>
