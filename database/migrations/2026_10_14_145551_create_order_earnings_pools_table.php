<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_earnings_pools', function (Blueprint $table) {
            $table->id();

            // A delivered order may generate exactly one original pool.
            $table->foreignId('order_id')
                ->unique()
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('quotation_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('quotation_number');
            $table->decimal('source_amount', 15, 2);
            $table->decimal('rate_percent', 5, 2)->default(5.00);
            $table->decimal('pool_amount', 15, 2);

            // Snapshot the source values used to calculate the pool.
            $table->json('source_snapshot');
            $table->timestamp('delivered_at');
            $table->string('status')->default('pending');

            $table->timestamps();

            $table->index(['status', 'delivered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_earnings_pools');
    }
};
