<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('store_daily_cash_record_id');

            $table->foreign(
                ['store_daily_cash_record_id', 'store_id'],
                'store_expenses_daily_store_fk'
            )->references(['id', 'store_id'])
                ->on('store_daily_cash_records')
                ->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('category', 50);
            $table->string('payment_method', 20);
            $table->text('description');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(
                ['store_daily_cash_record_id', 'category'],
                'store_expenses_daily_category_index'
            );
            $table->index(['store_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_expenses');
    }
};
