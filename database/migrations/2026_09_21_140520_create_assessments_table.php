<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();

            $table->foreignId('assessed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('assessment_date');

            $table->string('status', 30)->default('draft');

            $table->text('needs');

            $table->string('current_supplier', 200)->nullable();

            $table->text('procurement_process')->nullable();

            $table->decimal('estimated_budget', 15, 2)->nullable();

            $table->text('estimated_demand')->nullable();

            $table->string('buying_timeline', 100)->nullable();

            $table->string('decision_maker', 200)->nullable();

            $table->text('observations')->nullable();

            $table->text('opportunities')->nullable();

            $table->text('risks')->nullable();

            $table->text('recommendation')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index('organization_id');
            $table->index('assessed_by');
            $table->index('assessment_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};