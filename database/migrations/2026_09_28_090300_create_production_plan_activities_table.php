<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_plan_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('production_plan_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('production_activity_id')
                ->constrained()
                ->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['production_plan_id', 'production_activity_id'],
                'plan_activity_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_plan_activities');
    }
};
