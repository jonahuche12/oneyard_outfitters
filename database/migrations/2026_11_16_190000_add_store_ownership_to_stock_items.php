<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table): void {
            $table->foreignId('store_id')
                ->after('id')
                ->constrained('stores')
                ->restrictOnDelete();

            $table->index(
                ['store_id', 'approval_status'],
                'stock_items_store_approval_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table): void {
            $table->dropIndex('stock_items_store_approval_index');
            $table->dropConstrainedForeignId('store_id');
        });
    }
};
