<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->unsignedSmallInteger('expected_delivery_days')
                ->nullable()
                ->after('valid_until');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('expected_delivery_days')
                ->nullable()
                ->after('order_date');

            $table->date('expected_delivery_date')
                ->nullable()
                ->after('expected_delivery_days');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'expected_delivery_days',
                'expected_delivery_date',
            ]);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('expected_delivery_days');
        });
    }
};
