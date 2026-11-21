<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_remittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->unsignedBigInteger('store_daily_cash_record_id');
            $table->date('business_date');
            $table->decimal('amount_cash', 15, 2)->default(0);
            $table->decimal('amount_bank', 15, 2)->default(0);
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->text('note')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('resubmits_id')->nullable()
                ->constrained('store_remittances')->restrictOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at');
            $table->foreignId('reviewed_by')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_reason')->nullable();
            $table->timestamps();

            $table->foreign(
                ['store_daily_cash_record_id', 'store_id'],
                'store_remittances_daily_store_fk'
            )->references(['id', 'store_id'])
                ->on('store_daily_cash_records')
                ->restrictOnDelete();

            $table->index(['store_id', 'status'], 'store_remittances_store_status_index');
            $table->index(['store_daily_cash_record_id', 'status'], 'store_remittances_daily_status_index');
            $table->index(['store_id', 'business_date'], 'store_remittances_store_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_remittances');
    }
};
