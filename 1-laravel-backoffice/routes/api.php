<?php

use App\Http\Controllers\Api\V1\AccountApiController;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\CouponApiController;
use App\Http\Controllers\Api\V1\ProductApiController;
use App\Http\Controllers\Api\V1\QuestionApiController;
use App\Http\Controllers\Api\V1\SellerStatsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — E-ticaret entegrasyonu
|--------------------------------------------------------------------------
| Tüm endpoint'ler Sanctum token ister: Authorization: Bearer {token}
| Rate limit: token başına dakikada 300 istek.
*/

// Auth (token gerektirmez — throttle'lı)
Route::prefix('v1')->middleware('throttle:20,1')->group(function () {
    Route::post('/auth/register', [AuthApiController::class, 'register'])->name('api.auth.register');
    Route::post('/auth/login', [AuthApiController::class, 'login'])->name('api.auth.login');
    Route::post('/auth/forgot-password', [AuthApiController::class, 'forgotPassword'])->name('api.auth.forgot');
    Route::post('/auth/reset-password', [AuthApiController::class, 'resetPassword'])->name('api.auth.reset');
    Route::post('/auth/verify-email', [AuthApiController::class, 'verifyEmail'])->name('api.auth.verify');
    Route::post('/auth/resend-verification', [AuthApiController::class, 'resendVerification'])->name('api.auth.resend');
    Route::post('/auth/google/exchange', [AuthApiController::class, 'googleExchange'])->name('api.auth.google-exchange');
    Route::get('/auth/google/durum', [AuthApiController::class, 'googleDurum'])->name('api.auth.google-durum');
    Route::post('/auth/google/demo-login', [AuthApiController::class, 'googleDemoLogin'])->name('api.auth.google-demo');
});

// Müşteri hesabı — müşterinin kendi token'ıyla
Route::middleware(['auth:sanctum', 'throttle:120,1'])
    ->prefix('v1')
    ->group(function () {
        Route::get('/auth/me', [AuthApiController::class, 'me'])->name('api.auth.me');
        Route::post('/auth/logout', [AuthApiController::class, 'logout'])->name('api.auth.logout');

        Route::post('/reviews/{review}/faydali', [ProductApiController::class, 'faydaliToggle'])->whereNumber('review')->name('api.reviews.faydali');

        Route::put('/hesap/profil', [AccountApiController::class, 'profil'])->name('api.hesap.profil');
        Route::put('/hesap/sifre', [AccountApiController::class, 'sifre'])->name('api.hesap.sifre');
        Route::get('/hesap/adresler', [AccountApiController::class, 'adresler'])->name('api.hesap.adresler');
        Route::post('/hesap/adresler', [AccountApiController::class, 'adresEkle'])->name('api.hesap.adres-ekle');
        Route::delete('/hesap/adresler/{id}', [AccountApiController::class, 'adresSil'])->whereNumber('id')->name('api.hesap.adres-sil');
        Route::get('/hesap/durum', [AccountApiController::class, 'durum'])->name('api.hesap.durum');
        Route::put('/hesap/durum', [AccountApiController::class, 'durumKaydet'])->name('api.hesap.durum-kaydet');
    });

