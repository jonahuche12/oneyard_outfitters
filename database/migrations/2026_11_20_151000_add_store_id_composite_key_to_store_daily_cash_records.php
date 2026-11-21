<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_daily_cash_records', function (Blueprint $table) {
            $table->unique(
                ['id', 'store_id'],
                'store_daily_cash_id_store_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('store_daily_cash_records', function (Blueprint $table) {
            $table->dropUnique('store_daily_cash_id_store_unique');
        });
    }
};
