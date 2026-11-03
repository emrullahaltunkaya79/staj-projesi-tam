<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rezerve stok: sepet/sipariş aşamasında tutulan adet.
        // Müsait stok = stock_quantity - reserved_quantity
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('reserved_quantity')->default(0)->after('stock_quantity');
        });

        // E-ticaret siparişleri: faturadan AYRI yaşam döngüsü
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_no')->unique();               // ORD-000001
            $table->string('external_ref')->nullable()->index(); // e-ticaret sipariş numarası
            $table->string('customer_email')->nullable();        // e-ticaret müşterisi
            $table->string('customer_name')->nullable();
            $table->string('shipping_address')->nullable();
            $table->string('tracking_no')->nullable();           // kargo takip no
            $table->string('carrier')->nullable();               // kargo firması
            $table->enum('status', [
                'pending',      // bekliyor (stok rezerve)
                'paid',         // ödendi (rezerv → gerçek stok düşümü + fatura)
                'preparing',    // hazırlanıyor
                'shipped',      // kargolandı
                'delivered',    // teslim edildi
                'cancelled',    // iptal (stok serbest)
            ])->default('pending');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(20);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->json('items');                                // sipariş kalemleri snapshot
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();          // rezervasyon zaman aşımı
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('reserved_quantity');
        });
    }
};
