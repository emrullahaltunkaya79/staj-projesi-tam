<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\WebhookController;
use App\Livewire\CartPage;
use App\Livewire\Checkout;
use App\Livewire\MyOrders;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShopController::class, 'home'])->name('shop.home');
Route::get('/urun/{id}', [ShopController::class, 'product'])->name('shop.product');

Route::get('/sepet', CartPage::class)->name('shop.cart');
Route::get('/siparislerim', MyOrders::class)->name('shop.myorders');
Route::get('/odeme', Checkout::class)->name('shop.checkout');

Route::get('/siparis/{orderNo}', [OrderController::class, 'show'])->name('shop.order');
Route::post('/siparis/{orderNo}/ode', [OrderController::class, 'pay'])->name('shop.order.pay');
Route::post('/siparis/{orderNo}/iptal', [OrderController::class, 'cancel'])->name('shop.order.cancel');

// CSPR ödeme ekranı + canlı durum yoklaması
Route::get('/siparis/{orderNo}/odeme', [PaymentController::class, 'show'])->name('shop.payment');
Route::get('/siparis/{orderNo}/odeme/durum', [PaymentController::class, 'status'])->name('shop.payment.status');

// Arka ofis webhook alıcısı
Route::post('/webhook/backoffice', WebhookController::class)->name('shop.webhook');
