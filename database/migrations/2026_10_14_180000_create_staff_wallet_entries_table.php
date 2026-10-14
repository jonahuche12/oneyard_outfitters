<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_wallet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 15, 2);
            $table->foreignId('order_earnings_pool_id')
                ->nullable()
                ->constrained('order_earnings_pools')
                ->restrictOnDelete();
            $table->foreignId('order_earnings_allocation_id')
                ->nullable()
                ->unique()
                ->constrained('order_earnings_allocations')
                ->restrictOnDelete();
            $table->foreignId('reverses_entry_id')
                ->nullable()
                ->unique()
                ->constrained('staff_wallet_entries')
                ->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_wallet_entries');
    }
};
