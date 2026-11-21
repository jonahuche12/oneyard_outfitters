<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_daily_cash_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->date('business_date');
            $table->decimal('opening_cash', 15, 2);
            $table->decimal('opening_bank', 15, 2);
            $table->decimal('closing_cash', 15, 2)->nullable();
            $table->decimal('closing_bank', 15, 2)->nullable();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['store_id', 'business_date'],
                'store_daily_cash_store_date_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_daily_cash_records');
    }
};
