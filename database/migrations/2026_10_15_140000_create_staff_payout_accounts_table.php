<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('bank_name');
            $table->string('bank_code', 20);
            $table->string('account_name');
            $table->text('account_number');
            $table->string('last_four', 4);
            $table->boolean('is_active')->default(true);
            $table->timestamp('usable_from');
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_payout_accounts');
    }
};
