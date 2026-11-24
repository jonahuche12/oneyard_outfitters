<?php

namespace Tests\Feature\Stores;

use App\Actions\Stores\ReviewStoreRemittance;
use App\Actions\Stores\SubmitStoreRemittance;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreRemittance;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreRemittanceHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_staff_can_view_only_their_store_remittance_history(): void
    {
        $store = $this->makeStore();
        $staff = $this->assign($store, StoreStaff::ROLE_COORDINATOR);
        [$ownRemittance] = $this->pendingRemittance($store, $staff);

        $otherStore = $this->makeStore();
        $otherStaff = $this->assign($otherStore, StoreStaff::ROLE_COORDINATOR);
        [$otherRemittance] = $this->pendingRemittance($otherStore, $otherStaff);

        $response = $this->actingAs($staff)
            ->getJson(route('stores.remittances.index', $store));

        $response->assertOk()
            ->assertJsonFragment(['id' => $ownRemittance->id])
            ->assertJsonMissing(['id' => $otherRemittance->id]);
    }

    public function test_assigned_staff_can_submit_and_request_store_id_is_ignored(): void
    {
        $store = $this->makeStore();
        $staff = $this->assign($store, StoreStaff::ROLE_COORDINATOR);
        $day = $this->closeDay($store, $staff);

        $otherStore = $this->makeStore();

        $response = $this->actingAs($staff)->postJson(
            route('stores.remittances.store', $store),
            [
                'store_daily_cash_record_id' => $day->id,
                'amount_cash' => '500.00',
                'amount_bank' => '0.00',
                'method' => StoreRemittance::METHOD_CASH_HANDOVER,
                'store_id' => $otherStore->id,
            ]
        );

        $response->assertCreated();

        $this->assertDatabaseHas('store_remittances', [
            'store_daily_cash_record_id' => $day->id,
            'store_id' => $store->id,
            'amount_cash' => '500.00',
            'status' => StoreRemittance::STATUS_PENDING,
            'submitted_by' => $staff->id,
        ]);

        $this->assertDatabaseMissing('store_remittances', [
            'store_daily_cash_record_id' => $day->id,
            'store_id' => $otherStore->id,
        ]);
    }

    public function test_unassigned_user_cannot_view_or_submit_for_a_store(): void
    {
        $store = $this->makeStore();
        $outsider = User::factory()->create(['is_active' => true]);
        $day = $this->closeDay($store, $this->assign($store, StoreStaff::ROLE_COORDINATOR));

        $this->actingAs($outsider)
            ->getJson(route('stores.remittances.index', $store))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->postJson(route('stores.remittances.store', $store), [
                'store_daily_cash_record_id' => $day->id,
                'amount_cash' => '500.00',
                'amount_bank' => '0.00',
                'method' => StoreRemittance::METHOD_CASH_HANDOVER,
            ])
            ->assertForbidden();
    }

    public function test_reviewer_queue_requires_permission_and_contains_pending_records_only(): void
    {
        $outsider = User::factory()->create(['is_active' => true]);

        $this->actingAs($outsider)
            ->getJson(route('store-remittances.index'))
            ->assertForbidden();

        $store = $this->makeStore();
        $submitter = $this->assign($store, StoreStaff::ROLE_COORDINATOR);
        [$pending, $day] = $this->pendingRemittance($store, $submitter);

        $reviewed = app(SubmitStoreRemittance::class)->execute(
            $submitter,
            $store,
            [
                'store_daily_cash_record_id' => $day->id,
                'amount_cash' => '300.00',
                'amount_bank' => '0.00',
                'method' => StoreRemittance::METHOD_CASH_HANDOVER,
            ]
        );

        $reviewer = $this->reviewer();

        app(ReviewStoreRemittance::class)->execute(
            $reviewer,
            $reviewed,
            'confirm'
        );

        $this->actingAs($reviewer)
            ->getJson(route('store-remittances.index'))
            ->assertOk()
            ->assertJsonFragment(['id' => $pending->id])
            ->assertJsonMissing(['id' => $reviewed->id]);
    }

    public function test_reviewer_can_confirm_a_remittance_through_http(): void
    {
        $store = $this->makeStore();
        $submitter = $this->assign($store, StoreStaff::ROLE_COORDINATOR);
        [$remittance] = $this->pendingRemittance($store, $submitter);
        $reviewer = $this->reviewer();

        $this->actingAs($reviewer)
            ->postJson(route('store-remittances.confirm', $remittance))
            ->assertOk();

        $this->assertDatabaseHas('store_remittances', [
            'id' => $remittance->id,
            'status' => StoreRemittance::STATUS_CONFIRMED,
            'reviewed_by' => $reviewer->id,
        ]);
    }

    public function test_reviewer_can_reject_a_remittance_through_http(): void
    {
        $store = $this->makeStore();
        $submitter = $this->assign($store, StoreStaff::ROLE_COORDINATOR);
        [$remittance] = $this->pendingRemittance($store, $submitter);
        $reviewer = $this->reviewer();

        $this->actingAs($reviewer)
            ->postJson(route('store-remittances.reject', $remittance), [
                'review_reason' => 'The submitted amount does not match the handover record.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('store_remittances', [
            'id' => $remittance->id,
            'status' => StoreRemittance::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
        ]);
    }

    public function test_submitter_cannot_review_their_own_remittance_through_http(): void
    {
        $store = $this->makeStore();
        $submitter = $this->assign($store, StoreStaff::ROLE_COORDINATOR);
        [$remittance] = $this->pendingRemittance($store, $submitter);

        $this->grantReviewPermission($submitter);

        $this->actingAs($submitter)
            ->postJson(route('store-remittances.confirm', $remittance))
            ->assertForbidden();

        $this->assertDatabaseHas('store_remittances', [
            'id' => $remittance->id,
            'status' => StoreRemittance::STATUS_PENDING,
        ]);
    }

    private function pendingRemittance(
        Store $store,
        User $submitter,
        array $overrides = []
    ): array {
        $day = $this->closeDay($store, $submitter);

        $remittance = app(SubmitStoreRemittance::class)->execute(
            $submitter,
            $store,
            array_merge([
                'store_daily_cash_record_id' => $day->id,
                'amount_cash' => '2000.00',
                'amount_bank' => '0.00',
                'method' => StoreRemittance::METHOD_CASH_HANDOVER,
            ], $overrides)
        );

        return [$remittance, $day];
    }

    private function reviewer(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->grantReviewPermission($user);

        return $user;
    }

    private function grantReviewPermission(User $user): void
    {
        $permission = Permission::query()->firstOrCreate(
            ['slug' => ReviewStoreRemittance::PERMISSION],
            ['name' => ReviewStoreRemittance::PERMISSION]
        );

        $role = Role::factory()->create([
            'name' => 'Remittance HTTP Reviewer '.uniqid(),
            'slug' => 'remittance-http-reviewer-'.uniqid(),
        ]);

        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $user->roles()->attach($role);
    }

    private function closeDay(Store $store, User $user): StoreDailyCashRecord
    {
        return $store->dailyCashRecords()->create([
            'business_date' => today()->toDateString(),
            'opening_cash' => '1000.00',
            'opening_bank' => '5000.00',
            'closing_cash' => '3000.00',
            'closing_bank' => '7000.00',
            'opened_by' => $user->id,
            'closed_by' => $user->id,
        ]);
    }

    private function makeStore(): Store
    {
        return Store::query()->create([
            'name' => 'Remittance HTTP Store '.str()->random(8),
            'code' => strtoupper(fake()->unique()->bothify('RH###??')),
            'is_active' => true,
        ]);
    }

    private function assign(Store $store, string $role): User
    {
        $user = User::factory()->create(['is_active' => true]);

        StoreStaff::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role,
            'starts_at' => now()->subMinute(),
        ]);

        return $user;
    }
}
