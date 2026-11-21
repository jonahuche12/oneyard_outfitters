<?php

namespace Tests\Feature\Stock;

use App\Actions\Stock\DispatchStockConsignment;
use App\Actions\Stock\AcknowledgeStockConsignment;
use App\Models\Role;
use App\Models\StockConsignment;
use App\Models\StockConsignmentDiscrepancy;
use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockConsignmentHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_unassigned_administrator_cannot_dispatch_stock(): void
    {
        $admin = $this->administrator();
        $source = $this->makeStore();
        $destination = $this->makeStore();

        $item = $this->makeItem($source, $admin, 'Dispatch cotton');
        $this->makeLevel($source, $item, '12.000', '100.000000', '1200.00');

        $this->actingAs($admin)
            ->get(route('stock.consignments.create', ['source_store_id' => $source->id]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('stock.consignments.store'), [
                'destination_store_id' => $destination->id,
                'items' => [[
                    'stock_item_id' => $item->id,
                    'quantity' => '3.000',
                ]],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('stock_consignments', 0);
    }

    public function test_source_coordinator_can_dispatch_without_submitting_source_store_id(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $source = $this->makeStore();
        $destination = $this->makeStore();
        $this->assign($coordinator, $source, StoreStaff::ROLE_COORDINATOR);

        $item = $this->makeItem($source, $coordinator, 'Coordinator stock');
        $this->makeLevel($source, $item, '5.000', '20.000000', '100.00');

        $this->actingAs($coordinator)
            ->get(route('stock.consignments.create'))
            ->assertOk()
            ->assertSee('Coordinator stock');

        $response = $this->post(route('stock.consignments.store'), [
            'destination_store_id' => $destination->id,
            'items' => [[
                'stock_item_id' => $item->id,
                'quantity' => '2.000',
            ]],
        ]);

        $consignment = StockConsignment::query()->firstOrFail();

        $response->assertRedirect(route('stock.consignments.show', $consignment));
        $this->assertSame($source->id, $consignment->source_store_id);
        $this->assertSame($coordinator->id, $consignment->dispatched_by);
    }

    public function test_store_can_dispatch_acknowledged_stock_originating_from_another_store(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $source = $this->makeStore();
        $origin = $this->makeStore();
        $destination = $this->makeStore();

        $this->assign($coordinator, $source, StoreStaff::ROLE_COORDINATOR);

        $item = $this->makeItem($origin, $coordinator, 'Received stock from another store');
        $this->makeLevel($origin, $item, '5.000', '20.000000', '100.00');
        $sourceLevel = $this->makeLevel($source, $item, '3.000', '20.000000', '60.00');

        $this->actingAs($coordinator)
            ->get(route('stock.consignments.create'))
            ->assertOk()
            ->assertSee('Received stock from another store');

        $response = $this->actingAs($coordinator)
            ->post(route('stock.consignments.store'), [
                'destination_store_id' => $destination->id,
                'items' => [[
                    'stock_item_id' => $item->id,
                    'quantity' => '2.000',
                ]],
            ]);

        $consignment = StockConsignment::query()->firstOrFail();

        $response->assertRedirect(route('stock.consignments.show', $consignment));
        $this->assertSame($origin->id, $item->fresh()->store_id);
        $this->assertSame('1.000', $sourceLevel->fresh()->quantity);
        $this->assertSame(
            '2.000',
            StockLevel::query()
                ->where('store_id', $destination->id)
                ->where('stock_item_id', $item->id)
                ->value('quantity_quarantined')
        );
    }

    public function test_dispatch_rejects_quantity_greater_than_source_available_stock(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $source = $this->makeStore();
        $destination = $this->makeStore();

        $this->assign($coordinator, $source, StoreStaff::ROLE_COORDINATOR);

        $item = $this->makeItem($source, $coordinator, 'Limited available stock');
        $level = $this->makeLevel($source, $item, '5.000', '20.000000', '100.00');

        $this->actingAs($coordinator)
            ->post(route('stock.consignments.store'), [
                'destination_store_id' => $destination->id,
                'items' => [[
                    'stock_item_id' => $item->id,
                    'quantity' => '6.000',
                ]],
            ])
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('stock_consignments', 0);
        $this->assertSame('5.000', $level->fresh()->quantity);
        $this->assertSame('0.000', $level->fresh()->quantity_quarantined);
    }

    public function test_dispatch_form_lists_available_stock_from_source_regardless_of_legacy_approval_status(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $source = $this->makeStore();
        $other = $this->makeStore();
        $this->assign($coordinator, $source, StoreStaff::ROLE_COORDINATOR);

        $eligible = $this->makeItem($source, $coordinator, 'Eligible stock');
        $this->makeLevel($source, $eligible, '4.000', '10.000000', '40.00');

        $zero = $this->makeItem($source, $coordinator, 'Zero balance stock');
        $this->makeLevel($source, $zero, '0.000', '10.000000', '0.00');

        $pending = $this->makeItem($source, $coordinator, 'Pending stock', [
            'approval_status' => StockItem::APPROVAL_PENDING,
        ]);
        $this->makeLevel($source, $pending, '4.000', '10.000000', '40.00');

        $foreign = $this->makeItem($other, $coordinator, 'Foreign stock');
        $this->makeLevel($other, $foreign, '4.000', '10.000000', '40.00');
        $this->makeLevel($source, $foreign, '2.000', '10.000000', '20.00');

        $this->actingAs($coordinator)
            ->get(route('stock.consignments.create'))
            ->assertOk()
            ->assertSee('Eligible stock')
            ->assertDontSee('Zero balance stock')
            ->assertSee('Pending stock')
            ->assertSee('Foreign stock');
    }

    public function test_assistant_can_dispatch_but_unassigned_user_cannot(): void
    {
        $source = $this->makeStore();
        $destination = $this->makeStore();

        $assistant = User::factory()->create(['is_active' => true]);
        $this->assign($assistant, $source, StoreStaff::ROLE_ASSISTANT);

        $item = $this->makeItem($source, $assistant, 'Assistant dispatch stock');
        $this->makeLevel($source, $item, '5.000', '20.000000', '100.00');

        $this->actingAs($assistant)
            ->get(route('stock.consignments.create'))
            ->assertOk()
            ->assertSee('Assistant dispatch stock');

        $this->actingAs($assistant)
            ->post(route('stock.consignments.store'), [
                'destination_store_id' => $destination->id,
                'items' => [[
                    'stock_item_id' => $item->id,
                    'quantity' => '2.000',
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('stock_consignments', 1);

        $unassigned = User::factory()->create(['is_active' => true]);

        $this->actingAs($unassigned)
            ->get(route('stock.consignments.create'))
            ->assertForbidden();

        $this->actingAs($unassigned)
            ->post(route('stock.consignments.store'), [])
            ->assertForbidden();

        $this->assertDatabaseCount('stock_consignments', 1);
    }

    public function test_receipt_is_visible_to_staff_of_either_store_but_not_an_outsider(): void
    {
        $admin = $this->administrator();
        $source = $this->makeStore();
        $destination = $this->makeStore();
        $outsiderStore = $this->makeStore();
        $this->assign($admin, $source, StoreStaff::ROLE_COORDINATOR);

        $item = $this->makeItem($source, $admin, 'Receipt stock');
        $this->makeLevel($source, $item, '5.000', '10.000000', '50.00');

        $consignment = app(DispatchStockConsignment::class)->execute(
            $admin,
            $source,
            $destination,
            [
                'items' => [[
                    'stock_item_id' => $item->id,
                    'quantity' => '2.000',
                ]],
            ]
        );

        $destinationAssistant = User::factory()->create(['is_active' => true]);
        $this->assign($destinationAssistant, $destination, StoreStaff::ROLE_ASSISTANT);

        $outsider = User::factory()->create(['is_active' => true]);
        $this->assign($outsider, $outsiderStore, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($destinationAssistant)
            ->get(route('stock.consignments.show', $consignment))
            ->assertOk()
            ->assertSee($consignment->reference)
            ->assertSee('Receipt stock');

        $this->actingAs($outsider)
            ->get(route('stock.consignments.show', $consignment))
            ->assertForbidden();
    }

    public function test_source_coordinator_can_confirm_damaged_consignment_discrepancy(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $source = $this->makeStore();
        $destination = $this->makeStore();
        $this->assign($coordinator, $source, StoreStaff::ROLE_COORDINATOR);

        $item = $this->makeItem($source, $coordinator, 'Discrepancy confirmation stock');
        $this->makeLevel($source, $item, '10.000', '10.000000', '100.00');

        $consignment = app(DispatchStockConsignment::class)->execute(
            $coordinator,
            $source,
            $destination,
            ['items' => [[
                'stock_item_id' => $item->id,
                'quantity' => '5.000',
            ]]]
        );

        $destinationCoordinator = User::factory()->create(['is_active' => true]);
        $this->assign($destinationCoordinator, $destination, StoreStaff::ROLE_COORDINATOR);

        app(AcknowledgeStockConsignment::class)->execute(
            $destinationCoordinator,
            $consignment,
            ['items' => [[
                'stock_consignment_item_id' => $consignment->items()->firstOrFail()->id,
                'quantity_good' => '2.000',
                'quantity_damaged' => '1.000',
                'quantity_shortage' => '1.000',
            ]]]
        );

        $discrepancy = StockConsignmentDiscrepancy::query()
            ->where('stock_consignment_item_id', $consignment->items()->firstOrFail()->id)
            ->where('type', 'damaged')
            ->firstOrFail();

        $response = $this->actingAs($coordinator)->post(
            route('stock.consignments.discrepancies.confirm', [
                'consignment' => $consignment,
                'discrepancy' => $discrepancy,
            ]),
            ['resolution_reason' => 'Damaged stock inspected and approved for write-off.']
        );

        $response->assertRedirect(route('stock.consignments.show', $consignment));

        $this->assertDatabaseHas('stock_consignment_discrepancies', [
            'id' => $discrepancy->id,
            'status' => 'resolved',
            'resolution' => 'written_off',
            'resolved_by' => $coordinator->id,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $item->id,
            'store_id' => $destination->id,
            'type' => \App\Models\StockMovement::TYPE_CONSIGNMENT_WRITE_OFF,
            'source_type' => \App\Models\StockConsignmentDiscrepancy::class,
            'source_id' => $discrepancy->id,
            'created_by' => $coordinator->id,
        ]);

        $this->assertSame(
            '1.000',
            $this->level($destination, $item)->fresh()->quantity_quarantined
        );
    }

    public function test_only_source_coordinator_can_confirm_consignment_discrepancy_over_http(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $source = $this->makeStore();
        $destination = $this->makeStore();
        $this->assign($coordinator, $source, StoreStaff::ROLE_COORDINATOR);

        $item = $this->makeItem($source, $coordinator, 'Restricted discrepancy stock');
        $this->makeLevel($source, $item, '10.000', '10.000000', '100.00');

        $consignment = app(DispatchStockConsignment::class)->execute(
            $coordinator,
            $source,
            $destination,
            ['items' => [[
                'stock_item_id' => $item->id,
                'quantity' => '5.000',
            ]]]
        );

        $destinationCoordinator = User::factory()->create(['is_active' => true]);
        $this->assign($destinationCoordinator, $destination, StoreStaff::ROLE_COORDINATOR);

        app(AcknowledgeStockConsignment::class)->execute(
            $destinationCoordinator,
            $consignment,
            ['items' => [[
                'stock_consignment_item_id' => $consignment->items()->firstOrFail()->id,
                'quantity_good' => '2.000',
                'quantity_damaged' => '1.000',
                'quantity_shortage' => '1.000',
            ]]]
        );

        $discrepancy = StockConsignmentDiscrepancy::query()
            ->where('stock_consignment_item_id', $consignment->items()->firstOrFail()->id)
            ->where('type', 'damaged')
            ->firstOrFail();

        $sourceAssistant = User::factory()->create(['is_active' => true]);
        $this->assign($sourceAssistant, $source, StoreStaff::ROLE_ASSISTANT);

        $unassignedAdministrator = $this->administrator();

        foreach ([$sourceAssistant, $destinationCoordinator, $unassignedAdministrator] as $actor) {
            $this->actingAs($actor)
                ->post(
                    route('stock.consignments.discrepancies.confirm', [
                        'consignment' => $consignment,
                        'discrepancy' => $discrepancy,
                    ]),
                    ['resolution_reason' => 'Unauthorized confirmation attempt.']
                )
                ->assertForbidden();
        }

        $this->assertSame('open', $discrepancy->fresh()->status);
        $this->assertSame(
            '2.000',
            $this->level($destination, $item)->fresh()->quantity_quarantined
        );
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


    public function test_destination_coordinator_can_record_consignment_acknowledgement(): void
    {
        $admin = $this->administrator();
        $source = $this->makeStore();
        $destination = $this->makeStore();
        $this->assign($admin, $source, StoreStaff::ROLE_COORDINATOR);
        $item = $this->makeItem($source, $admin, 'Consignment acknowledgement item');
        $this->makeLevel($source, $item, '5.000', '10.000000', '50.00');

        $consignment = app(\App\Actions\Stock\DispatchStockConsignment::class)->execute(
            $admin,
            $source,
            $destination,
            ['items' => [['stock_item_id' => $item->id, 'quantity' => 5]]]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $this->assign($coordinator, $destination, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($coordinator)
            ->get(route('stock.consignments.acknowledgements.create', $consignment))
            ->assertOk()
            ->assertSee('Record Acknowledgement');

        $this->actingAs($coordinator)
            ->post(route('stock.consignments.acknowledgements.store', $consignment), [
                'items' => [[
                    'stock_consignment_item_id' => $consignment->items()->first()->id,
                    'quantity_good' => 5,
                    'quantity_damaged' => 0,
                    'quantity_shortage' => 0,
                ]],
                'notes' => 'Received and checked.',
            ])
            ->assertRedirect(route('stock.consignments.show', $consignment));

        $this->assertDatabaseHas('stock_consignment_acknowledgements', [
            'stock_consignment_id' => $consignment->id,
            'acknowledged_by' => $coordinator->id,
        ]);

        $this->assertDatabaseHas('stock_consignments', [
            'id' => $consignment->id,
            'status' => \App\Models\StockConsignment::STATUS_RECONCILED,
        ]);
    }

    public function test_source_coordinator_cannot_acknowledge_destination_consignment(): void
    {
        $admin = $this->administrator();
        $source = $this->makeStore();
        $destination = $this->makeStore();
        $this->assign($admin, $source, StoreStaff::ROLE_ASSISTANT);

        $item = $this->makeItem($source, $admin, 'Consignment acknowledgement item');
        $this->makeLevel($source, $item, '5.000', '10.000000', '50.00');

        $consignment = app(DispatchStockConsignment::class)->execute(
            $admin,
            $source,
            $destination,
            ['items' => [['stock_item_id' => $item->id, 'quantity' => 5]]]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $this->assign($coordinator, $source, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($coordinator)
            ->get(route('stock.consignments.acknowledgements.create', $consignment))
            ->assertForbidden();

        $this->actingAs($coordinator)
            ->post(route('stock.consignments.acknowledgements.store', $consignment), [
                'items' => [[
                    'stock_consignment_item_id' => $consignment->items()->first()->id,
                    'quantity_good' => 5,
                    'quantity_damaged' => 0,
                    'quantity_shortage' => 0,
                ]],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('stock_consignment_acknowledgements', 0);
    }

    public function test_destination_assistant_can_acknowledge_consignment(): void
    {
        $admin = $this->administrator();
        $source = $this->makeStore();
        $destination = $this->makeStore();
        $this->assign($admin, $source, StoreStaff::ROLE_COORDINATOR);

        $item = $this->makeItem($source, $admin, 'Consignment acknowledgement item');
        $this->makeLevel($source, $item, '5.000', '10.000000', '50.00');

        $consignment = app(DispatchStockConsignment::class)->execute(
            $admin,
            $source,
            $destination,
            ['items' => [['stock_item_id' => $item->id, 'quantity' => 5]]]
        );

        $assistant = User::factory()->create(['is_active' => true]);
        $this->assign($assistant, $destination, StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($assistant)
            ->get(route('stock.consignments.acknowledgements.create', $consignment))
            ->assertOk()
            ->assertSee('Record Acknowledgement');

        $this->actingAs($assistant)
            ->post(route('stock.consignments.acknowledgements.store', $consignment), [
                'items' => [[
                    'stock_consignment_item_id' => $consignment->items()->first()->id,
                    'quantity_good' => 5,
                    'quantity_damaged' => 0,
                    'quantity_shortage' => 0,
                ]],
            ])
            ->assertRedirect(route('stock.consignments.show', $consignment));

        $this->assertDatabaseHas('stock_consignment_acknowledgements', [
            'stock_consignment_id' => $consignment->id,
            'acknowledged_by' => $assistant->id,
        ]);

        $this->assertDatabaseHas('stock_consignments', [
            'id' => $consignment->id,
            'status' => StockConsignment::STATUS_RECONCILED,
        ]);
    }

    public function test_acknowledgement_form_displays_only_remaining_quantity(): void
    {
        $admin = $this->administrator();
        $source = $this->makeStore();
        $destination = $this->makeStore();
        $this->assign($admin, $source, StoreStaff::ROLE_COORDINATOR);
        $item = $this->makeItem($source, $admin, 'Consignment acknowledgement item');
        $this->makeLevel($source, $item, '5.000', '10.000000', '50.00');

        $consignment = app(\App\Actions\Stock\DispatchStockConsignment::class)->execute(
            $admin,
            $source,
            $destination,
            ['items' => [['stock_item_id' => $item->id, 'quantity' => 5]]]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $this->assign($coordinator, $destination, StoreStaff::ROLE_COORDINATOR);

        app(\App\Actions\Stock\AcknowledgeStockConsignment::class)->execute(
            $coordinator,
            $consignment,
            ['items' => [[
                'stock_consignment_item_id' => $consignment->items()->first()->id,
                'quantity_good' => 2,
                'quantity_damaged' => 0,
                'quantity_shortage' => 0,
            ]]]
        );

        $this->actingAs($coordinator)
            ->get(route('stock.consignments.acknowledgements.create', $consignment))
            ->assertOk()
            ->assertSee('2.000')
            ->assertSee('3.000');
    }

    public function test_consignment_receipt_displays_acknowledgement_history(): void
    {
        $admin = $this->administrator();
        $source = $this->makeStore();
        $destination = $this->makeStore();
        $this->assign($admin, $source, StoreStaff::ROLE_COORDINATOR);
        $item = $this->makeItem($source, $admin, 'Consignment acknowledgement item');
        $this->makeLevel($source, $item, '5.000', '10.000000', '50.00');

        $consignment = app(\App\Actions\Stock\DispatchStockConsignment::class)->execute(
            $admin,
            $source,
            $destination,
            ['items' => [['stock_item_id' => $item->id, 'quantity' => 5]]]
        );

        $coordinator = User::factory()->create(['is_active' => true]);
        $this->assign($coordinator, $destination, StoreStaff::ROLE_COORDINATOR);

        app(\App\Actions\Stock\AcknowledgeStockConsignment::class)->execute(
            $coordinator,
            $consignment,
            ['items' => [[
                'stock_consignment_item_id' => $consignment->items()->first()->id,
                'quantity_good' => 3,
                'quantity_damaged' => 1,
                'quantity_shortage' => 1,
            ]],
            'notes' => 'One damaged and one missing.',
            ]
        );

        $this->actingAs($coordinator)
            ->get(route('stock.consignments.show', $consignment))
            ->assertOk()
            ->assertSee('Acknowledgement history')
            ->assertSee('One damaged and one missing.')
            ->assertSee('Record Acknowledgement');
    }

}
