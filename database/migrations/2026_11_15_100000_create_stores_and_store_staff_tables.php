<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('code', 20)->unique();
            $table->string('type', 20)->default('store');
            // 1 for the (single) general warehouse, NULL otherwise. Derived by the model.
            $table->unsignedTinyInteger('general_warehouse_slot')->nullable()->unique();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('store_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role', 20);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('ended_reason', 500)->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            // 1 while the assignment is active, NULL once ended. Derived by the model.
            $table->unsignedTinyInteger('active_user_slot')->nullable();
            // 1 only while this is an active coordinator assignment, NULL otherwise.
            $table->unsignedTinyInteger('coordinator_slot')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'active_user_slot'], 'store_staff_active_user_unique');
            $table->unique(['store_id', 'coordinator_slot'], 'store_staff_coordinator_unique');
            $table->index(['store_id', 'ends_at'], 'store_staff_store_ends_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_staff');
        Schema::dropIfExists('stores');
    }
};