// Mağaza sunucusu ve panel — müşteri token'ları bu uçlara erişemez (başkasının siparişi, ürün CRUD vb.)
// Görsel proxy'si sayfa başına ~75 istek atar, limit buna göre yüksek
Route::middleware(['auth:sanctum', 'musteri.engelle', 'throttle:300,1'])
    ->prefix('v1')
    ->group(function () {
        // Ürün kataloğu
        Route::get('/products', [ApiController::class, 'products'])->name('api.products');
        Route::get('/products/{id}', [ApiController::class, 'productShow'])->whereNumber('id')->name('api.products.show');
        Route::get('/products/{id}/image/{index}', [ApiController::class, 'productImage'])->whereNumber('id')->whereNumber('index')->name('api.products.image');

        // Ürün CRUD (satıcı paneli) + görsel + yorumlar
        Route::post('/products', [ProductApiController::class, 'store'])->name('api.products.store');
        Route::put('/products/{id}', [ProductApiController::class, 'update'])->whereNumber('id')->name('api.products.update');
        Route::delete('/products/{id}', [ProductApiController::class, 'destroy'])->whereNumber('id')->name('api.products.destroy');
        Route::post('/products/{id}/images', [ProductApiController::class, 'uploadImage'])->whereNumber('id')->name('api.products.images');
        Route::get('/products/{id}/reviews', [ProductApiController::class, 'reviews'])->whereNumber('id')->name('api.products.reviews');
        Route::get('/yorumlar', [ProductApiController::class, 'yorumModerasyon'])->name('api.yorumlar');
        Route::patch('/yorumlar/{id}/onay', [ProductApiController::class, 'yorumOnay'])->whereNumber('id')->name('api.yorumlar.onay');
        Route::delete('/yorumlar/{id}', [ProductApiController::class, 'yorumSil'])->whereNumber('id')->name('api.yorumlar.sil');
        Route::post('/products/{id}/reviews', [ProductApiController::class, 'addReview'])->whereNumber('id')->name('api.products.reviews.add');

        // Canlı stok
        Route::get('/stock', [ApiController::class, 'stock'])->name('api.stock');

        // Satıcı paneli özeti
        Route::get('/stats/seller', [SellerStatsController::class, 'index'])->name('api.stats.seller');

        // Kuponlar
        Route::post('/coupons/validate', [CouponApiController::class, 'validateCoupon'])->name('api.coupons.validate');
        Route::get('/coupons', [CouponApiController::class, 'index'])->name('api.coupons.index');
        Route::post('/coupons', [CouponApiController::class, 'store'])->name('api.coupons.store');
        Route::delete('/coupons/{id}', [CouponApiController::class, 'destroy'])->whereNumber('id')->name('api.coupons.destroy');

        // Müşteri (idempotent: e-posta eşleşirse mevcut cari döner)
        Route::post('/customers', [ApiController::class, 'createCustomer'])->name('api.customers');

        // Sipariş → rezerve stok (ödeme gelince faturalaşır)
        Route::post('/orders', [ApiController::class, 'createOrder'])->name('api.orders');
        Route::get('/orders', [ApiController::class, 'ordersIndex'])->name('api.orders.index');
        Route::get('/orders/{orderNo}', [ApiController::class, 'orderShow'])->name('api.orders.show');
        Route::post('/orders/{orderNo}/pay', [ApiController::class, 'pay'])->name('api.orders.pay');
        Route::post('/orders/{orderNo}/cancel', [ApiController::class, 'cancel'])->name('api.orders.cancel');
        Route::post('/orders/{orderNo}/return-request', [ApiController::class, 'requestReturn'])->name('api.orders.return-request');
        Route::get('/orders/{orderNo}/invoice.pdf', [ApiController::class, 'invoicePdf'])->name('api.orders.invoice-pdf');
        Route::post('/orders/{orderNo}/return-approve', [ApiController::class, 'approveReturn'])->name('api.orders.return-approve');
        Route::post('/orders/{orderNo}/shipping', [ApiController::class, 'updateShipping'])->name('api.orders.shipping');

        // Satış odaklı öneriler + müşteri bildirim kayıtları
        Route::get('/products/arama', [ApiController::class, 'arama'])->name('api.products.arama');
        Route::get('/products/{id}/also-bought', [ApiController::class, 'alsoBought'])->whereNumber('id')->name('api.products.also-bought');
        Route::get('/reviews/latest', [ApiController::class, 'latestReviews'])->name('api.reviews.latest');
        Route::post('/stock-alerts', [ApiController::class, 'stockAlert'])->name('api.stock-alerts');
        Route::post('/price-alerts', [ApiController::class, 'priceAlert'])->name('api.price-alerts');

        // Ürün soru-cevap
        Route::get('/products/{id}/sorular', [QuestionApiController::class, 'index'])->whereNumber('id')->name('api.sorular.urun');
        Route::post('/products/{id}/sorular', [QuestionApiController::class, 'store'])->whereNumber('id')->name('api.sorular.ekle');
        Route::get('/sorular', [QuestionApiController::class, 'satici'])->name('api.sorular.satici');
        Route::patch('/sorular/{id}/cevap', [QuestionApiController::class, 'cevapla'])->whereNumber('id')->name('api.sorular.cevap');
        Route::delete('/sorular/{id}', [QuestionApiController::class, 'sil'])->whereNumber('id')->name('api.sorular.sil');

        Route::post('/newsletter', [ApiController::class, 'newsletterSubscribe'])->name('api.newsletter');
        Route::get('/bildirimler', [ApiController::class, 'bildirimKayitlari'])->name('api.bildirimler');
        Route::post('/iletisim', [ApiController::class, 'iletisimKayit'])->name('api.iletisim.kayit');
        Route::get('/iletisim', [ApiController::class, 'iletisimListesi'])->name('api.iletisim.liste');

        // Aktif token listesi (yönetim)
        Route::get('/tokens', [ApiController::class, 'tokens'])->name('api.tokens');

        // CSPR ödeme: talimat üretimi (QR) + durum sorgusu (e-ticaret polling)
        Route::post('/orders/{orderNo}/cspr-payment', [ApiController::class, 'csprPayment'])->name('api.cspr.payment');
        Route::get('/cspr-payments/{memo}', [ApiController::class, 'csprStatus'])->name('api.cspr.status');
    });
