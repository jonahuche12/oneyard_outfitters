<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_notification_recipients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('contact_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('email');
            $table->string('access_token', 64)->unique();
            $table->timestamp('notified_at')->nullable();

            $table->timestamps();

            $table->unique(['order_id', 'contact_id']);
            $table->index(['order_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_notification_recipients');
    }
};
