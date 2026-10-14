<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('earnings_activity_weights')) {
            Schema::create('earnings_activity_weights', function (Blueprint $table) {
                $table->id();
                $table->string('activity_key')->unique();
                $table->string('label');
                $table->decimal('points', 10, 4)->default(0);
                $table->boolean('is_enabled')->default(true);
                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->restrictOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('earnings_weight_audits')) {
            Schema::create('earnings_weight_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('activity_weight_id')
                    ->nullable()
                    ->constrained('earnings_activity_weights')
                    ->nullOnDelete();
                $table->string('activity_key');
                $table->decimal('old_points', 10, 4)->nullable();
                $table->decimal('new_points', 10, 4);
                $table->boolean('old_enabled')->nullable();
                $table->boolean('new_enabled');
                $table->foreignId('changed_by')
                    ->constrained('users')
                    ->restrictOnDelete();
                $table->text('reason');
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['activity_key', 'created_at']);
            });
        }

        if (!Schema::hasTable('order_earnings_contributions')) {
            Schema::create('order_earnings_contributions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_earnings_pool_id')
                    ->constrained('order_earnings_pools')
                    ->restrictOnDelete();
                $table->foreignId('user_id')
                    ->constrained()
                    ->restrictOnDelete();
                $table->string('activity_key');
                $table->decimal('points', 10, 4);
                $table->string('source_type');
                $table->string('source_reference')->nullable();
                $table->timestamp('activity_at');
                $table->boolean('user_active_at_activity');
                $table->foreignId('assigned_by')
                    ->nullable()
                    ->constrained('users')
                    ->restrictOnDelete();
                $table->text('reason')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['order_earnings_pool_id', 'activity_key']);
                $table->index(['user_id', 'activity_at']);
                $table->index(['source_type', 'source_reference']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_earnings_contributions');
        Schema::dropIfExists('earnings_weight_audits');
        Schema::dropIfExists('earnings_activity_weights');
    }
};
