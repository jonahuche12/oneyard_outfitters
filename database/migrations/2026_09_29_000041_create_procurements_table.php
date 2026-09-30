<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('item_name');
            $table->text('description')->nullable();

            $table->decimal('quantity', 12, 2);
            $table->string('unit', 50);

            $table->decimal('maximum_unit_price', 15, 2);

            $table->date('required_by')->nullable();
            $table->dateTime('offer_deadline')->nullable();

            $table->string('priority', 30)
                ->default('normal');

            $table->string('status', 30)
                ->default('draft');

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['order_id', 'status'],
                'procurements_order_status_index'
            );

            $table->index(
                ['status', 'offer_deadline'],
                'procurements_status_deadline_index'
            );

            $table->index(
                ['created_by', 'status'],
                'procurements_creator_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurements');
    }
};
