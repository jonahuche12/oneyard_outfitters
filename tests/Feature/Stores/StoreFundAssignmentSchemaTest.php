<?php

namespace Tests\Feature\Stores;

use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreFundAssignment;
use App\Models\StoreFundAssignmentEvent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StoreFundAssignmentSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_funds_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('store_fund_assignments'));
        $this->assertTrue(Schema::hasTable('store_fund_assignment_events'));
    }

    public function test_assignment_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('store_fund_assignments', [
            'id',
            'store_id',
            'store_daily_cash_record_id',
            'business_date',
            'source',
            'purpose',
            'status',
            'requested_cash',
            'requested_bank',
            'sent_cash',
            'sent_bank',
            'requested_by',
            'handled_by',
            'approved_by',
            'approved_at',
            'dispatched_by',
            'dispatched_at',
            'confirmed_by',
            'confirmed_at',
            'rejection_reason',
            'reference',
            'note',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_event_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('store_fund_assignment_events', [
            'id',
            'store_fund_assignment_id',
            'actor_id',
            'event_type',
            'details',
            'created_at',
        ]));
    }

    public function test_assignment_amount_columns_are_decimal_with_two_places(): void
    {
        foreach ([
            'requested_cash',
            'requested_bank',
            'sent_cash',
            'sent_bank',
        ] as $column) {
            $type = Schema::getColumnType('store_fund_assignments', $column);

            $expectedType = DB::connection()->getDriverName() === 'sqlite'
                ? 'numeric'
                : 'decimal';

            $this->assertSame($expectedType, $type, $column);
        }
    }

    public function test_assignment_source_purpose_and_status_constants_are_defined(): void
    {
        $this->assertContains(
            StoreFundAssignment::SOURCE_STORE_REQUEST,
            StoreFundAssignment::SOURCES
        );

        $this->assertContains(
            StoreFundAssignment::SOURCE_DIRECT_ASSIGNMENT,
            StoreFundAssignment::SOURCES
        );

        $this->assertContains(
            StoreFundAssignment::PURPOSE_DAILY_TRANSACTIONS,
            StoreFundAssignment::PURPOSES
        );

        $this->assertContains(
            StoreFundAssignment::PURPOSE_PHYSICAL_PURCHASES,
            StoreFundAssignment::PURPOSES
        );

        $this->assertContains(
            StoreFundAssignment::PURPOSE_UTILITIES,
            StoreFundAssignment::PURPOSES
        );

        foreach (StoreFundAssignment::STATUSES as $status) {
            $this->assertNotEmpty($status);
        }
    }

    public function test_assignment_amount_and_date_casts_are_configured(): void
    {
        $assignment = new StoreFundAssignment();

        $casts = $assignment->getCasts();

        $this->assertSame('date', $casts['business_date']);
        $this->assertSame('decimal:2', $casts['requested_cash']);
        $this->assertSame('decimal:2', $casts['requested_bank']);
        $this->assertSame('decimal:2', $casts['sent_cash']);
        $this->assertSame('decimal:2', $casts['sent_bank']);
        $this->assertSame('datetime', $casts['approved_at']);
        $this->assertSame('datetime', $casts['dispatched_at']);
        $this->assertSame('datetime', $casts['confirmed_at']);
    }

    public function test_event_details_are_cast_to_array(): void
    {
        $event = new StoreFundAssignmentEvent();

        $this->assertSame('array', $event->getCasts()['details']);
    }

    public function test_store_fund_assignment_relationships_are_configured(): void
    {
        $assignment = new StoreFundAssignment();

        $this->assertInstanceOf(BelongsTo::class, $assignment->store());
        $this->assertInstanceOf(BelongsTo::class, $assignment->dailyCashRecord());
        $this->assertInstanceOf(BelongsTo::class, $assignment->requestedBy());
        $this->assertInstanceOf(BelongsTo::class, $assignment->handledBy());
        $this->assertInstanceOf(BelongsTo::class, $assignment->approvedBy());
        $this->assertInstanceOf(BelongsTo::class, $assignment->dispatchedBy());
        $this->assertInstanceOf(BelongsTo::class, $assignment->confirmedBy());
        $this->assertInstanceOf(HasMany::class, $assignment->events());
    }

    public function test_event_relationships_are_configured(): void
    {
        $event = new StoreFundAssignmentEvent();

        $this->assertInstanceOf(BelongsTo::class, $event->assignment());
        $this->assertInstanceOf(BelongsTo::class, $event->actor());
    }

    public function test_store_and_daily_cash_models_have_fund_assignment_relationships(): void
    {
        $store = new Store();
        $dailyCash = new StoreDailyCashRecord();

        $this->assertInstanceOf(HasMany::class, $store->fundAssignments());
        $this->assertInstanceOf(HasMany::class, $dailyCash->fundAssignments());

        $this->assertSame(
            'store_daily_cash_record_id',
            $dailyCash->fundAssignments()->getForeignKeyName()
        );
    }
}
