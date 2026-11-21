<?php

namespace Tests\Feature\Stores;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_table_has_store_owned_fields_and_constraints(): void
    {
        $this->assertTrue(Schema::hasTable('customers'));

        $this->assertTrue(Schema::hasColumns('customers', [
            'id', 'store_id', 'customer_code', 'name', 'phone',
            'alternate_phone', 'email', 'address', 'notes',
            'is_active', 'created_at', 'updated_at', 'deleted_at',
        ]));

        $this->assertTrue(Schema::hasColumn('customers', 'store_id'));
    }

    public function test_customer_relationships_are_scoped_to_their_own_store(): void
    {
        $firstStore = $this->makeStore();
        $secondStore = $this->makeStore();

        $firstCustomer = $this->makeCustomer($firstStore, 'CUS-001');
        $secondCustomer = $this->makeCustomer($secondStore, 'CUS-001');

        $this->assertTrue($firstCustomer->store->is($firstStore));
        $this->assertTrue($secondCustomer->store->is($secondStore));

        $this->assertSame(
            [$firstCustomer->id],
            $firstStore->customers()->pluck('id')->all()
        );

        $this->assertSame(
            [$secondCustomer->id],
            $secondStore->customers()->pluck('id')->all()
        );
    }

    public function test_customer_code_must_be_unique_within_a_store_only(): void
    {
        $firstStore = $this->makeStore();
        $secondStore = $this->makeStore();

        $this->makeCustomer($firstStore, 'CUS-001');
        $this->makeCustomer($secondStore, 'CUS-001');

        $this->assertSame(2, Customer::query()
            ->where('customer_code', 'CUS-001')
            ->count());
    }

    public function test_duplicate_customer_code_in_the_same_store_is_rejected(): void
    {
        $store = $this->makeStore();

        $this->makeCustomer($store, 'CUS-001');

        $this->expectException(QueryException::class);

        $this->makeCustomer($store, 'CUS-001');
    }

    public function test_customer_can_be_soft_deleted_without_being_physically_removed(): void
    {
        $customer = $this->makeCustomer($this->makeStore(), 'CUS-001');

        $customer->delete();

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'store_id' => $customer->store_id,
        ]);
        $this->assertNull(Customer::query()->find($customer->id));
        $this->assertNotNull(Customer::withTrashed()->find($customer->id));
    }

    private function makeStore(): Store
    {
        return Store::query()->create([
            'name' => 'Store '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
        ]);
    }

    private function makeCustomer(Store $store, string $code): Customer
    {
        return Customer::query()->create([
            'store_id' => $store->id,
            'customer_code' => $code,
            'name' => 'Customer '.Str::random(6),
            'phone' => '08012345678',
        ]);
    }
}
