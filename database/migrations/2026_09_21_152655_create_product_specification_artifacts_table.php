<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_specification_artifacts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_specification_id')
                ->constrained('product_specifications')
                ->cascadeOnDelete();

            $table->string('artifact_type', 50);

            $table->string('title', 200);

            $table->text('description')->nullable();

            $table->string('file_path', 500);

            $table->string('original_filename', 255);

            $table->string('mime_type', 150);

            $table->unsignedBigInteger('file_size');

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('is_current')->default(true);

            $table->timestamps();

            $table->softDeletes();

            $table->index('product_specification_id');
            $table->index('artifact_type');
            $table->index('uploaded_by');
            $table->index('is_current');

            $table->index(
                [
                    'product_specification_id',
                    'artifact_type',
                    'is_current',
                ],
                'psa_spec_type_current_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_specification_artifacts');
    }
};