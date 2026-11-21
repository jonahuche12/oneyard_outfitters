<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_consignment_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_consignment_id')
                ->constrained('stock_consignments')
                ->restrictOnDelete();
            $table->string('reference', 40)->unique();
            $table->foreignId('dispatched_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('dispatched_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->text('dispatch_notes')->nullable();
            $table->text('receipt_notes')->nullable();
            $table->timestamps();

            $table->index(['stock_consignment_id', 'dispatched_at'], 'sc_returns_consignment_dispatched_idx');
        });

        Schema::create('stock_consignment_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_consignment_return_id')
                ->constrained('stock_consignment_returns', indexName: 'scr_items_return_fk')
                ->restrictOnDelete();
            $table->foreignId('stock_consignment_item_id')
                ->constrained('stock_consignment_items', indexName: 'scr_items_consignment_item_fk')
                ->restrictOnDelete();
            $table->decimal('quantity_dispatched', 12, 3);
            $table->decimal('quantity_received', 12, 3)->nullable();
            $table->timestamps();

            $table->unique(
                ['stock_consignment_return_id', 'stock_consignment_item_id'],
                'consignment_return_item_unique'
            );
            $table->index('stock_consignment_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_consignment_return_items');
        Schema::dropIfExists('stock_consignment_returns');
    }
};
