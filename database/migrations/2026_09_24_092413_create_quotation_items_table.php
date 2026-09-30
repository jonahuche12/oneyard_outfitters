<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quotation_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_specification_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('item_name');
            $table->text('description')->nullable();

            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('unit')->default('piece');

            /*
             * Snapshot of the price offered at quotation time.
             * This must remain unchanged if the Product Specification
             * price changes later.
             */
            $table->decimal('unit_price', 15, 2);

            $table->decimal('line_total', 15, 2);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['quotation_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
