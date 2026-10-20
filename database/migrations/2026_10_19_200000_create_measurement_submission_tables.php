<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_item_id')
                ->constrained('order_items')
                ->cascadeOnDelete();

            $table->unsignedInteger('revision')->default(1);

            // Normalized data structure, independent of how it was entered.
            $table->string('measurement_method', 30);
            $table->string('input_source', 20)->default('manual');

            $table->string('status', 30)->default('draft');

            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->text('correction_reason')->nullable();

            $table->timestamps();

            $table->unique(
                ['order_item_id', 'revision'],
                'measurement_submissions_item_revision_unique'
            );

            $table->index(
                ['order_item_id', 'status'],
                'measurement_submissions_item_status_index'
            );
        });

        Schema::create('individual_measurements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('measurement_submission_id')
                ->constrained('measurement_submissions')
                ->cascadeOnDelete();

            // Optional label or reference; detailed privacy rules come later.
            $table->string('person_label')->nullable();

            // Measurement fields vary by garment type.
            $table->json('measurements');

            $table->text('notes')->nullable();
            $table->unsignedInteger('position')->default(1);

            $table->timestamps();

            $table->unique(
                ['measurement_submission_id', 'position'],
                'individual_measurements_submission_position_unique'
            );
        });

        Schema::create('measurement_size_quantities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('measurement_submission_id')
                ->constrained('measurement_submissions')
                ->cascadeOnDelete();

            $table->string('size_label', 50);
            $table->unsignedInteger('quantity');

            $table->timestamps();

            $table->unique(
                ['measurement_submission_id', 'size_label'],
                'measurement_size_quantities_submission_size_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_size_quantities');
        Schema::dropIfExists('individual_measurements');
        Schema::dropIfExists('measurement_submissions');
    }
};
