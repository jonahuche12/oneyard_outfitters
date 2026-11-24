<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_daily_cash_records', function (Blueprint $table) {
            $table->decimal('entered_opening_cash', 15, 2)->nullable()
                ->after('opening_bank');

            $table->decimal('entered_opening_bank', 15, 2)->nullable()
                ->after('entered_opening_cash');

            $table->decimal('assigned_opening_cash', 15, 2)->nullable()
                ->after('entered_opening_bank');

            $table->decimal('assigned_opening_bank', 15, 2)->nullable()
                ->after('assigned_opening_cash');
        });
    }

    public function down(): void
    {
        Schema::table('store_daily_cash_records', function (Blueprint $table) {
            $table->dropColumn([
                'entered_opening_cash',
                'entered_opening_bank',
                'assigned_opening_cash',
                'assigned_opening_bank',
            ]);
        });
    }
};
