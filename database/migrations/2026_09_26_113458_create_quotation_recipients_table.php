<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_recipients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quotation_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('contact_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('email');
            $table->string('access_token', 64)->unique();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('responded_at')->nullable();

            $table->string('response_status')->default('pending');

            $table->timestamps();

            $table->unique(['quotation_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_recipients');
    }
};
