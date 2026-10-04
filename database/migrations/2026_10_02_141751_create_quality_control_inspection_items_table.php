<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_control_inspection_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('quality_control_inspection_id')
                ->constrained('quality_control_inspections', 'id', 'qc_inspection_item_inspection_fk')
                ->cascadeOnDelete();

            $table->foreignId('order_item_id')
                ->constrained('order_items', 'id', 'qc_inspection_item_order_fk')
                ->restrictOnDelete();

            // Snapshot the order item at the time of inspection.
            $table->string('item_name');
            $table->string('unit')->default('piece');
            $table->decimal('quantity', 15, 2);
            $table->decimal('failed_quantity', 15, 2)->nullable();

            $table->text('findings')->nullable();

            $table->timestamps();

            $table->unique(
                ['quality_control_inspection_id', 'order_item_id'],
                'qc_inspection_item_unique'
            );

            $table->index(
                'order_item_id',
                'qc_inspection_item_order_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_control_inspection_items');
    }
};
