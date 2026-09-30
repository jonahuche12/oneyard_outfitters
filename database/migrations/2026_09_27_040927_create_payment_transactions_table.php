<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_recipient_id')
                ->constrained('quotation_recipients')
                ->cascadeOnDelete();

            $table->string('reference')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('gateway')->default('paystack');
            $table->string('status')->default('initialized');

            $table->string('gateway_transaction_id')->nullable();
            $table->string('access_code')->nullable();
            $table->text('authorization_url')->nullable();

            $table->timestamp('initialized_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['quotation_recipient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
