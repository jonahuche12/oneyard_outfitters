<?php

namespace Tests\Feature\Stock;

use App\Models\Customer;
use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\StockSale;
use App\Models\StockSalePayment;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreSalesCreateHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_assistant_can_open_sale_form_with_only_local_available_stock_and_customers(): void
    {
        $store = $this->makeStore();
        $otherStore = $this->makeStore();
        $assistant = User::factory()->create(['is_active' => true]);
        $this->assign($store, $assistant, StoreStaff::ROLE_ASSISTANT);

        $localItem = $this->makeItem($store, $assistant, 'Local available fabric');
        $this->makeLevel($store, $localItem, '5.000');

        $zeroItem = $this->makeItem($store, $assistant, 'Zero balance fabric');
        $this->makeLevel($store, $zeroItem, '0.000');

        $foreignItem = $this->makeItem($otherStore, $assistant, 'Foreign store fabric');
        $this->makeLevel($otherStore, $foreignItem, '8.000');

        $localCustomer = $this->makeCustomer($store, 'Local customer');
        $this->makeCustomer($otherStore, 'Foreign customer');

        $this->actingAs($assistant)
            ->get(route('stores.sales.create', $store))
            ->assertOk()
            ->assertSee('Local available fabric')
            ->assertSee($localCustomer->name)
            ->assertDontSee('Zero balance fabric')
            ->assertDontSee('Foreign store fabric')
            ->assertDontSee('Foreign customer');
    }

    public function test_coordinator_can_record_part_paid_sale_through_http(): void
    {
        $store = $this->makeStore();
        $coordinator = User::factory()->create(['is_active' => true]);
        $this->assign($store, $coordinator, StoreStaff::ROLE_COORDINATOR);

        $item = $this->makeItem($store, $coordinator, 'HTTP sale fabric');
        $level = $this->makeLevel($store, $item, '5.000');
        $customer = $this->makeCustomer($store, 'HTTP sale customer');

        $movementCount = StockMovement::query()->count();

        $response = $this->actingAs($coordinator)->post(
            route('stores.sales.store', $store),
            [
                'customer_id' => $customer->id,
                'items' => [
                    [
                        'stock_item_id' => $item->id,
                        'quantity' => '2',
                        'unit_selling_price' => '4000.00',
                    ],
                ],
                'initial_payment_amount' => '8000.00',
                'initial_payment_method' => 'cash',
            ]
        );

        $response->assertRedirect(route('stores.sales.index', $store));

        $sale = StockSale::query()
            ->where('store_id', $store->id)
            ->where('created_by', $coordinator->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('8000.00', $sale->total_amount);
        $this->assertSame('3.000', $level->fresh()->quantity);
        $this->assertDatabaseHas('stock_sale_items', [
            'stock_sale_id' => $sale->id,
            'stock_item_id' => $item->id,
            'quantity' => '2.000',
            'line_total' => '8000.00',
        ]);
        $this->assertDatabaseHas('stock_sale_payments', [
            'stock_sale_id' => $sale->id,
            'amount' => '8000.00',
            'received_by' => $coordinator->id,
        ]);
        $this->assertSame($movementCount + 1, StockMovement::query()->count());
    }

    public function test_sale_form_is_forbidden_to_user_without_store_assignment(): void
    {
        $store = $this->makeStore();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('stores.sales.create', $store))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('stores.sales.store', $store), [])
            ->assertForbidden();

        $this->assertSame(0, StockSale::query()->count());
    }

    public function test_guest_is_redirected_from_sale_creation_form(): void
    {
        $store = $this->makeStore();

        $this->get(route('stores.sales.create', $store))
            ->assertRedirect();
    }

    public function test_excess_quantity_is_rejected_without_creating_sale_or_deducting_stock(): void
    {
        $store = $this->makeStore();
        $coordinator = User::factory()->create(['is_active' => true]);
        $this->assign($store, $coordinator, StoreStaff::ROLE_COORDINATOR);

        $item = $this->makeItem($store, $coordinator, 'Insufficient HTTP stock');
        $level = $this->makeLevel($store, $item, '2.000');
        $customer = $this->makeCustomer($store, 'Insufficient stock customer');

        $this->actingAs($coordinator)
            ->from(route('stores.sales.create', $store))
            ->post(route('stores.sales.store', $store), [
                'customer_id' => $customer->id,
                'items' => [
                    [
                        'stock_item_id' => $item->id,
                        'quantity' => '3',
                        'unit_selling_price' => '100.00',
                    ],
                ],
            ])
            ->assertSessionHasErrors();

        $this->assertSame(0, StockSale::query()->count());
        $this->assertSame('2.000', $level->fresh()->quantity);
        $this->assertSame(0, StockSalePayment::query()->count());
    }

    private function makeStore(): Store
    {
        return Store::query()->create([
            'name' => 'Store '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
            'type' => Store::TYPE_STORE,
            'is_active' => true,
        ]);
    }

    private function assign(Store $store, User $user, string $role): StoreStaff
    {
        return StoreStaff::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role,
            'starts_at' => now(),
            'assigned_by' => $user->id,
        ]);
    }

    private function makeItem(Store $store, User $creator, string $description): StockItem
    {
        return StockItem::query()->create([
            'store_id' => $store->id,
            'description' => $description,
            'category' => 'fabric',
            'unit' => StockItem::UNIT_YARD,
            'is_raw' => false,
            'approval_status' => StockItem::APPROVAL_APPROVED,
            'processing_status' => StockItem::PROCESSING_PENDING,
            'created_by' => $creator->id,
        ]);
    }

    private function makeLevel(Store $store, StockItem $item, string $quantity): StockLevel
    {
        return StockLevel::query()->create([
            'store_id' => $store->id,
            'stock_item_id' => $item->id,
            'quantity' => $quantity,
            'quantity_quarantined' => '0.000',
            'average_unit_cost' => '1000.000000',
            'inventory_value' => '5000.00',
            'quarantine_value' => '0.00',
        ]);
    }

    private function makeCustomer(Store $store, string $name): Customer
    {
        return Customer::query()->create([
            'store_id' => $store->id,
            'customer_code' => 'CUS-'.strtoupper(Str::random(8)),
            'name' => $name,
            'phone' => '08012345678',
            'is_active' => true,
        ]);
    }
}
