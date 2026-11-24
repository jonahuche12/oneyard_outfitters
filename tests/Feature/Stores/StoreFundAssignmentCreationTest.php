<?php

namespace Tests\Feature\Stores;

use App\Actions\Stores\CreateDirectStoreFundAssignment;
use App\Actions\Stores\RequestStoreFunds;
use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreFundAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreFundAssignmentCreationTest extends TestCase
{
    use RefreshDatabase;

    private function createStore(): Store
    {
        return Store::create([
            'name' => 'Test Store',
            'code' => 'TST-001',
            'type' => 'store',
            'is_active' => true,
        ]);
    }

    private function createDailyRecord(
        Store $store,
        User $openedBy
    ): StoreDailyCashRecord {
        return StoreDailyCashRecord::create([
            'store_id' => $store->id,
            'business_date' => today()->toDateString(),
            'opening_cash' => 1000,
            'opening_bank' => 2000,
            'opened_by' => $openedBy->id,
        ]);
    }

    private function validRequestData(): array
    {
        return [
            'purpose' => StoreFundAssignment::PURPOSES[0],
            'requested_cash' => '1500.00',
            'requested_bank' => '2500.00',
            'note' => 'Funds required for store operations.',
            'reference' => 'REQ-001',
        ];
    }

    private function expectFundGateAuthorization(
        string $ability
    ): void {
        Gate::shouldReceive('authorize')
            ->once()
            ->with(
                $ability,
                \Mockery::on(
                    fn ($arguments): bool =>
                        is_array($arguments)
                        && count($arguments) === 2
                        && $arguments[0] === StoreFundAssignment::class
                        && $arguments[1] instanceof Store
                )
            );
    }

    public function test_store_request_creates_requested_assignment_and_audit_event(): void
    {
        $actor = User::factory()->create();
        $store = $this->createStore();

        $this->actingAs($actor);

        $this->expectFundGateAuthorization('request');

        $assignment = app(RequestStoreFunds::class)->execute(
            $store,
            $this->validRequestData()
        );

        $this->assertDatabaseHas('store_fund_assignments', [
            'id' => $assignment->id,
            'store_id' => $store->id,
            'source' => StoreFundAssignment::SOURCE_STORE_REQUEST,
            'status' => StoreFundAssignment::STATUS_REQUESTED,
            'requested_cash' => '1500.00',
            'requested_bank' => '2500.00',
            'requested_by' => $actor->id,
            'handled_by' => null,
            'approved_by' => null,
        ]);

        $this->assertTrue(
            \Illuminate\Support\Facades\DB::table('store_fund_assignments')
                ->where('id', $assignment->id)
                ->whereDate('business_date', today()->toDateString())
                ->exists()
        );

        $this->assertDatabaseHas('store_fund_assignment_events', [
            'store_fund_assignment_id' => $assignment->id,
            'actor_id' => $actor->id,
            'event_type' => 'requested',
        ]);

        $this->assertNull($assignment->store_daily_cash_record_id);
    }

    public function test_store_request_links_to_existing_daily_cash_record(): void
    {
        $actor = User::factory()->create();
        $store = $this->createStore();

        $dailyRecord = $this->createDailyRecord($store, $actor);

        $this->actingAs($actor);

        $this->expectFundGateAuthorization('request');

        $assignment = app(RequestStoreFunds::class)->execute(
            $store,
            $this->validRequestData()
        );

        $this->assertSame(
            $dailyRecord->id,
            $assignment->store_daily_cash_record_id
        );

        $this->assertDatabaseHas('store_fund_assignments', [
            'id' => $assignment->id,
            'store_daily_cash_record_id' => $dailyRecord->id,
        ]);
    }

    public function test_direct_assignment_is_created_as_approved_and_audited(): void
    {
        $actor = User::factory()->create();
        $store = $this->createStore();

        $this->actingAs($actor);

        $this->expectFundGateAuthorization('createDirect');

        $assignment = app(CreateDirectStoreFundAssignment::class)->execute(
            $store,
            $this->validRequestData()
        );

        $this->assertDatabaseHas('store_fund_assignments', [
            'id' => $assignment->id,
            'store_id' => $store->id,
            'source' => StoreFundAssignment::SOURCE_DIRECT_ASSIGNMENT,
            'status' => StoreFundAssignment::STATUS_APPROVED,
            'requested_cash' => '1500.00',
            'requested_bank' => '2500.00',
            'requested_by' => $actor->id,
            'handled_by' => $actor->id,
            'approved_by' => $actor->id,
        ]);

        $this->assertTrue(
            \Illuminate\Support\Facades\DB::table('store_fund_assignments')
                ->where('id', $assignment->id)
                ->whereDate('business_date', today()->toDateString())
                ->exists()
        );

        $this->assertNotNull($assignment->approved_at);

        $this->assertDatabaseHas('store_fund_assignment_events', [
            'store_fund_assignment_id' => $assignment->id,
            'actor_id' => $actor->id,
            'event_type' => 'direct_assigned',
        ]);
    }

    public function test_direct_assignment_links_to_existing_daily_cash_record(): void
    {
        $actor = User::factory()->create();
        $store = $this->createStore();

        $dailyRecord = $this->createDailyRecord($store, $actor);

        $this->actingAs($actor);

        $this->expectFundGateAuthorization('createDirect');

        $assignment = app(CreateDirectStoreFundAssignment::class)->execute(
            $store,
            $this->validRequestData()
        );

        $this->assertSame(
            $dailyRecord->id,
            $assignment->store_daily_cash_record_id
        );
    }

    public function test_store_request_rejects_zero_cash_and_zero_bank(): void
    {
        $actor = User::factory()->create();
        $store = $this->createStore();

        $this->actingAs($actor);

        $data = $this->validRequestData();
        $data['requested_cash'] = '0.00';
        $data['requested_bank'] = '0.00';

        try {
            app(RequestStoreFunds::class)->execute($store, $data);
            $this->fail('Expected validation to reject an empty request.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'requested_cash',
                $exception->errors()
            );
            $this->assertArrayHasKey(
                'requested_bank',
                $exception->errors()
            );
        }
    }

    public function test_direct_assignment_rejects_zero_cash_and_zero_bank(): void
    {
        $actor = User::factory()->create();
        $store = $this->createStore();

        $this->actingAs($actor);

        $data = $this->validRequestData();
        $data['requested_cash'] = '0.00';
        $data['requested_bank'] = '0.00';

        try {
            app(CreateDirectStoreFundAssignment::class)->execute(
                $store,
                $data
            );

            $this->fail('Expected validation to reject an empty assignment.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'requested_cash',
                $exception->errors()
            );
            $this->assertArrayHasKey(
                'requested_bank',
                $exception->errors()
            );
        }
    }

    public function test_store_request_rejects_negative_amounts(): void
    {
        $actor = User::factory()->create();
        $store = $this->createStore();

        $this->actingAs($actor);

        $data = $this->validRequestData();
        $data['requested_cash'] = '-1.00';

        $this->expectException(ValidationException::class);

        app(RequestStoreFunds::class)->execute($store, $data);
    }

    public function test_direct_assignment_rejects_invalid_purpose(): void
    {
        $actor = User::factory()->create();
        $store = $this->createStore();

        $this->actingAs($actor);

        $data = $this->validRequestData();
        $data['purpose'] = 'invalid-purpose';

        $this->expectException(ValidationException::class);

        app(CreateDirectStoreFundAssignment::class)->execute(
            $store,
            $data
        );
    }
}
