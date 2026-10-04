<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table): void {
            $table->string('offline_payment_status')
                ->nullable()
                ->after('payment_arrangement');

            $table->foreignId('offline_payment_claimed_by')
                ->nullable()
                ->after('offline_payment_status')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('offline_payment_claimed_at')
                ->nullable()
                ->after('offline_payment_claimed_by');

            $table->foreignId('offline_payment_reviewed_by')
                ->nullable()
                ->after('offline_payment_claimed_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('offline_payment_reviewed_at')
                ->nullable()
                ->after('offline_payment_reviewed_by');

            $table->text('offline_payment_review_notes')
                ->nullable()
                ->after('offline_payment_reviewed_at');

            $table->index('offline_payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table): void {
            $table->dropForeign(['offline_payment_claimed_by']);
            $table->dropForeign(['offline_payment_reviewed_by']);

            $table->dropIndex(['offline_payment_status']);

            $table->dropColumn([
                'offline_payment_status',
                'offline_payment_claimed_by',
                'offline_payment_claimed_at',
                'offline_payment_reviewed_by',
                'offline_payment_reviewed_at',
                'offline_payment_review_notes',
            ]);
        });
    }
};
