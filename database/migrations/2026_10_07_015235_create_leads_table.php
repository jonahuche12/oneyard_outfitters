<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('phone');
            $table->string('category');
            $table->text('message');

            $table->string('source')->default('website');
            $table->string('status')->default('new');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('organization_id')
                ->nullable()
                ->constrained('organizations')
                ->nullOnDelete();

            $table->timestamp('converted_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('source');
            $table->index('status');
            $table->index('created_by');
            $table->index('assigned_to');
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
