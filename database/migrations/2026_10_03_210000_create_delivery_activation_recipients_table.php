<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_activation_recipients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('delivery_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('contact_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('email');

            $table->string('access_token', 64)
                ->unique();

            $table->string('status')
                ->default('pending');

            $table->timestamp('notified_at')
                ->nullable();

            $table->timestamp('viewed_at')
                ->nullable();

            $table->timestamp('activated_at')
                ->nullable();

            $table->timestamps();

            $table->unique(['delivery_id', 'contact_id']);
            $table->index(['delivery_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_activation_recipients');
    }
};
