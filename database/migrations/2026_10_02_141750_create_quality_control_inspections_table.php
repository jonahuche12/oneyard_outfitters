<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_control_inspections', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('inspected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status')->default('in_progress');
            $table->string('result')->nullable();

            $table->text('findings')->nullable();
            $table->boolean('correction_required')->default(false);
            $table->text('correction_notes')->nullable();

            $table->timestamp('inspected_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['result', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_control_inspections');
    }
};
