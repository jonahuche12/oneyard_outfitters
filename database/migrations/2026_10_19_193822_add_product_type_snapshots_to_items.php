<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_specifications', function (Blueprint $table) {
            $table->string('product_type', 50)->nullable()->change();
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->string('product_type', 50)->nullable()
                ->after('product_specification_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_type', 50)->nullable()
                ->after('product_specification_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('product_type');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn('product_type');
        });

        Schema::table('product_specifications', function (Blueprint $table) {
            $table->string('product_type', 50)->nullable(false)->change();
        });
    }
};
