<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\SystemHealthController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CasperPaymentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanySettingController;
use App\Http\Controllers\CashTransferController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactPriceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceVerifyController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\RecycleBinController;
use App\Http\Controllers\RecurringInvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Misafir - kimlik doğrulama
Route::middleware('guest')->group(function () {
    Route::get('/kayit', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/kayit', [AuthController::class, 'register'])->name('register.store');
    Route::get('/giris', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/giris', [AuthController::class, 'login'])->middleware('throttle:auth')->name('login.store');

    // Şifremi unuttum
    Route::get('/sifremi-unuttum', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/sifremi-unuttum', [PasswordResetController::class, 'sendResetLinkEmail'])
        ->middleware('throttle:password-reset')->name('password.email');
    Route::get('/sifre-sifirla/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/sifre-sifirla', [PasswordResetController::class, 'updatePassword'])->name('password.update');
});

Route::post('/cikis', [AuthController::class, 'logout'])->name('logout');

// E-posta doğrulama (imzalı bağlantı, oturum gerektirmez)
Route::get('/e-posta/dogrula/{id}/{hash}', [ProfileController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('verification.verify');

// Fatura doğrulama (QR kod — herkese açık, oturum gerekmez)
Route::get('/fatura/dogrula/{code}', [InvoiceVerifyController::class, 'show'])->name('fatura.dogrula');

// Panel - oturum açmış kullanıcılar
Route::middleware('auth')->group(function () {
    Route::get('/panel', [DashboardController::class, 'index'])->name('dashboard');

    // Excel dışa aktarma (resource parametre rotalarıyla çakışmaması için önce tanımlanır)
    Route::get('/cariler/export', [ContactController::class, 'export'])->name('cariler.export');
    Route::get('/urunler/export', [ProductController::class, 'export'])->name('urunler.export');
    Route::get('/urunler/stok-sayim', [ProductController::class, 'exportStockCount'])->name('urunler.stok-sayim');
    Route::get('/faturalar/export', [InvoiceController::class, 'export'])->name('faturalar.export');

    // Excel'den içe aktarma (cari / ürün / stok sayımı)
    Route::get('/cariler/import', [ImportController::class, 'contactsForm'])->name('cariler.import.form');
    Route::post('/cariler/import', [ImportController::class, 'importContacts'])->name('cariler.import');
    Route::get('/cariler/import/sablon', [ImportController::class, 'contactsTemplate'])->name('cariler.import.template');
    Route::get('/urunler/import', [ImportController::class, 'productsForm'])->name('urunler.import.form');
    Route::post('/urunler/import', [ImportController::class, 'importProducts'])->name('urunler.import');
    Route::get('/urunler/import/sablon', [ImportController::class, 'productsTemplate'])->name('urunler.import.template');
    Route::get('/urunler/stok-sayim/import', [ImportController::class, 'stockCountForm'])->name('urunler.stok-sayim.import.form');
    Route::post('/urunler/stok-sayim/import', [ImportController::class, 'importStockCount'])->name('urunler.stok-sayim.import');

    Route::resource('cariler', ContactController::class)->parameters(['cariler' => 'contact']);
    Route::resource('urunler', ProductController::class)->parameters(['urunler' => 'product']);
    Route::post('/urunler/{product}/gorseller', [ProductController::class, 'uploadImage'])->name('urunler.gorsel.upload');
    Route::delete('/urunler/{product}/gorseller/{image}', [ProductController::class, 'destroyImage'])->name('urunler.gorsel.destroy');
    Route::resource('faturalar', InvoiceController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->parameters(['faturalar' => 'invoice']);
    Route::get('/faturalar/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])
        ->middleware('auth')
        ->name('faturalar.pdf');
    Route::get('/faturalar/{invoice}/iade', [InvoiceController::class, 'refundForm'])->name('faturalar.iade.form');
    Route::post('/faturalar/{invoice}/iade', [InvoiceController::class, 'refund'])->name('faturalar.iade');
    Route::post('/faturalar/{invoice}/e-posta', [InvoiceController::class, 'email'])
        ->name('faturalar.email');

    // Alış faturaları (tedarikçiden satın alma)
    Route::get('/alis-faturalari/create', [InvoiceController::class, 'createPurchase'])->name('alislar.create');
    Route::post('/alis-faturalari', [InvoiceController::class, 'storePurchase'])->name('alislar.store');

    // Stok hareket geçmişi ve raporlar
    Route::get('/stok-hareketleri', [StockMovementController::class, 'index'])->name('movements.index');
    Route::get('/raporlar', [ReportController::class, 'index'])->name('raporlar.index');
    Route::get('/raporlar/excel', [ReportController::class, 'export'])->name('raporlar.excel');

    // Global arama (cariler, ürünler, faturalar)
    Route::get('/ara', SearchController::class)->name('ara');

    // Ödeme takvimi (vadeler + tekrarlayan kesimler)
    Route::get('/takvim', [CalendarController::class, 'index'])->name('takvim.index');

    // E-ticaret siparişleri (panel)
    Route::get('/siparisler', [OrderController::class, 'index'])->name('siparisler.index');
    Route::get('/siparisler/{order}', [OrderController::class, 'show'])->name('siparisler.show');
    Route::get('/siparisler/{order}/etiket', [OrderController::class, 'label'])->name('siparisler.etiket');
    Route::post('/siparisler/{order}/odendi', [OrderController::class, 'markPaid'])->name('siparisler.odendi');
    Route::post('/siparisler/{order}/iptal', [OrderController::class, 'cancelOrder'])->name('siparisler.iptal');
    Route::post('/siparisler/{order}/kargo', [OrderController::class, 'updateShipping'])->name('siparisler.kargo');

    // CSPR kripto ödeme
    Route::post('/siparisler/{order}/cspr-odeme', [CasperPaymentController::class, 'start'])->name('siparisler.cspr.start');
    Route::get('/siparisler/{order}/cspr-odeme/{payment}', [CasperPaymentController::class, 'show'])->name('siparisler.cspr.show');
    Route::post('/siparisler/{order}/cspr-odeme/{payment}/kontrol', [CasperPaymentController::class, 'check'])->name('siparisler.cspr.check');
    Route::post('/siparisler/{order}/cspr-odeme/{payment}/onay', [CasperPaymentController::class, 'forceConfirm'])->name('siparisler.cspr.confirm');

    // Özel fiyat listesi (cari bazlı)
    Route::get('/ara/fiyatlar', [SearchController::class, 'prices'])->name('ara.fiyatlar');
    Route::get('/fiyatlar', [ContactPriceController::class, 'index'])->name('fiyatlar.index');
    Route::post('/fiyatlar', [ContactPriceController::class, 'store'])->name('fiyatlar.store');
    Route::delete('/fiyatlar/{price}', [ContactPriceController::class, 'destroy'])->name('fiyatlar.destroy');

    // Depolar
    Route::get('/depolar', [WarehouseController::class, 'index'])->name('depolar.index');
    Route::post('/depolar', [WarehouseController::class, 'store'])->name('depolar.store');
    Route::delete('/depolar/{warehouse}', [WarehouseController::class, 'destroy'])->name('depolar.destroy');
    Route::get('/depolar/{warehouse}/stok', [WarehouseController::class, 'stock'])->name('depolar.stock');
    Route::post('/depolar/{warehouse}/transfer', [WarehouseController::class, 'transfer'])->name('depolar.transfer');
    Route::get('/depolar/matris', [WarehouseController::class, 'matrix'])->name('depolar.matrix');
    Route::get('/depolar/{warehouse}/excel', [WarehouseController::class, 'export'])->name('depolar.excel');
    Route::get('/depolar/dagit', [WarehouseController::class, 'distribute'])->name('depolar.distribute');
    Route::post('/depolar/dagit', [WarehouseController::class, 'distribute'])->name('depolar.distribute.store');

    // Ürün kategorileri
    Route::post('/kategoriler', [CategoryController::class, 'store'])->name('kategoriler.store');
    Route::delete('/kategoriler/{category}', [CategoryController::class, 'destroy'])->name('kategoriler.destroy');

    // Kasa ↔ Banka virmanları
    Route::get('/virmanlar', [CashTransferController::class, 'index'])->name('virmanlar.index');
    Route::post('/virmanlar', [CashTransferController::class, 'store'])->name('virmanlar.store');

    // Şirket ayarları (yönetici)
    Route::get('/ayarlar', [CompanySettingController::class, 'edit'])->name('ayarlar.edit');
    Route::put('/ayarlar', [CompanySettingController::class, 'update'])->name('ayarlar.update');
    Route::post('/ayarlar/yedek', [CompanySettingController::class, 'backup'])->name('ayarlar.backup');
    Route::post('/ayarlar/entegrasyon', [CompanySettingController::class, 'updateIntegration'])->name('ayarlar.integration');

    // Profil: e-posta doğrulama + şifre değiştirme
    Route::post('/e-posta/yeniden-gonder', [ProfileController::class, 'resendVerification'])
        ->middleware('throttle:password-reset')->name('verification.resend');
    Route::get('/sifre-degistir', [ProfileController::class, 'passwordForm'])->name('sifre-degistir');
    Route::put('/sifre-degistir', [ProfileController::class, 'updatePassword'])->name('sifre-degistir.update');

    // API token yönetimi (e-ticaret entegrasyonu)
    Route::get('/api-tokenlar', [\App\Http\Controllers\TokenWebController::class, 'index'])->name('api-tokenlar.index');
    Route::post('/api-tokenlar', [\App\Http\Controllers\TokenWebController::class, 'store'])->name('api-tokenlar.store');
    Route::delete('/api-tokenlar/{tokenId}', [\App\Http\Controllers\TokenWebController::class, 'destroy'])->name('api-tokenlar.destroy');
    Route::get('/api-tokenlar/postman', [\App\Http\Controllers\TokenWebController::class, 'postman'])->name('api-tokenlar.postman');

    // Denetim izi ve geri dönüşüm kutusu (yönetici, controller içinde policy)
    Route::get('/etkinlikler', [ActivityLogController::class, 'index'])->name('etkinlik.index');
    Route::get('/geri-donusum', [RecycleBinController::class, 'index'])->name('geri-donusum.index');
    Route::post('/geri-donusum/{type}/{id}/geri-yukle', [RecycleBinController::class, 'restore'])->name('geri-donusum.restore');

    // Sistem sağlığı (yönetici)
    Route::get('/sistem', [SystemHealthController::class, 'index'])->name('sistem.index');

    // Webhook teslimat geçmişi (yönetici)
    Route::get('/webhooklar', [\App\Http\Controllers\WebhookDeliveryController::class, 'index'])->name('webhooklar.index');
    Route::post('/webhooklar/{delivery}/tekrar-gonder', [\App\Http\Controllers\WebhookDeliveryController::class, 'retry'])->name('webhooklar.retry');

    // Giderler (kasa çıkışları)
    Route::resource('giderler', ExpenseController::class)
        ->only(['index', 'create', 'store', 'destroy'])
        ->parameters(['giderler' => 'expense']);

    // Teklif modülü
    Route::resource('teklifler', QuoteController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
        ->parameters(['teklifler' => 'quote']);
    Route::post('/teklifler/{quote}/durum', [QuoteController::class, 'changeStatus'])->name('teklifler.durum');
    Route::post('/teklifler/{quote}/faturalandir', [QuoteController::class, 'convert'])->name('teklifler.faturalandir');
    Route::get('/teklifler/{quote}/pdf', [QuoteController::class, 'downloadPdf'])->name('teklifler.pdf');
    Route::post('/teklifler/{quote}/e-posta', [QuoteController::class, 'email'])->name('teklifler.email');

    // Tekrarlayan faturalar (aylık kira/abonelik)
    Route::resource('tekrarlayan', RecurringInvoiceController::class)
        ->only(['index', 'create', 'store', 'destroy'])
        ->parameters(['tekrarlayan' => 'rule']);

    // Kullanıcı yönetimi — erişim UserPolicy ile kontrol edilir (controller içinde)
    Route::resource('kullanicilar', UserController::class)
        ->only(['index', 'create', 'store', 'destroy'])
        ->parameters(['kullanicilar' => 'user']);
    Route::post('/kullanicilar/{user}/rol', [UserController::class, 'changeRole'])->name('kullanicilar.rol');

    // Tahsilat (ödeme alma) modülü
    Route::get('/tahsilatlar', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/tahsilatlar/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/tahsilatlar', [PaymentController::class, 'store'])->name('payments.store');

    // Tedarikçiye ödeme
    Route::get('/tedarikci-odemeleri/create', [PaymentController::class, 'createSupplier'])->name('payments.create_supplier');
    Route::post('/tedarikci-odemeleri', [PaymentController::class, 'storeSupplier'])->name('payments.store_supplier');
});

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));
