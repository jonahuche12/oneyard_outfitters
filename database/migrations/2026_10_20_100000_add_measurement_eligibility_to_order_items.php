<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('measurement_eligibility')
                ->default('pending');
            $table->text('measurement_eligibility_reason')->nullable();
            $table->foreignId('measurement_eligibility_decided_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('measurement_eligibility_decided_at')->nullable();
        });

        Schema::create('order_item_measurement_eligibility_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')
                ->constrained('order_items')
                ->cascadeOnDelete();
            $table->string('old_eligibility')->nullable();
            $table->string('new_eligibility');
            $table->foreignId('changed_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('reason');
            $table->timestamps();

            $table->index(['order_item_id', 'created_at'], 'oimea_item_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_measurement_eligibility_audits');

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['measurement_eligibility_decided_by']);
            $table->dropColumn([
                'measurement_eligibility',
                'measurement_eligibility_reason',
                'measurement_eligibility_decided_by',
                'measurement_eligibility_decided_at',
            ]);
        });
    }
};
