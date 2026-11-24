<?php

namespace Tests\Feature\Stores;

use App\Actions\Stores\ConfirmStoreFundAssignmentReceipt;
use App\Actions\Stores\CorrectStoreFundAssignmentDispatch;
use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreFundAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreFundAssignmentConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private function createStore(): Store
    {
        return Store::create([
            'name' => 'Confirmation Test Store',
            'code' => 'CTS-001',
            'type' => 'store',
            'is_active' => true,
        ]);
    }

    private function createSentAssignment(
        Store $store,
        User $requester,
        User $dispatcher
    ): StoreFundAssignment {
        return $store->fundAssignments()->create([
            'business_date' => today()->toDateString(),
            'source' => StoreFundAssignment::SOURCE_STORE_REQUEST,
            'purpose' => StoreFundAssignment::PURPOSE_DAILY_TRANSACTIONS,
            'status' => StoreFundAssignment::STATUS_SENT,
            'requested_cash' => '1500.00',
            'requested_bank' => '2500.00',
            'sent_cash' => '1200.00',
            'sent_bank' => '2000.00',
            'requested_by' => $requester->id,
            'handled_by' => $dispatcher->id,
            'approved_by' => $dispatcher->id,
            'approved_at' => now(),
            'dispatched_by' => $dispatcher->id,
            'dispatched_at' => now(),
        ]);
    }

    public function test_dispatched_assignment_can_be_confirmed_with_exact_cash_and_bank_amounts(): void
    {
        $coordinator = User::factory()->create();
        $requester = User::factory()->create();
        $dispatcher = User::factory()->create();

        $assignment = $this->createSentAssignment(
            $this->createStore(),
            $requester,
            $dispatcher
        );

        $this->actingAs($coordinator);

        Gate::shouldReceive('authorize')
            ->once()
            ->with('confirm', \Mockery::type(StoreFundAssignment::class));

        $result = app(ConfirmStoreFundAssignmentReceipt::class)->execute(
            $assignment,
            [
                'received_cash' => '1200.00',
                'received_bank' => '2000.00',
            ]
        );

        $this->assertSame(
            StoreFundAssignment::STATUS_CONFIRMED,
            $result->status
        );
        $this->assertSame($coordinator->id, $result->confirmed_by);
        $this->assertNotNull($result->confirmed_at);

        $event = $result->events()
            ->where('event_type', 'receipt_confirmed')
            ->firstOrFail();

        $this->assertSame($coordinator->id, $event->actor_id);
        $this->assertSame('1200.00', $event->details['received_cash']);
        $this->assertSame('2000.00', $event->details['received_bank']);
    }

    public function test_confirmation_links_previously_unlinked_assignment_to_open_daily_record(): void
    {
        $coordinator = User::factory()->create();
        $requester = User::factory()->create();
        $dispatcher = User::factory()->create();

        $store = $this->createStore();

        // The assignment existed before opening but was still unconfirmed.
        $assignment = $this->createSentAssignment(
            $store,
            $requester,
            $dispatcher
        );

        $this->assertNull($assignment->store_daily_cash_record_id);

        // Opening excludes this sent assignment from opening allocations.
        $dailyRecord = $store->dailyCashRecords()->create([
            'business_date' => today()->toDateString(),
            'opening_cash' => '2500.00',
            'opening_bank' => '7500.00',
            'entered_opening_cash' => '2500.00',
            'entered_opening_bank' => '7500.00',
            'assigned_opening_cash' => '0.00',
            'assigned_opening_bank' => '0.00',
            'opened_by' => $coordinator->id,
        ]);

        $this->actingAs($coordinator);

        Gate::shouldReceive('authorize')
            ->once()
            ->with('confirm', \Mockery::type(StoreFundAssignment::class));

        $result = app(ConfirmStoreFundAssignmentReceipt::class)->execute(
            $assignment,
            [
                'received_cash' => '1200.00',
                'received_bank' => '2000.00',
            ]
        );

        $this->assertSame(
            StoreFundAssignment::STATUS_CONFIRMED,
            $result->status
        );

        $this->assertSame(
            $dailyRecord->id,
            $result->store_daily_cash_record_id
        );

        $this->assertDatabaseHas('store_fund_assignments', [
            'id' => $assignment->id,
            'status' => StoreFundAssignment::STATUS_CONFIRMED,
            'store_daily_cash_record_id' => $dailyRecord->id,
        ]);

        $event = $result->events()
            ->where('event_type', 'receipt_confirmed')
            ->firstOrFail();

        $this->assertSame(
            $dailyRecord->id,
            $event->details['store_daily_cash_record_id']
        );

        // Confirmation must not rewrite the opening balances.
        $dailyRecord->refresh();

        $this->assertSame('2500.00', $dailyRecord->opening_cash);
        $this->assertSame('7500.00', $dailyRecord->opening_bank);
        $this->assertSame('0.00', $dailyRecord->assigned_opening_cash);
        $this->assertSame('0.00', $dailyRecord->assigned_opening_bank);
    }

    public function test_receipt_confirmation_rejects_cash_mismatch(): void
    {
        $coordinator = User::factory()->create();
        $requester = User::factory()->create();
        $dispatcher = User::factory()->create();

        $assignment = $this->createSentAssignment(
            $this->createStore(),
            $requester,
            $dispatcher
        );

        $this->actingAs($coordinator);

        Gate::shouldReceive('authorize')
            ->once()
            ->with('confirm', \Mockery::type(StoreFundAssignment::class));

        try {
            app(ConfirmStoreFundAssignmentReceipt::class)->execute(
                $assignment,
                [
                    'received_cash' => '1199.00',
                    'received_bank' => '2000.00',
                ]
            );

            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'received_cash',
                $exception->errors()
            );
        }

        $this->assertDatabaseHas('store_fund_assignments', [
            'id' => $assignment->id,
            'status' => StoreFundAssignment::STATUS_SENT,
        ]);

        $this->assertDatabaseMissing('store_fund_assignment_events', [
            'store_fund_assignment_id' => $assignment->id,
            'event_type' => 'receipt_confirmed',
        ]);
    }

    public function test_receipt_confirmation_rejects_bank_mismatch(): void
    {
        $coordinator = User::factory()->create();
        $requester = User::factory()->create();
        $dispatcher = User::factory()->create();

        $assignment = $this->createSentAssignment(
            $this->createStore(),
            $requester,
            $dispatcher
        );

        $this->actingAs($coordinator);

        Gate::shouldReceive('authorize')
            ->once()
            ->with('confirm', \Mockery::type(StoreFundAssignment::class));

        $this->expectException(ValidationException::class);

        app(ConfirmStoreFundAssignmentReceipt::class)->execute(
            $assignment,
            [
                'received_cash' => '1200.00',
                'received_bank' => '1999.00',
            ]
        );
    }

    public function test_receipt_confirmation_rejects_zero_cash_and_zero_bank(): void
    {
        $coordinator = User::factory()->create();
        $requester = User::factory()->create();
        $dispatcher = User::factory()->create();

        $assignment = $this->createSentAssignment(
            $this->createStore(),
            $requester,
            $dispatcher
        );

        $this->actingAs($coordinator);

        Gate::shouldReceive('authorize')
            ->once()
            ->with('confirm', \Mockery::type(StoreFundAssignment::class));

        $this->expectException(ValidationException::class);

        app(ConfirmStoreFundAssignmentReceipt::class)->execute(
            $assignment,
            [
                'received_cash' => '0.00',
                'received_bank' => '0.00',
            ]
        );
    }

    public function test_receipt_confirmation_rejects_negative_amount(): void
    {
        $coordinator = User::factory()->create();
        $requester = User::factory()->create();
        $dispatcher = User::factory()->create();

        $assignment = $this->createSentAssignment(
            $this->createStore(),
            $requester,
            $dispatcher
        );

        $this->actingAs($coordinator);

        $this->expectException(ValidationException::class);

        app(ConfirmStoreFundAssignmentReceipt::class)->execute(
            $assignment,
            [
                'received_cash' => '-1.00',
                'received_bank' => '2000.00',
            ]
        );
    }

    public function test_already_confirmed_assignment_cannot_be_confirmed_again(): void
    {
        $coordinator = User::factory()->create();
        $requester = User::factory()->create();
        $dispatcher = User::factory()->create();

        $assignment = $this->createSentAssignment(
            $this->createStore(),
            $requester,
            $dispatcher
        );

        $assignment->forceFill([
            'status' => StoreFundAssignment::STATUS_CONFIRMED,
            'confirmed_by' => $coordinator->id,
            'confirmed_at' => now(),
        ])->save();

        $this->actingAs($coordinator);

        Gate::shouldReceive('authorize')
            ->once()
            ->with('confirm', \Mockery::type(StoreFundAssignment::class));

        $this->expectException(ValidationException::class);

        app(ConfirmStoreFundAssignmentReceipt::class)->execute(
            $assignment,
            [
                'received_cash' => '1200.00',
                'received_bank' => '2000.00',
            ]
        );
    }

    public function test_dispatch_can_be_corrected_and_previous_and_new_values_are_audited(): void
    {
        $dispatcher = User::factory()->create();
        $requester = User::factory()->create();

        $assignment = $this->createSentAssignment(
            $this->createStore(),
            $requester,
            $dispatcher
        );

        $this->actingAs($dispatcher);

        Gate::shouldReceive('authorize')
            ->once()
            ->with('correctDispatch', \Mockery::type(StoreFundAssignment::class));

        $result = app(CorrectStoreFundAssignmentDispatch::class)->execute(
            $assignment,
            [
                'sent_cash' => '1100.00',
                'sent_bank' => '2100.00',
            ]
        );

        $this->assertSame(StoreFundAssignment::STATUS_SENT, $result->status);
        $this->assertSame('1100.00', $result->sent_cash);
        $this->assertSame('2100.00', $result->sent_bank);

        // The original dispatcher remains the policy reference.
        $this->assertSame($dispatcher->id, $result->dispatched_by);

        $event = $result->events()
            ->where('event_type', 'dispatch_corrected')
            ->firstOrFail();

        $this->assertSame($dispatcher->id, $event->actor_id);
        $this->assertSame('1200.00', $event->details['previous_sent_cash']);
        $this->assertSame('2000.00', $event->details['previous_sent_bank']);
        $this->assertSame('1100.00', $event->details['corrected_sent_cash']);
        $this->assertSame('2100.00', $event->details['corrected_sent_bank']);
        $this->assertSame($dispatcher->id, $event->details['dispatched_by']);
        $this->assertSame($dispatcher->id, $event->details['corrected_by']);
        $this->assertNotEmpty($event->details['corrected_at']);
    }

    public function test_dispatch_correction_rejects_both_amounts_as_zero(): void
    {
        $dispatcher = User::factory()->create();
        $requester = User::factory()->create();

        $assignment = $this->createSentAssignment(
            $this->createStore(),
            $requester,
            $dispatcher
        );

        $this->actingAs($dispatcher);

        $this->expectException(ValidationException::class);

        app(CorrectStoreFundAssignmentDispatch::class)->execute(
            $assignment,
            [
                'sent_cash' => '0.00',
                'sent_bank' => '0.00',
            ]
        );
    }

    public function test_dispatch_correction_rejects_negative_amount(): void
    {
        $dispatcher = User::factory()->create();
        $requester = User::factory()->create();

        $assignment = $this->createSentAssignment(
            $this->createStore(),
            $requester,
            $dispatcher
        );

        $this->actingAs($dispatcher);

        $this->expectException(ValidationException::class);

        app(CorrectStoreFundAssignmentDispatch::class)->execute(
            $assignment,
            [
                'sent_cash' => '-1.00',
                'sent_bank' => '2100.00',
            ]
        );
    }

    public function test_dispatch_correction_rejects_unchanged_amounts(): void
    {
        $dispatcher = User::factory()->create();
        $requester = User::factory()->create();

        $assignment = $this->createSentAssignment(
            $this->createStore(),
            $requester,
            $dispatcher
        );

        $this->actingAs($dispatcher);

        Gate::shouldReceive('authorize')
            ->once()
            ->with('correctDispatch', \Mockery::type(StoreFundAssignment::class));

        $this->expectException(ValidationException::class);

        app(CorrectStoreFundAssignmentDispatch::class)->execute(
            $assignment,
            [
                'sent_cash' => '1200.00',
                'sent_bank' => '2000.00',
            ]
        );
    }

    public function test_dispatch_correction_rejects_assignment_not_in_sent_status(): void
    {
        $dispatcher = User::factory()->create();
        $requester = User::factory()->create();

        $assignment = $this->createSentAssignment(
            $this->createStore(),
            $requester,
            $dispatcher
        );

        $assignment->forceFill([
            'status' => StoreFundAssignment::STATUS_CONFIRMED,
        ])->save();

        $this->actingAs($dispatcher);

        Gate::shouldReceive('authorize')
            ->once()
            ->with('correctDispatch', \Mockery::type(StoreFundAssignment::class));

        $this->expectException(ValidationException::class);

        app(CorrectStoreFundAssignmentDispatch::class)->execute(
            $assignment,
            [
                'sent_cash' => '1100.00',
                'sent_bank' => '2100.00',
            ]
        );
    }
}
