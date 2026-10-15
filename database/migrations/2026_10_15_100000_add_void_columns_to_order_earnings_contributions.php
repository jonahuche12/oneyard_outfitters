<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_earnings_contributions', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
            // 1 while active, NULL once voided, so the unique key only binds active rows.
            $table->unsignedTinyInteger('is_active_slot')->nullable()->default(1);
        });

        Schema::table('order_earnings_contributions', function (Blueprint $table) {
            $table->unique(
                ['order_earnings_pool_id', 'user_id', 'activity_key', 'is_active_slot'],
                'oec_active_pool_user_activity_unique'
            );
        });

        Schema::table('order_earnings_contributions', function (Blueprint $table) {
            $table->dropUnique('oec_pool_user_activity_unique');
        });
    }

    public function down(): void
    {
        Schema::table('order_earnings_contributions', function (Blueprint $table) {
            $table->unique(
                ['order_earnings_pool_id', 'user_id', 'activity_key'],
                'oec_pool_user_activity_unique'
            );
        });

        Schema::table('order_earnings_contributions', function (Blueprint $table) {
            $table->dropUnique('oec_active_pool_user_activity_unique');
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['voided_at', 'void_reason', 'is_active_slot']);
        });
    }
};
