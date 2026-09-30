<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_offers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('procurement_id')
                ->constrained('procurements')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);

            $table->text('notes')->nullable();

            $table->string('status', 30)
                ->default('submitted');

            $table->dateTime('submitted_at')->nullable();

            $table->timestamps();

            $table->index(
                ['procurement_id', 'status'],
                'proc_offers_proc_status_index'
            );

            $table->index(
                ['user_id', 'status'],
                'proc_offers_user_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_offers');
    }
};
