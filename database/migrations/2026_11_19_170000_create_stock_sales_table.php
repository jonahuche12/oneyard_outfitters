<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->string('sale_number', 40);
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('customer_name', 200)->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->string('customer_email')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->dateTime('sold_at');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['store_id', 'sale_number']);
            $table->index(['store_id', 'sold_at']);
            $table->index(['store_id', 'customer_id']);
        });

        Schema::create('stock_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_sale_id')->constrained('stock_sales')->restrictOnDelete();
            $table->foreignId('stock_item_id')->constrained('stock_items')->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_selling_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->decimal('unit_cost_snapshot', 15, 6)->nullable();
            $table->decimal('line_cost_snapshot', 15, 2)->nullable();
            $table->timestamps();

            $table->unique(['stock_sale_id', 'stock_item_id']);
            $table->index('stock_item_id');
        });

        Schema::create('stock_sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_sale_id')->constrained('stock_sales')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 30);
            $table->string('reference', 120)->nullable();
            $table->dateTime('paid_at');
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['stock_sale_id', 'paid_at']);
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_sale_payments');
        Schema::dropIfExists('stock_sale_items');
        Schema::dropIfExists('stock_sales');
    }
};
