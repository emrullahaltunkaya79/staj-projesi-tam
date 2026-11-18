# Canlıya Alma Kontrol Listesi — Entegrasyon Denetimi

> Denetim tarihi: 18.11.2026 · Tüm canlı sağlık testleri bu tarihte, çalışan sistem üzerinde yapıldı.

## 1) Canlı Sağlık Testi Sonuçları (bugün, yeşil)

| # | Test | Sonuç |
|---|------|-------|
| 1 | Mağaza token'ı ile `GET /api/v1/products` | **200** · 0.28s · gerçek katalog JSON |
| 2 | Görsel proxy `GET :3001/api/img/6` | **200** · 90 KB görsel |
| 3 | Mağaza ana sayfa `:3001/` | **200** |
| 4 | Panel `:8000/panel` | **302** (oturumsuz → /giris yönlendirme, beklenen) |
| 5 | Webhook teslimatları (son 5) | **5/5 success, kod 200** — imza eşleşmesi sağlam |
| 6 | `mail:test` | Hattı çalışıyor — log sürücüsüne yazdı (canlıda SMTP'ye geçecek) |
| 7 | Sağlık parametreleri | webhook_url tanımlı, secret 48 karakter, jobs tablosu mevcut |

## 2) Entegrasyon Envanteri ve Canlıya Adımları

### A. Panel ↔ Mağaza (çekirdek entegrasyon) — SAĞLIKLI
- **Mağaza → Panel**: `/api/v1/*` (Sanctum + throttle; ürünler, siparişler, yorumlar, sorular, bildirimler)
- **Panel → Mağaza**: webhook'lar (`stock.changed`, `product.upserted/deleted`, `order.paid/cancelled/returned/shipped/delivered`)
  - HMAC-SHA256 imza (`X-Signature`, `Setting::webhookSecret`), retry destekli, `webhook_deliveries` izleme
- **Görsel hattı**: `/api/img/[id]` proxy + globalThis önbellek + webhook invalidasyon
- **Canlıya**: `Setting::webhook_url` ve mağazadaki `BACKOFFICE_WEBHOOK_SECRET` gerçek domain'e taşınınca aynı secret'ı **iki tarafta da** güncelle.

### B. E-Posta — DEV MODUNDA, GEÇİŞ ŞART
- Akışlar: sipariş/fatura PDF maili (base64, kuyruklu), teklif, kritik stok (anti-spam damgalı), e-posta doğrulama, şifre sıfırlama, fiyat/stok alarmı, günün özeti.
- **Canlıya**: `.env` → `MAIL_MAILER=smtp` + gerçek SMTP host/port/kullanıcı/şifre; `MAIL_FROM_ADDRESS` gerçek adres. Sonra `php artisan mail:test --to=...` ile **gerçek gönderim** doğrula.
- Kritik stok alıcısı: `KRITIK_STOK_MAIL_ALICI=emrullahaltunkaya79@gmail.com` ✓

### C. Kuyruk — DEV MODUNDA (sync), GEÇİŞ ŞART
- Kuyruklanan işler: tüm mail'ler (PDF'li!), webhook gönderimleri.
- `jobs` tablosu migrasyonu mevcut ✓
- **Canlıya**: `QUEUE_CONNECTION=database` → `php artisan queue:work` servis olarak (supervisor/NSSM) veya systemd.
- Neden şart: sync kuyrukta PDF'li mail **istek içini bloklar**, canlıda zaman aşımı yaratır.

### D. Zamanlanmış Görevler — 10 görev tanımlı, ÇALIŞTIRICI ŞART
`bildirim:gundelik-ozet (08:00) · tekrarlayan:faturalar-kes (00:05) · siparisler:rezerv-serbest-birak (5dk) · casper:odemeler-kontrol (2dk) · backup:run --only-db (Pzt 03:00) · stok:bildirim-gonder (15dk) · fiyat:alarm-gonder (15dk) · gonder:sepet-hatirlatma (saatlik) · gonder:dogum-gunu-kuponlari (09:30) · gorsel:temizle (Paz 03:30)`
- **Canlıya**: Windows → `php artisan schedule:work` servisi; Linux → cron: `* * * * * php /yol/artisan schedule:run`
- **Canlıda kritik**: backup görevi yalnız Pzt 03:00 — sıkılaştırılabilir.

