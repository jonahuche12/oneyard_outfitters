<?php

namespace Tests\Feature\Stock;

use App\Actions\Stock\RecordStockProcessing;
use App\Models\Role;
use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\StockProcessing;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockProcessingHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_unassigned_administrator_cannot_record_processing_for_a_selected_store(): void
    {
        $admin = $this->administrator();
        $store = $this->makeStore();

        $this->actingAs($admin)
            ->get(route('stock.processings.create', ['store_id' => $store->id]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('stock.processings.store'), [
                'store_id' => $store->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('stock_processings', 0);
    }

    public function test_coordinator_can_record_processing_without_submitting_a_store_id(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $raw = $this->makeItem($store, $coordinator, 'Coordinator raw', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);
        $fabric = $this->makeItem($store, $coordinator, 'Coordinator fabric');
        $this->makeLevel($store, $raw, '5.000', '50.000000', '250.00');

        $this->actingAs($coordinator)
            ->get(route('stock.processings.create'))
            ->assertOk()
            ->assertSee('Coordinator raw')
            ->assertSee('Coordinator fabric');

        $response = $this->post(route('stock.processings.store'), [
            'raw_stock_item_id' => $raw->id,
            'quantity_consumed' => '1.000',
            'recoverable_waste_kg' => '0.000',
            'discarded_waste_kg' => '0.000',
            'processed_on' => '2026-11-17',
            'outputs' => [[
                'stock_item_id' => $fabric->id,
                'quantity_produced' => '3.000',
                'line_cost' => '50.00',
            ]],
        ]);

        $processing = StockProcessing::query()->firstOrFail();

        $response->assertRedirect(route('stock.processings.show', $processing));
        $this->assertSame($store->id, $processing->store_id);
        $this->assertSame($coordinator->id, $processing->processed_by);
    }

    public function test_processing_form_only_lists_eligible_items_from_the_selected_store(): void
    {
        $admin = $this->administrator();
        $store = $this->makeStore();
        $otherStore = $this->makeStore();
        $this->assign($admin, $store, StoreStaff::ROLE_COORDINATOR);

        $eligibleRaw = $this->makeItem($store, $admin, 'Eligible raw', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);
        $this->makeLevel($store, $eligibleRaw, '2.000', '10.000000', '20.00');

        $zeroRaw = $this->makeItem($store, $admin, 'Zero balance raw', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);
        $this->makeLevel($store, $zeroRaw, '0.000', '10.000000', '0.00');

        $processedRaw = $this->makeItem($store, $admin, 'Already processed raw', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
            'processing_status' => StockItem::PROCESSING_PROCESSED,
        ]);
        $this->makeLevel($store, $processedRaw, '2.000', '10.000000', '20.00');

        $pendingRaw = $this->makeItem($store, $admin, 'Pending approval raw', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
            'approval_status' => StockItem::APPROVAL_PENDING,
        ]);
        $this->makeLevel($store, $pendingRaw, '2.000', '10.000000', '20.00');

        $foreignRaw = $this->makeItem($otherStore, $admin, 'Foreign raw', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);
        $this->makeLevel($otherStore, $foreignRaw, '2.000', '10.000000', '20.00');

        $this->makeItem($store, $admin, 'Eligible fabric');
        $this->makeItem($store, $admin, 'Eligible reusable waste', [
            'unit' => StockItem::UNIT_KG,
            'category' => 'reusable-waste',
        ]);
        $this->makeItem($otherStore, $admin, 'Foreign fabric');
        $this->makeItem($store, $admin, 'Ordinary kilogram item', [
            'unit' => StockItem::UNIT_KG,
            'category' => 'fabric',
        ]);

        $this->actingAs($admin)
            ->get(route('stock.processings.create', ['store_id' => $store->id]))
            ->assertOk()
            ->assertSee('Eligible raw')
            ->assertSee('Eligible fabric')
            ->assertSee('Eligible reusable waste')
            ->assertDontSee('Zero balance raw')
            ->assertDontSee('Already processed raw')
            ->assertSee('Pending approval raw')
            ->assertDontSee('Foreign raw')
            ->assertDontSee('Foreign fabric')
            ->assertDontSee('Ordinary kilogram item');
    }

    public function test_assistant_can_process_stock_but_unassigned_user_cannot(): void
    {
        $store = $this->makeStore();

        $assistant = User::factory()->create(['is_active' => true]);
        $this->assign($assistant, $store, StoreStaff::ROLE_ASSISTANT);

        $raw = $this->makeItem($store, $assistant, 'Assistant raw', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);
        $fabric = $this->makeItem($store, $assistant, 'Assistant fabric');
        $this->makeLevel($store, $raw, '5.000', '50.000000', '250.00');

        $this->actingAs($assistant)
            ->get(route('stock.processings.create'))
            ->assertOk()
            ->assertSee('Assistant raw')
            ->assertSee('Assistant fabric');

        $this->actingAs($assistant)
            ->post(route('stock.processings.store'), [
                'raw_stock_item_id' => $raw->id,
                'quantity_consumed' => '1.000',
                'recoverable_waste_kg' => '0.000',
                'discarded_waste_kg' => '0.000',
                'processed_on' => '2026-11-17',
                'outputs' => [[
                    'stock_item_id' => $fabric->id,
                    'quantity_produced' => '3.000',
                    'line_cost' => '50.00',
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('stock_processings', [
            'store_id' => $store->id,
            'processed_by' => $assistant->id,
        ]);

        $unassigned = User::factory()->create(['is_active' => true]);

        $this->actingAs($unassigned)
            ->get(route('stock.processings.create'))
            ->assertForbidden();

        $this->actingAs($unassigned)
            ->post(route('stock.processings.store'), [])
            ->assertForbidden();

        $this->assertDatabaseCount('stock_processings', 1);
    }

    public function test_receipt_is_visible_to_same_store_staff_but_not_other_store_staff(): void
    {
        $admin = $this->administrator();
        $store = $this->makeStore();
        $otherStore = $this->makeStore();
        $this->assign($admin, $store, StoreStaff::ROLE_COORDINATOR);

        $raw = $this->makeItem($store, $admin, 'Receipt raw', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);
        $fabric = $this->makeItem($store, $admin, 'Receipt fabric');
        $this->makeLevel($store, $raw, '5.000', '50.000000', '250.00');

        $processing = app(RecordStockProcessing::class)->execute(
            $admin,
            $store,
            $this->payload($raw, $fabric)
        );

        $sameStoreAssistant = User::factory()->create(['is_active' => true]);
        $this->assign($sameStoreAssistant, $store, StoreStaff::ROLE_ASSISTANT);

        $otherStoreCoordinator = User::factory()->create(['is_active' => true]);
        $this->assign($otherStoreCoordinator, $otherStore, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($sameStoreAssistant)
            ->get(route('stock.processings.show', $processing))
            ->assertOk()
            ->assertSee($processing->number)
            ->assertSee('Receipt raw')
            ->assertSee('Receipt fabric');

        $this->actingAs($otherStoreCoordinator)
            ->get(route('stock.processings.show', $processing))
            ->assertForbidden();
    }

    private function administrator(): User
    {
        $user = User::factory()->create(['is_active' => true]);

        $role = Role::query()->create([
            'name' => 'Administrator '.Str::random(6),
            'slug' => 'admin',
            'description' => 'Test administrator',
        ]);

        $user->roles()->attach($role);

        return $user;
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

    private function makeItem(
        Store $store,
        User $creator,
        string $description,
        array $overrides = []
    ): StockItem {
        return StockItem::query()->create(array_merge([
            'store_id' => $store->id,
            'description' => $description,
            'category' => 'fabric',
            'unit' => StockItem::UNIT_YARD,
            'is_raw' => false,
            'approval_status' => StockItem::APPROVAL_APPROVED,
            'processing_status' => StockItem::PROCESSING_PENDING,
            'created_by' => $creator->id,
        ], $overrides));
    }

    private function makeLevel(
        Store $store,
        StockItem $item,
        string $quantity,
        string $average,
        string $value
    ): StockLevel {
        return StockLevel::query()->create([
            'store_id' => $store->id,
            'stock_item_id' => $item->id,
            'quantity' => $quantity,
            'quantity_quarantined' => '0.000',
            'average_unit_cost' => $average,
            'inventory_value' => $value,
            'quarantine_value' => '0.00',
        ]);
    }

    private function level(Store $store, StockItem $item): StockLevel
    {
        return StockLevel::query()
            ->where('store_id', $store->id)
            ->where('stock_item_id', $item->id)
            ->firstOrFail();
    }

    private function payload(StockItem $raw, StockItem $fabric): array
    {
        return [
            'raw_stock_item_id' => $raw->id,
            'quantity_consumed' => '2.000',
            'recoverable_waste_kg' => '0.000',
            'discarded_waste_kg' => '0.000',
            'processed_on' => '2026-11-17',
            'notes' => 'Receipt access test',
            'outputs' => [[
                'stock_item_id' => $fabric->id,
                'quantity_produced' => '10.000',
                'line_cost' => '200.00',
            ]],
        ];
    }

    private function assign(User $user, Store $store, string $role): void
    {
        StoreStaff::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role,
            'starts_at' => now(),
            'assigned_by' => $user->id,
        ]);
    }
}
