# Sunum Demo Senaryosu (~5 dakika)

> Hedef: sistemi bir işletme sahibi gözüyle uçtan uca gezdirmek.
> Her adımda **ne yaptığınızı söyleyin**, ekranı o işlem üzerinden anlatın.

## Sunum Öncesi Kontrol (2 dakika)

```bash
# 1) MySQL açık olmalı (XAMPP Control Panel → MySQL Start)
# 2) Sunucu:
php artisan serve          # http://127.0.0.1:8000
# 3) (İsteğe bağlı) temiz demo verisi:
php artisan migrate:fresh --seed
```

**Giriş:** `admin@test.com` / `password` (yönetici) · `personel@test.com` / `password` (standart)

---

## Rota (adım adım)

### 1. Giriş + Dashboard (30 sn) — `http://127.0.0.1:8000/panel`
- "Kayıt olurken şirket + yönetici kullanıcı birlikte açılıyor; her şirket kendi verisini görür."
- Kartlar: cari/ürn/fatura tutarı. **Kırmızı uyarı bandını gösterin:** "1 faturanın vadesi geçti" →
  "Tahsilatlar faturalara FIFO dağıtılıyor, gecikme günü otomatik hesaplanıyor."
- Kritik Stok tablosu: "stoğu 5'in altına düşenler."

### 2. Cari kartı + Ekstre (40 sn) — Cariler → Ahmet Market → Detay / Ekstre
- Bilgi kartı, bakiye (pozitif = alacak), **yaşlandırma** (0-30/31-60/60+ gün).
- Ekstre tablosu: vade, kalan, durum rozetleri (Açık / Kısmi ödendi / X gün gecikti).

### 3. Teklif hunisi (45 sn) — Teklifler → TK-000001
- "Teklif stok ve bakiye etkisi yaratmaz" → **Onayla** → **Faturalaştır**.
- "Dönüşüm anında InvoiceService devreye giriyor: stok kontrolü yapılır, yetersizse tüm işlem geri alınır."

### 4. Fatura evrakı (40 sn) — oluşan fatura sayfası
- Resmi düzen: firma, cari, kalemler, "Yalnız ... Türk Lirası" (tutar yazıyla), imza alanları.
- **PDF İndir** (gerçek DomPDF çıktısı) ve **E-posta ile Gönder** (PDF ekli).
- **İade Faturası Kes** → kalem bazlı miktar girme ekranı: "kısmi iade — sadece girilen miktar kadar stok geri yüklenir ve bakiye düşer."

### 5. Tahsilat (30 sn) — cari ekstresindeki "Tahsilat Al"
- Bakiye ipucu: "tahsilat bu tutarı aşamaz." Ödeme girin → bakiye düşer, ekstrede "Kısmi ödendi/Kapandı".

### 6. Ürünler & stok (30 sn) — Ürünler
- Kritik stok filtresi; **Excel** dışa aktarma; **Stok Sayım** listesi → doldur → **Sayım Yükle**
  → "farklar otomatik Düzeltme hareketi olarak işlenir."
- **Stok Hareketleri** sayfası: açılış/alış/satış/iade/düzeltme izi, işlem sonrası stok.

### 7. Raporlar (40 sn) — Raporlar
- 12 aylık ciro grafiği (satış/alış/gider), en çok satanlar, brüt kâr.
- **Kasa Durumu:** nakit/banka (tahsilat − tedarikçi ödemesi − gider).
- **Alacak Yaşlandırma** tablosu.

### 8. Roller (20 sn) — çıkış yapıp `personel@test.com` ile girin
- Menüde Kullanıcılar yok; Sil butonları yok; `…/kullanicilar` → 403.

### 9. Yönetim araçları (25 sn) — tekrar admin ile
- **Etkinlik Günlüğü**: "kim, neyi, ne zaman" — değişen alanlar ve IP.
- **Geri Dönüşüm**: silinen kaydı geri yükleme (soft delete).
- **Şirket Ayarları**: logo yükle → faturada görünür; **Şimdi Yedekle**.

### 10. Kapanış (10 sn)
- "67 otomatik test, CI'da her push'ta çalışıyor; detaylar docs/MIMARI.md'de."

---

## Muhtemel Sorular ve Cevapları

**S: Aynı anda iki kullanıcı aynı ürünü satarsa stok eksiye düşer mi?**
C: Hayır — fatura servisi ürün satırlarını `lockForUpdate` ile kilitler; ikinci
işlem birincinin bitmesini bekler, stok yetmezse tümü geri alınır (rollback).

**S: Başka şirketin verisini görebilir miyim?**
C: Hayır — `BelongsToCompany` global scope tüm sorgulara şirket filtresi ekler;
servis katmanında da ikinci kontrol var; testlerle doğrulandı (403/404).

**S: Personel faturaya istediği fiyatı girebilir mi?**
C: Admin dışı kullanıcılar ürünün alış–satış fiyat aralığının dışına çıkamaz;
uç kontrol sunucu tarafında yapılır.

**S: Fatura yanlış kesilirse?**
C: Tam veya kalem bazlı kısmi iade — stok geri yüklenir, bakiye iade tutarı
kadar düşer; kalan iade hakkı takip edilir.

**S: Ödeme-fatura eşleşmesi nasıl?**
C: Tahsilatlar en eski faturadan başlayarak FIFO dağıtılır; her faturanın kalan
tutarı ve gecikme günü buna göre hesaplanır (`ReceivableService`).

**S: E-posta/sunucu ayarları?**
C: `MAIL_MAILER=log` ile geliştirmede e-postalar log'a yazılır; production'da
SMTP verilir, e-postalar kuyruklanır (`queue:work`). Yedekler haftalık ve
elle alınabilir (spatie/laravel-backup).
