<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();

            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();

            $table->string('position', 150)->nullable();

            $table->string('phone', 50);
            $table->string('alternate_phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('whatsapp', 50)->nullable();

            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'organization_id',
                'is_active',
            ]);

            $table->index([
                'organization_id',
                'is_primary',
            ]);

            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};