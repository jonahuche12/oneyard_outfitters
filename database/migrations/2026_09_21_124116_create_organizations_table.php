<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();

            $table->string('organization_code', 30)->unique();

            $table->string('name', 200);

            $table->string('type', 50);

            $table->string('ownership', 50);

            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();

            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('area', 100)->nullable();
            $table->string('lga', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->default('Nigeria');

            $table->string('website', 255)->nullable();

            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};