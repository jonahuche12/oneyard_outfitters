<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_earnings_pools', function (Blueprint $table) {
            $table->foreignId('finalized_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->json('finalization_snapshot')->nullable();
            $table->json('hold_reasons')->nullable();
            $table->timestamp('held_at')->nullable();
        });

        Schema::create('order_earnings_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_earnings_pool_id')
                ->constrained('order_earnings_pools')
                ->restrictOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('weight');
            $table->unsignedBigInteger('weight_scale');
            $table->decimal('effective_points', 10, 4);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['order_earnings_pool_id', 'user_id'],
                'oea_pool_user_unique'
            );
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_earnings_allocations');

        Schema::table('order_earnings_pools', function (Blueprint $table) {
            $table->dropConstrainedForeignId('finalized_by');
            $table->dropColumn([
                'finalized_at',
                'finalization_snapshot',
                'hold_reasons',
                'held_at',
            ]);
        });
    }
};
