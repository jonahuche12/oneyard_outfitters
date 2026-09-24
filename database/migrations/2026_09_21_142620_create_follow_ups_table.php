<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();

            $table->foreignId('contact_id')
                ->nullable()
                ->constrained('contacts')
                ->nullOnDelete();

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('follow_up_date');

            $table->string('type', 30);

            $table->string('subject', 200);

            $table->text('outcome');

            $table->text('next_action')->nullable();

            $table->dateTime('next_follow_up_date')->nullable();

            $table->string('status', 30)->default('open');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index('organization_id');
            $table->index('contact_id');
            $table->index('recorded_by');
            $table->index('follow_up_date');
            $table->index('next_follow_up_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};