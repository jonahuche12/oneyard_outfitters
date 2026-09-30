<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('quotation_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('quotation_recipient_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('payment_transaction_id')
                ->nullable()
                ->constrained('payment_transactions')
                ->nullOnDelete();

            $table->decimal('amount', 15, 2);

            $table->string('payment_method')->default('paystack');
            $table->string('reference')->unique();

            $table->string('status')->default('completed');

            $table->timestamp('paid_at');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['quotation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
