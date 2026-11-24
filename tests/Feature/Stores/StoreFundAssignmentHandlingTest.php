<?php

namespace Tests\Feature\Stores;

use App\Actions\Stores\ApproveStoreFundAssignment;
use App\Actions\Stores\DispatchStoreFundAssignment;
use App\Actions\Stores\RejectStoreFundAssignment;
use App\Models\Store;
use App\Models\StoreFundAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreFundAssignmentHandlingTest extends TestCase
{
    use RefreshDatabase;

    private function createStore(): Store
    {
        return Store::create([
            'name' => 'Handling Test Store',
            'code' => 'HTS-001',
            'type' => 'store',
            'is_active' => true,
        ]);
    }

    private function createAssignment(
        Store $store,
        User $requester,
        string $status = StoreFundAssignment::STATUS_REQUESTED,
        string $source = StoreFundAssignment::SOURCE_STORE_REQUEST
    ): StoreFundAssignment {
        return $store->fundAssignments()->create([
            'business_date' => today()->toDateString(),
            'source' => $source,
            'purpose' => StoreFundAssignment::PURPOSE_DAILY_TRANSACTIONS,
            'status' => $status,
            'requested_cash' => '1500.00',
            'requested_bank' => '2500.00',
            'requested_by' => $requester->id,
        ]);
    }

    public function test_requested_assignment_can_be_approved_and_audited(): void
    {
        $actor = User::factory()->create();
        $requester = User::factory()->create();
        $assignment = $this->createAssignment(
            $this->createStore(),
            $requester
        );

        $this->actingAs($actor);

        Gate::shouldReceive('authorize')
            ->once()
            ->with('approve', \Mockery::type(StoreFundAssignment::class));

        $result = app(ApproveStoreFundAssignment::class)->execute($assignment);

        $this->assertSame(StoreFundAssignment::STATUS_APPROVED, $result->status);
        $this->assertSame($actor->id, $result->handled_by);
        $this->assertSame($actor->id, $result->approved_by);
        $this->assertNotNull($result->approved_at);

        $this->assertDatabaseHas('store_fund_assignment_events', [
            'store_fund_assignment_id' => $assignment->id,
            'actor_id' => $actor->id,
            'event_type' => 'approved',
        ]);
    }

    public function test_requested_assignment_can_be_rejected_with_reason_and_audit(): void
    {
        $actor = User::factory()->create();
        $requester = User::factory()->create();
        $assignment = $this->createAssignment(
            $this->createStore(),
            $requester
        );

        $this->actingAs($actor);

        Gate::shouldReceive('authorize')
            ->once()
            ->with('reject', \Mockery::type(StoreFundAssignment::class));

        $result = app(RejectStoreFundAssignment::class)->execute(
            $assignment,
            'Insufficient supporting details.'
        );

        $this->assertSame(StoreFundAssignment::STATUS_REJECTED, $result->status);
        $this->assertSame($actor->id, $result->handled_by);
        $this->assertSame(
            'Insufficient supporting details.',
            $result->rejection_reason
        );

        $this->assertDatabaseHas('store_fund_assignment_events', [
            'store_fund_assignment_id' => $assignment->id,
            'actor_id' => $actor->id,
            'event_type' => 'rejected',
        ]);
    }

    public function test_rejection_requires_at_least_ten_non_whitespace_characters(): void
    {
        $actor = User::factory()->create();
        $requester = User::factory()->create();
        $assignment = $this->createAssignment(
            $this->createStore(),
            $requester
        );

        $this->actingAs($actor);

        $this->expectException(ValidationException::class);

        app(RejectStoreFundAssignment::class)->execute($assignment, '   short   ');
    }

    public function test_approved_assignment_can_be_dispatched_with_exact_amounts_and_audit(): void
    {
        $actor = User::factory()->create();
        $requester = User::factory()->create();
        $assignment = $this->createAssignment(
            $this->createStore(),
            $requester,
            StoreFundAssignment::STATUS_APPROVED
        );

        $this->actingAs($actor);

        Gate::shouldReceive('authorize')
            ->once()
            ->with('dispatch', \Mockery::type(StoreFundAssignment::class));

        $result = app(DispatchStoreFundAssignment::class)->execute(
            $assignment,
            [
                'sent_cash' => '1200.00',
                'sent_bank' => '2000.00',
            ]
        );

        $this->assertSame(StoreFundAssignment::STATUS_SENT, $result->status);
        $this->assertSame('1200.00', $result->sent_cash);
        $this->assertSame('2000.00', $result->sent_bank);
        $this->assertSame($actor->id, $result->dispatched_by);
        $this->assertNotNull($result->dispatched_at);

        $this->assertDatabaseHas('store_fund_assignment_events', [
            'store_fund_assignment_id' => $assignment->id,
            'actor_id' => $actor->id,
            'event_type' => 'dispatched',
        ]);
    }

    public function test_dispatch_rejects_zero_cash_and_zero_bank(): void
    {
        $actor = User::factory()->create();
        $requester = User::factory()->create();
        $assignment = $this->createAssignment(
            $this->createStore(),
            $requester,
            StoreFundAssignment::STATUS_APPROVED
        );

        $this->actingAs($actor);

        $this->expectException(ValidationException::class);

        app(DispatchStoreFundAssignment::class)->execute(
            $assignment,
            [
                'sent_cash' => '0.00',
                'sent_bank' => '0.00',
            ]
        );
    }

    public function test_dispatch_rejects_negative_amount(): void
    {
        $actor = User::factory()->create();
        $requester = User::factory()->create();
        $assignment = $this->createAssignment(
            $this->createStore(),
            $requester,
            StoreFundAssignment::STATUS_APPROVED
        );

        $this->actingAs($actor);

        $this->expectException(ValidationException::class);

        app(DispatchStoreFundAssignment::class)->execute(
            $assignment,
            [
                'sent_cash' => '-1.00',
                'sent_bank' => '100.00',
            ]
        );
    }

    public function test_approval_rejects_assignment_not_in_requested_status(): void
    {
        $actor = User::factory()->create();
        $requester = User::factory()->create();
        $assignment = $this->createAssignment(
            $this->createStore(),
            $requester,
            StoreFundAssignment::STATUS_APPROVED
        );

        $this->actingAs($actor);

        Gate::shouldReceive('authorize')
            ->once()
            ->with('approve', \Mockery::type(StoreFundAssignment::class));

        $this->expectException(ValidationException::class);

        app(ApproveStoreFundAssignment::class)->execute($assignment);
    }

    public function test_dispatch_rejects_assignment_not_in_approved_status(): void
    {
        $actor = User::factory()->create();
        $requester = User::factory()->create();
        $assignment = $this->createAssignment(
            $this->createStore(),
            $requester,
            StoreFundAssignment::STATUS_REQUESTED
        );

        $this->actingAs($actor);

        Gate::shouldReceive('authorize')
            ->once()
            ->with('dispatch', \Mockery::type(StoreFundAssignment::class));

        $this->expectException(ValidationException::class);

        app(DispatchStoreFundAssignment::class)->execute(
            $assignment,
            [
                'sent_cash' => '100.00',
                'sent_bank' => '0.00',
            ]
        );
    }
}
