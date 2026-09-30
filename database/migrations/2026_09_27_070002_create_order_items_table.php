<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('quotation_item_id')
                ->nullable()
                ->constrained('quotation_items')
                ->nullOnDelete();

            $table->foreignId('product_specification_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('item_name');
            $table->text('description')->nullable();

            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('unit')->default('piece');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['order_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
