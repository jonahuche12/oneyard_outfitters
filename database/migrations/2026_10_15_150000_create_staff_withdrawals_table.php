<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('requested');

            // Snapshot of the account used, so later account changes never alter a request.
            $table->foreignId('payout_account_id')->constrained('staff_payout_accounts')->restrictOnDelete();
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_last_four', 4);

            $table->timestamp('requested_at');

            // Filled by 4C (review and payment).
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_withdrawals');
    }
};
