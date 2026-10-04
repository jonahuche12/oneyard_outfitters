<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->foreignId('delivery_id')
                ->nullable()
                ->after('quotation_recipient_id')
                ->constrained('deliveries')
                ->nullOnDelete();

            $table->index('delivery_id');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->dropForeign(['delivery_id']);
            $table->dropIndex(['delivery_id']);
            $table->dropColumn('delivery_id');
        });
    }
};
