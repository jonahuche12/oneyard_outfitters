<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_earnings_contributions', function (Blueprint $table) {
            $table->unique(
                ['order_earnings_pool_id', 'user_id', 'activity_key'],
                'oec_pool_user_activity_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('order_earnings_contributions', function (Blueprint $table) {
            $table->dropUnique('oec_pool_user_activity_unique');
        });
    }
};