### E. Ödeme — SIMÜLASYON MODUNDA, ANAHTARLA GERÇEĞE GEÇER
- `OdemeGatewayFactory`: `IYZICO_API_KEY` + `IYZICO_SECRET` dolunca **otomatik gerçek iyzico 3DS**, boşken tam akışlı mock simülasyon.
- **Canlıya**: iyzico gerçekmerchant anahtarları + `IYZICO_BASE_URL=https://api.iyzipay.com` (sandbox değil!) + HTTPS zorunlu (3DS callback).
- CSPR kripto ödeme: `CASPER_NETWORK=testnet` → canlıda `mainnet` kararı (veya özelliği kapat).

### F. Google Girişi — DEMO MODU AKTİF, KENDİ KENDİNİ KAPATIR
- `googleDemoLogin`: gerçek `GOOGLE_CLIENT_ID` tanımlıysa **403 döner** (otomatik kapanır) ✓; satıcı/panel hesabına girişi engeller ✓
- **Canlıya**: Google Cloud Console'da `GOOGLE_CLIENT_ID/SECRET` + `GOOGLE_REDIRECT_URI=https://gercek-domain/auth/google/callback` + `GOOGLE_STORE_NEXT=https://magaza-domain`.

### G. Alan Adı / Oturum / Güvenlik — GEÇİŞ LİSTESİ
| Ayar | Şu an (dev) | Canlı değeri |
|---|---|---|
| `APP_ENV` | local | **production** |
| `APP_DEBUG` | true | **false** |
| `APP_URL` | http://localhost | https://panel-domain |
| `BACKOFFICE_URL` (mağaza .env.local) | 127.0.0.1:8000 | https://panel-domain/api/v1 |
| `SESSION_SECURE_COOKIE` | — | **true** (HTTPS'te) |
| `MAIL_MAILER` | log | **smtp** |
| `QUEUE_CONNECTION` | sync | **database** |
| webhook URL (Setting) | 127.0.0.1:3001 | https://magaza-domain/api/webhook/backoffice |

- Sonra: `php artisan config:cache route:cache view:cache` + `storage:link`.
- **Canlıdan önce bir kez daha**: `php artisan test` (212 test) yeşil olmalı.

### H. Otomatik Korumalar (dikkat — bilinçli kapanışlar)
- Demo Google girişi → client_id gelince otomatik 403 ✓
- Mock ödeme → iyzico anahtarı gelince otomatik gerçek akış ✓
- Test casusları: phpunit proses-sigortası gerçek DB'yi korur (APP_CONFIG_CACHE ayrımı) ✓

## 3) Önerilen Canlıya Alma Sırası

1. Alan adları + HTTPS kur (panel + mağaza), SSL doğrula.
2. Sunucuda `.env` yukarıdaki tabloya göre güncelle; **aynı webhook secret'ı iki tarafa da** yaz.
3. `composer install --no-dev --optimize-autoloader` → `php artisan migrate --force` (yedek sonrası!).
4. `config:cache`, `route:cache`, `view:cache`, `storage:link`.
5. **Queue worker** servisini başlat (`php artisan queue:work`) — yoksa mail'ler gitmez.
6. **Scheduler** servisini/cron'unu başlat — yoksa rezerv, fatura kesme, alarm, backup çalışmaz.
7. `php artisan mail:test --to=...` → gerçek SMTP'den gelmeli.
8. Uçtan uca: mağazadan test siparişi → panelde düşer → webhook 200 → stok günceller → PDF mail gelir.
9. `webhook_deliveries` tablosunu panelde kontrol et (hepsi 200).
10. Bakiye/kasa demo verilerini temizle: `php artisan canli-demo-temizle` (varsa) veya manuel.

## 4) Bugün Yeşil Olan, Değişiklik İstemeyenler
- Sanctum API + throttle'lar, webhook imza/retry zinciri, görsel GC hattı,
  RBAC (gate+middleware), kritik stok anti-spam, iade/stok defter tutarlılığı —
  hepsi 212 testle ve canlı testlerle doğrulandı.
