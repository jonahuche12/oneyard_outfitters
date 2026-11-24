<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_fund_assignment_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_fund_assignment_id')
                ->constrained('store_fund_assignments')
                ->restrictOnDelete();

            $table->foreignId('actor_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('event_type', 40);

            // Event snapshots, including before/after dispatch corrections.
            $table->json('details')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['store_fund_assignment_id', 'created_at'],
                'store_fund_assignment_events_assignment_created_index'
            );

            $table->index(
                ['actor_id', 'created_at'],
                'store_fund_assignment_events_actor_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_fund_assignment_events');
    }
};
