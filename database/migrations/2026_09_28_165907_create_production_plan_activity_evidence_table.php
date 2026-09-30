<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_plan_activity_evidence', function (Blueprint $table) {
            $table->id();

            $table->foreignId('production_plan_activity_id')
                ->constrained(
                    'production_plan_activities',
                    'id',
                    'evidence_plan_activity_fk'
                )
                ->restrictOnDelete();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained(
                    'users',
                    'id',
                    'evidence_uploaded_by_fk'
                )
                ->nullOnDelete();

            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('file_size');
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_plan_activity_evidence');
    }
};
