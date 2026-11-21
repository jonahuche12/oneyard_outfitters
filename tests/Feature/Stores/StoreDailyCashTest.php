<?php

namespace Tests\Feature\Stores;

use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreDailyCashTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_coordinator_can_open_the_business_day_once(): void
    {
        $store = $this->makeStore();
        $coordinator = $this->assign($store, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($coordinator)
            ->post(route('stores.daily-cash.open', $store), [
                'opening_cash' => '1250.50',
                'opening_bank' => '9000.00',
            ])
            ->assertRedirect(route('stores.show', $store))
            ->assertSessionHas('success');

        $record = StoreDailyCashRecord::query()->sole();

        $this->assertSame($store->id, $record->store_id);
        $this->assertSame(today()->toDateString(), $record->business_date->toDateString());
        $this->assertSame('1250.50', $record->opening_cash);
        $this->assertSame('9000.00', $record->opening_bank);
        $this->assertSame($coordinator->id, $record->opened_by);
        $this->assertNull($record->closed_by);

        $this->actingAs($coordinator)
            ->post(route('stores.daily-cash.open', $store), [
                'opening_cash' => '100',
                'opening_bank' => '200',
            ])
            ->assertSessionHasErrors('opening_cash');

        $this->assertSame(1, StoreDailyCashRecord::query()->count());
    }

    public function test_assigned_assistant_can_close_an_open_day_and_the_record_is_locked(): void
    {
        $store = $this->makeStore();
        $coordinator = $this->assign($store, StoreStaff::ROLE_COORDINATOR);
        $assistant = $this->assign($store, StoreStaff::ROLE_ASSISTANT);

        $record = $store->dailyCashRecords()->create([
            'business_date' => today()->toDateString(),
            'opening_cash' => '1000.00',
            'opening_bank' => '5000.00',
            'opened_by' => $coordinator->id,
        ]);

        $this->actingAs($assistant)
            ->post(route('stores.daily-cash.close', $store), [
                'closing_cash' => '850.00',
                'closing_bank' => '5200.00',
            ])
            ->assertRedirect(route('stores.show', $store))
            ->assertSessionHas('success');

        $record->refresh();

        $this->assertSame('850.00', $record->closing_cash);
        $this->assertSame('5200.00', $record->closing_bank);
        $this->assertSame($assistant->id, $record->closed_by);

        $this->actingAs($assistant)
            ->post(route('stores.daily-cash.close', $store), [
                'closing_cash' => '1',
                'closing_bank' => '1',
            ])
            ->assertSessionHasErrors('closing_cash');

        $this->actingAs($assistant)
            ->post(route('stores.daily-cash.open', $store), [
                'opening_cash' => '1',
                'opening_bank' => '1',
            ])
            ->assertSessionHasErrors('opening_cash');

        $this->assertSame('850.00', $record->fresh()->closing_cash);
    }

    public function test_day_cannot_be_closed_before_opening(): void
    {
        $store = $this->makeStore();
        $assistant = $this->assign($store, StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($assistant)
            ->post(route('stores.daily-cash.close', $store), [
                'closing_cash' => '100',
                'closing_bank' => '200',
            ])
            ->assertSessionHasErrors('closing_cash');

        $this->assertSame(0, StoreDailyCashRecord::query()->count());
    }

    public function test_users_not_assigned_to_the_store_cannot_open_or_close_its_day(): void
    {
        $store = $this->makeStore();
        $outsider = User::factory()->create(['is_active' => true]);

        $this->actingAs($outsider)
            ->post(route('stores.daily-cash.open', $store), [
                'opening_cash' => '100',
                'opening_bank' => '200',
            ])
            ->assertForbidden();

        $this->assertSame(0, StoreDailyCashRecord::query()->count());
    }

    public function test_inactive_store_or_inactive_user_cannot_record_balances(): void
    {
        $store = $this->makeStore(['is_active' => false]);
        $user = $this->assign($store, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($user)
            ->post(route('stores.daily-cash.open', $store), [
                'opening_cash' => '100',
                'opening_bank' => '200',
            ])
            ->assertForbidden();

        $activeStore = $this->makeStore();
        $inactiveUser = $this->assign($activeStore, StoreStaff::ROLE_ASSISTANT, [
            'is_active' => false,
        ]);

        $this->actingAs($inactiveUser)
            ->post(route('stores.daily-cash.open', $activeStore), [
                'opening_cash' => '100',
                'opening_bank' => '200',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('email', 'Your account is inactive. Please contact an administrator.');

        $this->assertSame(0, StoreDailyCashRecord::query()->count());
    }

    public function test_store_page_shows_daily_history_and_controls_to_assigned_staff(): void
    {
        $store = $this->makeStore();
        $coordinator = $this->assign($store, StoreStaff::ROLE_COORDINATOR);

        $store->dailyCashRecords()->create([
            'business_date' => today()->toDateString(),
            'opening_cash' => '1250.50',
            'opening_bank' => '9000.00',
            'opened_by' => $coordinator->id,
        ]);

        $this->actingAs($coordinator)
            ->get(route('stores.show', $store))
            ->assertOk()
            ->assertSee('Daily cash and bank balances')
            ->assertSee('Daily history')
            ->assertSee('1,250.50')
            ->assertSee('Opening cash:')
            ->assertSee('Close business day');
    }

    private function makeStore(array $attributes = []): Store
    {
        return Store::query()->create(array_merge([
            'name' => 'Test Store '.str()->random(8),
            'code' => strtoupper(fake()->unique()->bothify('ST###??')),
            'is_active' => true,
        ], $attributes));
    }

    private function assign(Store $store, string $role, array $userAttributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'is_active' => true,
        ], $userAttributes));

        StoreStaff::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role,
            'starts_at' => now()->subMinute(),
        ]);

        return $user;
    }
}
