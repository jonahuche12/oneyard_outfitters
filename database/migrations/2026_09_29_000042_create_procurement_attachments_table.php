<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('procurement_id')
                ->constrained('procurements')
                ->cascadeOnDelete();

            $table->foreignId('product_specification_artifact_id')
                ->nullable();

            $table->foreign(
                'product_specification_artifact_id',
                'proc_attach_artifact_fk'
            )
                ->references('id')
                ->on('product_specification_artifacts')
                ->restrictOnDelete();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(
                ['procurement_id', 'product_specification_artifact_id'],
                'proc_attach_proc_artifact_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_attachments');
    }
};
