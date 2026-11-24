<?php

namespace Tests\Feature\Stores;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreRemittance;
use App\Models\StoreStaff;
use App\Models\User;
use App\Policies\StoreRemittancePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class StoreRemittancePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewer_can_view_the_queue_and_any_remission(): void
    {
        [$remittance] = $this->remittance();
        $reviewer = $this->reviewer();

        $this->assertTrue(Gate::forUser($reviewer)->allows('viewAny', StoreRemittance::class));
        $this->assertTrue(Gate::forUser($reviewer)->allows('view', $remittance));
    }

    public function test_user_without_the_permission_cannot_view_the_queue(): void
    {
        $plain = User::factory()->create(['is_active' => true]);

        $this->assertFalse(Gate::forUser($plain)->allows('viewAny', StoreRemittance::class));
    }

    public function test_inactive_reviewer_is_denied_everything(): void
    {
        [$remittance] = $this->remittance();
        $inactive = $this->reviewer(['is_active' => false]);

        $this->assertFalse(Gate::forUser($inactive)->allows('viewAny', StoreRemittance::class));
        $this->assertFalse(Gate::forUser($inactive)->allows('view', $remittance));
        $this->assertFalse(Gate::forUser($inactive)->allows('review', $remittance));
    }

    public function test_store_staff_can_view_their_own_stores_remissions_only(): void
    {
        [$remittance, $store] = $this->remittance();
        $assistant = $this->assign($store, StoreStaff::ROLE_ASSISTANT);
        $otherStore = $this->makeStore();
        $outsider = $this->assign($otherStore, StoreStaff::ROLE_COORDINATOR);

        $this->assertTrue(Gate::forUser($assistant)->allows('view', $remittance));
        $this->assertFalse(Gate::forUser($outsider)->allows('view', $remittance));
        $this->assertFalse(Gate::forUser($assistant)->allows('viewAny', StoreRemittance::class));
    }

    public function test_active_coordinator_and_assistant_can_submit_for_their_store(): void
    {
        $store = $this->makeStore();
        $coordinator = $this->assign($store, StoreStaff::ROLE_COORDINATOR);
        $assistant = $this->assign($store, StoreStaff::ROLE_ASSISTANT);

        $this->assertTrue(Gate::forUser($coordinator)->allows('submit', [StoreRemittance::class, $store]));
        $this->assertTrue(Gate::forUser($assistant)->allows('submit', [StoreRemittance::class, $store]));
    }

    public function test_submit_is_denied_for_outsiders_inactive_users_and_inactive_stores(): void
    {
        $store = $this->makeStore();
        $coordinator = $this->assign($store, StoreStaff::ROLE_COORDINATOR);
        $outsider = User::factory()->create(['is_active' => true]);
        $inactiveAssistant = $this->assign($store, StoreStaff::ROLE_ASSISTANT, ['is_active' => false]);

        $this->assertFalse(Gate::forUser($outsider)->allows('submit', [StoreRemittance::class, $store]));
        $this->assertFalse(Gate::forUser($inactiveAssistant)->allows('submit', [StoreRemittance::class, $store]));

        $store->forceFill(['is_active' => false])->save();

        $this->assertFalse(Gate::forUser($coordinator)->allows('submit', [StoreRemittance::class, $store->fresh()]));
    }

    public function test_reviewer_can_review_but_not_a_remission_they_submitted(): void
    {
        [$remittance, , $coordinator] = $this->remittance();
        $reviewer = $this->reviewer();
        $submitterWhoReviews = $this->reviewer();
        $remittance->forceFill(['submitted_by' => $submitterWhoReviews->id])->saveQuietly();

        $this->assertTrue(Gate::forUser($reviewer)->allows('review', $remittance->fresh()));
        $this->assertFalse(Gate::forUser($submitterWhoReviews)->allows('review', $remittance->fresh()));
        $this->assertFalse(Gate::forUser($coordinator)->allows('review', $remittance->fresh()));
    }

    // ---------------------------------------------------------------- helpers

    /**
     * @return array{0: StoreRemittance, 1: Store, 2: User}
     */
    private function remittance(): array
    {
        $store = $this->makeStore();
        $coordinator = $this->assign($store, StoreStaff::ROLE_COORDINATOR);

        $day = $store->dailyCashRecords()->create([
            'business_date' => today()->toDateString(),
            'opening_cash' => '1000.00',
            'opening_bank' => '5000.00',
            'closing_cash' => '3000.00',
            'closing_bank' => '7000.00',
            'opened_by' => $coordinator->id,
            'closed_by' => $coordinator->id,
        ]);

        $remittance = StoreRemittance::create([
            'store_id' => $store->id,
            'store_daily_cash_record_id' => $day->id,
            'business_date' => $day->business_date,
            'amount_cash' => '2000.00',
            'amount_bank' => '0.00',
            'method' => StoreRemittance::METHOD_CASH_HANDOVER,
            'status' => StoreRemittance::STATUS_PENDING,
            'submitted_by' => $coordinator->id,
            'submitted_at' => now(),
        ]);

        return [$remittance, $store, $coordinator];
    }

    private function reviewer(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['is_active' => true], $attributes));

        $permission = Permission::query()->firstOrCreate(
            ['slug' => StoreRemittancePolicy::REVIEW_PERMISSION],
            ['name' => StoreRemittancePolicy::REVIEW_PERMISSION]
        );

        $role = Role::factory()->create([
            'name' => 'Remittance Reviewer '.uniqid(),
            'slug' => 'remittance-reviewer-'.uniqid(),
        ]);

        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $user->roles()->attach($role);

        return $user;
    }

    private function makeStore(array $attributes = []): Store
    {
        return Store::query()->create(array_merge([
            'name' => 'Remittance Policy Store '.str()->random(8),
            'code' => strtoupper(fake()->unique()->bothify('RP###??')),
            'is_active' => true,
        ], $attributes));
    }

    private function assign(Store $store, string $role, array $userAttributes = []): User
    {
        $user = User::factory()->create(array_merge(['is_active' => true], $userAttributes));

        StoreStaff::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role,
            'starts_at' => now()->subMinute(),
        ]);

        return $user;
    }
}
