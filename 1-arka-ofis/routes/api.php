<?php

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\CouponApiController;
use App\Http\Controllers\Api\V1\ProductApiController;
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
});

// Auth (token gerektirir) — görsel proxy'si sayfa başına ~75 istek atar, limit buna göre yüksek
Route::middleware(['auth:sanctum', 'throttle:300,1'])
    ->prefix('v1')
    ->group(function () {
        Route::get('/auth/me', [AuthApiController::class, 'me'])->name('api.auth.me');
        Route::post('/auth/logout', [AuthApiController::class, 'logout'])->name('api.auth.logout');

        // Ürün kataloğu
        Route::get('/products', [ApiController::class, 'products'])->name('api.products');
        Route::get('/products/{id}', [ApiController::class, 'productShow'])->whereNumber('id')->name('api.products.show');

        // Ürün CRUD (satıcı paneli) + görsel + yorumlar
        Route::post('/products', [ProductApiController::class, 'store'])->name('api.products.store');
        Route::put('/products/{id}', [ProductApiController::class, 'update'])->whereNumber('id')->name('api.products.update');
        Route::delete('/products/{id}', [ProductApiController::class, 'destroy'])->whereNumber('id')->name('api.products.destroy');
        Route::post('/products/{id}/images', [ProductApiController::class, 'uploadImage'])->whereNumber('id')->name('api.products.images');
        Route::get('/products/{id}/reviews', [ProductApiController::class, 'reviews'])->whereNumber('id')->name('api.products.reviews');
        Route::post('/products/{id}/reviews', [ProductApiController::class, 'addReview'])->whereNumber('id')->name('api.products.reviews.add');

        // Canlı stok
        Route::get('/stock', [ApiController::class, 'stock'])->name('api.stock');

        // Satıcı paneli özeti
        Route::get('/stats/seller', [SellerStatsController::class, 'index'])->name('api.stats.seller');

        // Kuponlar
        Route::post('/coupons/validate', [CouponApiController::class, 'validateCoupon'])->name('api.coupons.validate');
        Route::get('/coupons', [CouponApiController::class, 'index'])->name('api.coupons.index');

        // Müşteri (idempotent: e-posta eşleşirse mevcut cari döner)
        Route::post('/customers', [ApiController::class, 'createCustomer'])->name('api.customers');

        // Sipariş → rezerve stok (ödeme gelince faturalaşır)
        Route::post('/orders', [ApiController::class, 'createOrder'])->name('api.orders');
        Route::get('/orders', [ApiController::class, 'ordersIndex'])->name('api.orders.index');
        Route::get('/orders/{orderNo}', [ApiController::class, 'orderShow'])->name('api.orders.show');
        Route::post('/orders/{orderNo}/pay', [ApiController::class, 'pay'])->name('api.orders.pay');
        Route::post('/orders/{orderNo}/cancel', [ApiController::class, 'cancel'])->name('api.orders.cancel');
        Route::post('/orders/{orderNo}/shipping', [ApiController::class, 'updateShipping'])->name('api.orders.shipping');

        // Aktif token listesi (yönetim)
        Route::get('/tokens', [ApiController::class, 'tokens'])->name('api.tokens');

        // CSPR ödeme: talimat üretimi (QR) + durum sorgusu (e-ticaret polling)
        Route::post('/orders/{orderNo}/cspr-payment', [ApiController::class, 'csprPayment'])->name('api.cspr.payment');
        Route::get('/cspr-payments/{memo}', [ApiController::class, 'csprStatus'])->name('api.cspr.status');
    });
