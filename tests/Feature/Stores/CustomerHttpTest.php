<?php

namespace Tests\Feature\Stores;

use App\Models\Customer;
use App\Models\StockMovement;
use App\Models\StockSale;
use App\Models\StockSalePayment;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerHttpTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(): User
    {
        return User::factory()->create(['is_active' => true]);
    }

    private function makeStore(array $attributes = []): Store
    {
        return Store::query()->create(array_merge([
            'name' => 'Store '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
        ], $attributes));
    }

    private function assign(Store $store, User $user, string $role): StoreStaff
    {
        return StoreStaff::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role,
            'starts_at' => now(),
        ]);
    }

    public function test_coordinator_can_register_customer_without_recording_a_sale(): void
    {
        $store = $this->makeStore();
        $user = $this->staffUser();

        $this->assign($store, $user, StoreStaff::ROLE_COORDINATOR);

        $response = $this->actingAs($user)->post(
            route('stores.customers.store', $store),
            [
                'name' => 'Ada Customer',
                'phone' => '08012345678',
                'email' => 'ada@example.com',
            ]
        );

        $response->assertRedirect(route('stores.customers.index', $store));

        $customer = Customer::query()
            ->where('store_id', $store->id)
            ->where('phone', '08012345678')
            ->firstOrFail();

        $this->assertSame('Ada Customer', $customer->name);
        $this->assertSame('ada@example.com', $customer->email);
        $this->assertTrue($customer->is_active);
        $this->assertStringStartsWith('CUS-', $customer->customer_code);

        $this->assertSame(0, StockSale::query()->count());
        $this->assertSame(0, StockSalePayment::query()->count());
        $this->assertSame(0, StockMovement::query()->count());
    }

    public function test_assistant_can_register_customer_for_assigned_store(): void
    {
        $store = $this->makeStore();
        $user = $this->staffUser();

        $this->assign($store, $user, StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($user)
            ->post(route('stores.customers.store', $store), [
                'name' => 'Assistant Customer',
                'phone' => '08022223333',
            ])
            ->assertRedirect(route('stores.customers.index', $store));

        $this->assertDatabaseHas('customers', [
            'store_id' => $store->id,
            'name' => 'Assistant Customer',
            'phone' => '08022223333',
        ]);
    }

    public function test_customer_list_is_limited_to_the_assigned_store(): void
    {
        $store = $this->makeStore();
        $otherStore = $this->makeStore();
        $user = $this->staffUser();

        $this->assign($store, $user, StoreStaff::ROLE_COORDINATOR);

        Customer::query()->create([
            'store_id' => $store->id,
            'customer_code' => 'CUS-LOCAL001',
            'name' => 'Local Store Customer',
            'phone' => '08011111111',
            'is_active' => true,
        ]);

        Customer::query()->create([
            'store_id' => $otherStore->id,
            'customer_code' => 'CUS-OTHER001',
            'name' => 'Other Store Customer',
            'phone' => '08099999999',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('stores.customers.index', $store))
            ->assertOk()
            ->assertSee('Local Store Customer')
            ->assertDontSee('Other Store Customer');
    }

    public function test_unassigned_user_cannot_access_customer_management(): void
    {
        $store = $this->makeStore();
        $user = $this->staffUser();

        $this->actingAs($user)
            ->get(route('stores.customers.index', $store))
            ->assertForbidden();
    }

    public function test_user_assigned_to_another_store_cannot_register_customer_here(): void
    {
        $store = $this->makeStore();
        $assignedStore = $this->makeStore();
        $user = $this->staffUser();

        $this->assign($assignedStore, $user, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($user)
            ->post(route('stores.customers.store', $store), [
                'name' => 'Wrong Store Customer',
                'phone' => '08033334444',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('customers', [
            'store_id' => $store->id,
            'phone' => '08033334444',
        ]);
    }

    public function test_inactive_store_cannot_be_used_for_customer_management(): void
    {
        $store = $this->makeStore(['is_active' => false]);
        $user = $this->staffUser();

        $this->assign($store, $user, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($user)
            ->get(route('stores.customers.index', $store))
            ->assertForbidden();
    }

    public function test_customer_name_and_phone_are_required(): void
    {
        $store = $this->makeStore();
        $user = $this->staffUser();

        $this->assign($store, $user, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($user)
            ->from(route('stores.customers.create', $store))
            ->post(route('stores.customers.store', $store), [
                'email' => 'valid@example.com',
            ])
            ->assertSessionHasErrors(['name', 'phone']);

        $this->assertSame(0, Customer::query()->count());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $store = $this->makeStore();

        $this->get(route('stores.customers.index', $store))
            ->assertRedirect(route('login'));
    }
}
