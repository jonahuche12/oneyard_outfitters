<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Supplier receipts
        |--------------------------------------------------------------------------
        */

        Schema::create('intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->string('type', 20);
            $table->string('supplier_name', 150)->nullable();
            $table->string('reference', 100)->nullable();
            $table->date('received_on');
            $table->unsignedInteger('bale_count')->nullable();
            $table->decimal('weight', 12, 3)->nullable();
            $table->string('weight_unit', 2)->nullable();
            $table->decimal('weight_kg', 12, 3)->nullable();
            $table->decimal('rate', 15, 4)->nullable();
            $table->decimal('total_cost', 15, 2);
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['store_id', 'received_on'], 'intakes_store_received_index');
        });

        /*
        |--------------------------------------------------------------------------
        | Shared inventory definitions
        |--------------------------------------------------------------------------
        */

        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('intake_id')
                ->nullable()
                ->constrained('intakes')
                ->restrictOnDelete();

            $table->string('description', 200);
            $table->string('category', 80)->nullable();
            $table->string('grade', 50)->nullable();
            $table->string('unit', 10);
            $table->boolean('is_raw')->default(false);

            // Reference value only. Store-level moving average is authoritative.
            $table->decimal('unit_cost', 15, 6)->default(0);

            $table->decimal('suggested_price', 15, 2)->nullable();
            $table->decimal('current_market_price', 15, 2)->nullable();

            $table->string('approval_status', 20)->default('pending');
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->string('processing_status', 20)->nullable();
            $table->timestamp('processing_completed_at')->nullable();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('unit', 'stock_items_unit_index');
            $table->index('approval_status', 'stock_items_approval_index');
            $table->index('processing_status', 'stock_items_processing_status_index');
        });

        /*
        |--------------------------------------------------------------------------
        | Multiple item lines per supplier intake
        |--------------------------------------------------------------------------
        */

        Schema::create('intake_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intake_id')->constrained('intakes')->restrictOnDelete();
            $table->foreignId('stock_item_id')->constrained('stock_items')->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 15, 6);
            $table->decimal('line_cost', 15, 2);
            $table->timestamps();

            $table->unique(
                ['intake_id', 'stock_item_id'],
                'intake_items_intake_item_unique'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Inventory approval history
        |--------------------------------------------------------------------------
        */

        Schema::create('stock_item_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained('stock_items')->restrictOnDelete();
            $table->string('decision', 20);
            $table->text('reason')->nullable();
            $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at');
            $table->timestamps();

            $table->index(
                ['stock_item_id', 'reviewed_at'],
                'stock_item_approvals_item_date_index'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Current market-price history
        |--------------------------------------------------------------------------
        */

        Schema::create('stock_item_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained('stock_items')->restrictOnDelete();
            $table->decimal('market_price', 15, 2);
            $table->timestamp('effective_at');
            $table->string('reason', 500);
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(
                ['stock_item_id', 'effective_at'],
                'stock_item_price_history_item_date_index'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Per-store inventory balances
        |--------------------------------------------------------------------------
        */

        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained('stock_items')->restrictOnDelete();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();

            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('quantity_quarantined', 12, 3)->default(0);

            $table->decimal('average_unit_cost', 15, 6)->default(0);
            $table->decimal('inventory_value', 15, 2)->default(0);
            $table->decimal('quarantine_value', 15, 2)->default(0);

            $table->decimal('store_price', 15, 2)->nullable();

            $table->timestamps();

            $table->unique(
                ['stock_item_id', 'store_id'],
                'stock_levels_item_store_unique'
            );

            $table->index('store_id', 'stock_levels_store_index');
        });

        /*
        |--------------------------------------------------------------------------
        | Append-only stock ledger
        |--------------------------------------------------------------------------
        */

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained('stock_items')->restrictOnDelete();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();

            $table->string('type', 30);
            $table->decimal('quantity', 12, 3);

            $table->decimal('unit_cost', 15, 6)->nullable();
            $table->decimal('movement_value', 15, 2)->nullable();

            $table->nullableMorphs('source');

            $table->foreignId('reversal_of_id')->nullable()
                ->constrained('stock_movements')
                ->restrictOnDelete();

            $table->string('reason', 500)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(
                ['stock_item_id', 'store_id'],
                'stock_movements_item_store_index'
            );

            $table->index(
                ['store_id', 'created_at'],
                'stock_movements_store_created_index'
            );

            $table->index('reversal_of_id', 'stock_movements_reversal_index');
        });

        /*
        |--------------------------------------------------------------------------
        | Bale processing sessions
        |--------------------------------------------------------------------------
        */

        Schema::create('stock_processings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('raw_stock_item_id')->constrained('stock_items')->restrictOnDelete();

            $table->decimal('quantity_consumed', 12, 3);
            $table->decimal('consumed_cost', 15, 2);
            $table->decimal('allocated_cost', 15, 2);
            $table->decimal('cost_difference', 15, 2);

            $table->decimal('recoverable_waste_kg', 12, 3)->default(0);
            $table->decimal('discarded_waste_kg', 12, 3)->default(0);

            $table->date('processed_on')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('processed_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('store_id', 'stock_processings_store_index');

            $table->index(
                ['raw_stock_item_id', 'processed_on'],
                'stock_processings_raw_date_index'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Processing output lines
        |--------------------------------------------------------------------------
        */

        Schema::create('stock_processing_outputs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_processing_id')
                ->constrained('stock_processings')
                ->restrictOnDelete();

            $table->foreignId('stock_item_id')
                ->constrained('stock_items')
                ->restrictOnDelete();

            $table->decimal('quantity_produced', 12, 3);
            $table->decimal('line_cost', 15, 2);
            $table->timestamps();

            $table->unique(
                ['stock_processing_id', 'stock_item_id'],
                'stock_processing_outputs_session_item_unique'
            );

            $table->index('stock_item_id', 'stock_processing_outputs_item_index');
        });

        /*
        |--------------------------------------------------------------------------
        | Bale processing status audit
        |--------------------------------------------------------------------------
        */

        Schema::create('stock_processing_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained('stock_items')->restrictOnDelete();
            $table->string('event_type', 20);
            $table->string('previous_status', 20)->nullable();
            $table->string('new_status', 20);
            $table->string('reason', 500)->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(
                ['stock_item_id', 'recorded_at'],
                'stock_processing_status_item_date_index'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Consignment headers
        |--------------------------------------------------------------------------
        */

        Schema::create('stock_consignments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();

            $table->foreignId('source_store_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('destination_store_id')->constrained('stores')->restrictOnDelete();

            $table->string('status', 30)->default('draft');

            $table->timestamp('dispatched_at')->nullable();
            $table->foreignId('dispatched_by')->nullable()
                ->constrained('users')->restrictOnDelete();

            $table->decimal('total_cost', 15, 2)->nullable();
            $table->decimal('expected_sales_value', 15, 2)->nullable();
            $table->decimal('expected_gross_return', 15, 2)->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            $table->index(
                ['source_store_id', 'status'],
                'stock_consignments_source_status_index'
            );

            $table->index(
                ['destination_store_id', 'status'],
                'stock_consignments_destination_status_index'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Consignment inventory lines
        |--------------------------------------------------------------------------
        */

        Schema::create('stock_consignment_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_consignment_id')
                ->constrained('stock_consignments')
                ->restrictOnDelete();

            $table->foreignId('stock_item_id')->constrained('stock_items')->restrictOnDelete();

            $table->decimal('quantity_dispatched', 12, 3);

            $table->decimal('unit_cost', 15, 6)->nullable();
            $table->decimal('line_cost', 15, 2)->nullable();
            $table->decimal('market_price', 15, 2)->nullable();
            $table->decimal('expected_sales_value', 15, 2)->nullable();
            $table->decimal('expected_gross_return', 15, 2)->nullable();

            $table->timestamps();

            $table->unique(
                ['stock_consignment_id', 'stock_item_id'],
                'stock_consignment_items_consignment_item_unique'
            );

            $table->index('stock_item_id', 'stock_consignment_items_item_index');
        });

        /*
        |--------------------------------------------------------------------------
        | Repeated destination acknowledgement events
        |--------------------------------------------------------------------------
        */

        Schema::create('stock_consignment_acknowledgements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_consignment_id')
                ->constrained(
                    'stock_consignments',
                    indexName: 'sc_ack_header_consignment_fk'
                )
                ->restrictOnDelete();

            $table->foreignId('acknowledged_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('acknowledged_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(
                ['stock_consignment_id', 'acknowledged_at'],
                'sc_ack_header_date_index'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Acknowledgement quantities
        |--------------------------------------------------------------------------
        */

        Schema::create('stock_consignment_acknowledgement_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('acknowledgement_id')
                ->constrained(
                    'stock_consignment_acknowledgements',
                    indexName: 'sc_ack_items_ack_fk'
                )
                ->restrictOnDelete();

            $table->foreignId('stock_consignment_item_id')
                ->constrained(
                    'stock_consignment_items',
                    indexName: 'sc_ack_items_line_fk'
                )
                ->restrictOnDelete();

            $table->decimal('quantity_good', 12, 3)->default(0);
            $table->decimal('quantity_damaged', 12, 3)->default(0);
            $table->decimal('quantity_shortage', 12, 3)->default(0);

            $table->timestamps();

            $table->unique(
                ['acknowledgement_id', 'stock_consignment_item_id'],
                'sc_ack_items_event_line_unique'
            );

            $table->index(
                'stock_consignment_item_id',
                'sc_ack_items_line_index'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Shortage and damage investigations
        |--------------------------------------------------------------------------
        */

        Schema::create('stock_consignment_discrepancies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_consignment_item_id')
                ->constrained(
                    'stock_consignment_items',
                    indexName: 'sc_disc_line_fk'
                )
                ->restrictOnDelete();

            $table->foreignId('acknowledgement_item_id')->nullable()
                ->constrained(
                    'stock_consignment_acknowledgement_items',
                    indexName: 'sc_disc_ack_item_fk'
                )
                ->restrictOnDelete();

            $table->string('type', 20);
            $table->decimal('quantity', 12, 3);
            $table->string('status', 30)->default('open');

            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('reported_at');

            $table->string('resolution', 30)->nullable();
            $table->text('resolution_reason')->nullable();

            $table->foreignId('resolved_by')->nullable()
                ->constrained('users')->restrictOnDelete();

            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(
                ['stock_consignment_item_id', 'status'],
                'sc_disc_line_status_index'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Consignment operating expenses
        |--------------------------------------------------------------------------
        */

        Schema::create('stock_consignment_expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_consignment_id')
                ->constrained('stock_consignments')
                ->restrictOnDelete();

            $table->string('category', 40);
            $table->string('description', 500);
            $table->decimal('amount', 15, 2);
            $table->timestamp('incurred_at');

            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(
                ['stock_consignment_id', 'incurred_at'],
                'stock_consignment_expenses_date_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_consignment_expenses');
        Schema::dropIfExists('stock_consignment_discrepancies');
        Schema::dropIfExists('stock_consignment_acknowledgement_items');
        Schema::dropIfExists('stock_consignment_acknowledgements');
        Schema::dropIfExists('stock_consignment_items');
        Schema::dropIfExists('stock_consignments');

        Schema::dropIfExists('stock_processing_status_events');
        Schema::dropIfExists('stock_processing_outputs');
        Schema::dropIfExists('stock_processings');

        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_levels');

        Schema::dropIfExists('stock_item_price_history');
        Schema::dropIfExists('stock_item_approvals');

        Schema::dropIfExists('intake_items');
        Schema::dropIfExists('stock_items');
        Schema::dropIfExists('intakes');
    }
};
