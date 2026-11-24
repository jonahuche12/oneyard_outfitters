<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_fund_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->restrictOnDelete();

            // Nullable until daily cash integration associates the assignment
            // with the applicable daily cash record.
            $table->unsignedBigInteger('store_daily_cash_record_id')->nullable();

            $table->date('business_date');

            $table->string('source', 30);
            $table->string('purpose', 40);
            $table->string('status', 30);

            $table->decimal('requested_cash', 15, 2)->default(0);
            $table->decimal('requested_bank', 15, 2)->default(0);

            // Dispatch amounts remain null until dispatch is recorded.
            $table->decimal('sent_cash', 15, 2)->nullable();
            $table->decimal('sent_bank', 15, 2)->nullable();

            $table->foreignId('requested_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('handled_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->foreignId('dispatched_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('dispatched_at')->nullable();

            $table->foreignId('confirmed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('confirmed_at')->nullable();

            $table->text('rejection_reason')->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            // If a daily cash record is eventually linked, it must belong
            // to the same store as the assignment.
            $table->foreign(
                ['store_daily_cash_record_id', 'store_id'],
                'store_fund_assignments_daily_store_fk'
            )->references(['id', 'store_id'])
                ->on('store_daily_cash_records')
                ->restrictOnDelete();

            $table->index(
                ['store_id', 'business_date', 'status'],
                'store_fund_assignments_store_date_status_index'
            );

            $table->index(
                ['status', 'source'],
                'store_fund_assignments_status_source_index'
            );

            $table->index(
                ['store_daily_cash_record_id', 'status'],
                'store_fund_assignments_daily_status_index'
            );

            $table->index(
                ['requested_by', 'created_at'],
                'store_fund_assignments_requester_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_fund_assignments');
    }
};
