<?php

namespace Tests\Feature\Stock;

use App\Actions\Stock\RecordStockProcessing;
use App\Models\Role;
use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockProcessing;
use App\Models\StockProcessingOutput;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecordStockProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_record_processing_with_multiple_fabric_outputs_and_recoverable_waste(): void
    {
        $admin = $this->administrator();
        $store = $this->makeStore();
        $this->assign($admin, $store, \App\Models\StoreStaff::ROLE_COORDINATOR);

        $raw = $this->makeItem($store, $admin, 'Raw Ankara', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);

        $fabricA = $this->makeItem($store, $admin, 'Printed Ankara');
        $fabricB = $this->makeItem($store, $admin, 'Plain Cotton');

        $waste = $this->makeItem($store, $admin, 'Reusable textile waste', [
            'unit' => StockItem::UNIT_KG,
            'category' => 'reusable-waste',
        ]);

        $this->makeLevel($store, $raw, '10.000', '200.000000', '2000.00');

        $processing = app(RecordStockProcessing::class)->execute(
            $admin,
            $store,
            $this->payload($raw, $fabricA, [
                'quantity_consumed' => '5.000',
                'recoverable_waste_kg' => '1.000',
                'discarded_waste_kg' => '0.500',
                'outputs' => [
                    [
                        'stock_item_id' => $fabricA->id,
                        'quantity_produced' => '20.000',
                        'line_cost' => '1200.00',
                    ],
                    [
                        'stock_item_id' => $fabricB->id,
                        'quantity_produced' => '10.000',
                        'line_cost' => '600.00',
                    ],
                ],
                'recoverable_waste' => [
                    'stock_item_id' => $waste->id,
                    'line_cost' => '100.00',
                ],
            ])
        );

        $this->assertSame('5.000', $processing->quantity_consumed);
        $this->assertSame('1000.00', $processing->consumed_cost);
        $this->assertSame('1900.00', $processing->allocated_cost);
        $this->assertSame('-900.00', $processing->cost_difference);
        $this->assertSame('1.000', $processing->recoverable_waste_kg);
        $this->assertSame('0.500', $processing->discarded_waste_kg);
        $this->assertCount(3, $processing->outputs);

        $rawLevel = $this->level($store, $raw);
        $this->assertSame('5.000', $rawLevel->quantity);
        $this->assertSame('1000.00', $rawLevel->inventory_value);
        $this->assertSame('200.000000', $rawLevel->average_unit_cost);

        $fabricLevel = $this->level($store, $fabricA);
        $this->assertSame('20.000', $fabricLevel->quantity);
        $this->assertSame('1200.00', $fabricLevel->inventory_value);
        $this->assertSame('60.000000', $fabricLevel->average_unit_cost);

        $wasteLevel = $this->level($store, $waste);
        $this->assertSame('1.000', $wasteLevel->quantity);
        $this->assertSame('100.00', $wasteLevel->inventory_value);

        $this->assertSame(
            4,
            StockMovement::query()
                ->where('source_type', StockProcessing::class)
                ->where('source_id', $processing->id)
                ->count()
        );

        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $raw->id,
            'type' => StockMovement::TYPE_PROCESSING_OUT,
            'quantity' => '5.000',
            'movement_value' => '1000.00',
            'source_id' => $processing->id,
        ]);

        $this->assertSame(
            StockItem::PROCESSING_PROCESSING,
            $raw->fresh()->processing_status
        );

        $this->assertDatabaseHas('stock_processing_status_events', [
            'stock_item_id' => $raw->id,
            'event_type' => 'session_started',
            'previous_status' => StockItem::PROCESSING_PENDING,
            'new_status' => StockItem::PROCESSING_PROCESSING,
        ]);
    }


    public function test_processing_rejects_raw_input_owned_by_another_store(): void
    {
        $admin = $this->administrator();
        $store = $this->makeStore();
        $this->assign($admin, $store, \App\Models\StoreStaff::ROLE_COORDINATOR);
        $otherStore = $this->makeStore();

        $foreignRaw = $this->makeItem($otherStore, $admin, 'Foreign raw fabric', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);
        $fabric = $this->makeItem($store, $admin, 'Local finished fabric');

        try {
            app(RecordStockProcessing::class)->execute(
                $admin,
                $store,
                $this->payload($foreignRaw, $fabric)
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('raw_stock_item_id', $exception->errors());
            $this->assertDatabaseCount('stock_processings', 0);
            $this->assertDatabaseCount('stock_movements', 0);

            return;
        }

        $this->fail('Processing accepted raw input owned by another store.');
    }

    public function test_processing_rejects_fabric_output_owned_by_another_store(): void
    {
        $admin = $this->administrator();
        $store = $this->makeStore();
        $this->assign($admin, $store, \App\Models\StoreStaff::ROLE_COORDINATOR);
        $otherStore = $this->makeStore();

        $raw = $this->makeItem($store, $admin, 'Local raw fabric', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);
        $foreignFabric = $this->makeItem($otherStore, $admin, 'Foreign finished fabric');

        try {
            app(RecordStockProcessing::class)->execute(
                $admin,
                $store,
                $this->payload($raw, $foreignFabric)
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('outputs', $exception->errors());
            $this->assertDatabaseCount('stock_processings', 0);
            $this->assertDatabaseCount('stock_movements', 0);

            return;
        }

        $this->fail('Processing accepted a fabric output owned by another store.');
    }

    public function test_processing_rejects_reusable_waste_owned_by_another_store(): void
    {
        $admin = $this->administrator();
        $store = $this->makeStore();
        $this->assign($admin, $store, \App\Models\StoreStaff::ROLE_COORDINATOR);
        $otherStore = $this->makeStore();

        $raw = $this->makeItem($store, $admin, 'Local raw fabric', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);
        $fabric = $this->makeItem($store, $admin, 'Local finished fabric');
        $foreignWaste = $this->makeItem($otherStore, $admin, 'Foreign reusable waste', [
            'unit' => StockItem::UNIT_KG,
            'category' => 'reusable-waste',
        ]);

        try {
            app(RecordStockProcessing::class)->execute(
                $admin,
                $store,
                $this->payload($raw, $fabric, [
                    'recoverable_waste_kg' => '1.000',
                    'recoverable_waste' => [
                        'stock_item_id' => $foreignWaste->id,
                        'line_cost' => '100.00',
                    ],
                ])
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('recoverable_waste', $exception->errors());
            $this->assertDatabaseCount('stock_processings', 0);
            $this->assertDatabaseCount('stock_movements', 0);

            return;
        }

        $this->fail('Processing accepted reusable waste owned by another store.');
    }

    public function test_assigned_coordinator_can_record_processing_for_their_store(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();

        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $raw = $this->makeItem($store, $coordinator, 'Raw fabric', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);

        $fabric = $this->makeItem($store, $coordinator, 'Finished fabric');

        $this->makeLevel($store, $raw, '10.000', '100.000000', '1000.00');

        $processing = app(RecordStockProcessing::class)->execute(
            $coordinator,
            $store,
            $this->payload($raw, $fabric)
        );

        $this->assertSame($coordinator->id, $processing->processed_by);
        $this->assertDatabaseHas('stock_movements', [
            'source_type' => StockProcessing::class,
            'source_id' => $processing->id,
        ]);
    }

    public function test_assistant_can_record_processing(): void
    {
        $assistant = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();

        $this->assign($assistant, $store, StoreStaff::ROLE_ASSISTANT);

        $raw = $this->makeItem($store, $assistant, 'Raw fabric', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);

        $fabric = $this->makeItem($store, $assistant, 'Finished fabric');

        $this->makeLevel($store, $raw, '10.000', '100.000000', '1000.00');

        $processing = app(RecordStockProcessing::class)->execute(
            $assistant,
            $store,
            $this->payload($raw, $fabric)
        );

        $this->assertSame($assistant->id, $processing->processed_by);
        $this->assertDatabaseHas('stock_movements', [
            'source_type' => StockProcessing::class,
            'source_id' => $processing->id,
        ]);
    }
    public function test_coordinator_cannot_process_stock_for_another_store(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $assignedStore = $this->makeStore();
        $otherStore = $this->makeStore();

        $this->assign($coordinator, $assignedStore, StoreStaff::ROLE_COORDINATOR);

        $raw = $this->makeItem($assignedStore, $coordinator, 'Raw fabric', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);

        $fabric = $this->makeItem($assignedStore, $coordinator, 'Finished fabric');

        $this->makeLevel($otherStore, $raw, '10.000', '100.000000', '1000.00');

        $this->expectException(AuthorizationException::class);

        app(RecordStockProcessing::class)->execute(
            $coordinator,
            $otherStore,
            $this->payload($raw, $fabric)
        );
    }

    public function test_processing_cannot_consume_more_than_available_raw_stock(): void
    {
        $admin = $this->administrator();
        $store = $this->makeStore();
        $this->assign($admin, $store, \App\Models\StoreStaff::ROLE_COORDINATOR);

        $raw = $this->makeItem($store, $admin, 'Raw fabric', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);

        $fabric = $this->makeItem($store, $admin, 'Finished fabric');

        $this->makeLevel($store, $raw, '2.000', '100.000000', '200.00');

        try {
            app(RecordStockProcessing::class)->execute(
                $admin,
                $store,
                $this->payload($raw, $fabric, [
                    'quantity_consumed' => '3.000',
                ])
            );

            $this->fail('Processing must reject insufficient raw stock.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('stock_processings', 0);
            $this->assertDatabaseCount('stock_movements', 0);
            $this->assertSame('2.000', $this->level($store, $raw)->quantity);
        }
    }

    public function test_recoverable_and_discarded_waste_cannot_exceed_consumed_weight(): void
    {
        $admin = $this->administrator();
        $store = $this->makeStore();
        $this->assign($admin, $store, \App\Models\StoreStaff::ROLE_COORDINATOR);

        $raw = $this->makeItem($store, $admin, 'Raw fabric', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);

        $fabric = $this->makeItem($store, $admin, 'Finished fabric');
        $waste = $this->makeItem($store, $admin, 'Reusable waste', [
            'unit' => StockItem::UNIT_KG,
            'category' => 'reusable-waste',
        ]);

        $this->makeLevel($store, $raw, '10.000', '100.000000', '1000.00');

        try {
            app(RecordStockProcessing::class)->execute(
                $admin,
                $store,
                $this->payload($raw, $fabric, [
                    'quantity_consumed' => '2.000',
                    'recoverable_waste_kg' => '1.500',
                    'discarded_waste_kg' => '1.000',
                    'recoverable_waste' => [
                        'stock_item_id' => $waste->id,
                        'line_cost' => '10.00',
                    ],
                ])
            );

            $this->fail('Waste greater than consumed weight must be rejected.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('stock_processings', 0);
            $this->assertDatabaseCount('stock_movements', 0);
            $this->assertSame('10.000', $this->level($store, $raw)->quantity);
        }
    }

    public function test_recoverable_waste_requires_an_approved_reusable_waste_item(): void
    {
        $admin = $this->administrator();
        $store = $this->makeStore();
        $this->assign($admin, $store, \App\Models\StoreStaff::ROLE_COORDINATOR);

        $raw = $this->makeItem($store, $admin, 'Raw fabric', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);

        $fabric = $this->makeItem($store, $admin, 'Finished fabric');
        $wrongWaste = $this->makeItem($store, $admin, 'Ordinary cotton', [
            'unit' => StockItem::UNIT_KG,
            'category' => 'fabric',
        ]);

        $this->makeLevel($store, $raw, '10.000', '100.000000', '1000.00');

        $this->expectException(ValidationException::class);

        app(RecordStockProcessing::class)->execute(
            $admin,
            $store,
            $this->payload($raw, $fabric, [
                'recoverable_waste_kg' => '1.000',
                'recoverable_waste' => [
                    'stock_item_id' => $wrongWaste->id,
                    'line_cost' => '10.00',
                ],
            ])
        );
    }

    public function test_processing_rejects_an_item_already_marked_processed(): void
    {
        $admin = $this->administrator();
        $store = $this->makeStore();
        $this->assign($admin, $store, \App\Models\StoreStaff::ROLE_COORDINATOR);

        $raw = $this->makeItem($store, $admin, 'Completed raw item', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
            'processing_status' => StockItem::PROCESSING_PROCESSED,
        ]);

        $fabric = $this->makeItem($store, $admin, 'Finished fabric');

        $this->makeLevel($store, $raw, '10.000', '100.000000', '1000.00');

        $this->expectException(ValidationException::class);

        app(RecordStockProcessing::class)->execute(
            $admin,
            $store,
            $this->payload($raw, $fabric)
        );
    }

    public function test_duplicate_output_lines_are_combined_and_cost_difference_does_not_block_processing(): void
    {
        $admin = $this->administrator();
        $store = $this->makeStore();
        $this->assign($admin, $store, \App\Models\StoreStaff::ROLE_COORDINATOR);

        $raw = $this->makeItem($store, $admin, 'Raw fabric', [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
        ]);

        $fabric = $this->makeItem($store, $admin, 'Finished fabric');

        $this->makeLevel($store, $raw, '10.000', '100.000000', '1000.00');

        $processing = app(RecordStockProcessing::class)->execute(
            $admin,
            $store,
            $this->payload($raw, $fabric, [
                'quantity_consumed' => '2.000',
                'outputs' => [
                    [
                        'stock_item_id' => $fabric->id,
                        'quantity_produced' => '5.000',
                        'line_cost' => '50.00',
                    ],
                    [
                        'stock_item_id' => $fabric->id,
                        'quantity_produced' => '5.000',
                        'line_cost' => '50.00',
                    ],
                ],
            ])
        );

        $this->assertSame('200.00', $processing->consumed_cost);
        $this->assertSame('100.00', $processing->allocated_cost);
        $this->assertSame('100.00', $processing->cost_difference);
        $this->assertSame(1, $processing->outputs()->count());

        $output = $processing->outputs()->firstOrFail();

        $this->assertSame('10.000', $output->quantity_produced);
        $this->assertSame('100.00', $output->line_cost);
        $this->assertSame('10.000', $this->level($store, $fabric)->quantity);
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

    private function payload(
        StockItem $raw,
        StockItem $fabric,
        array $overrides = []
    ): array {
        return array_merge([
            'raw_stock_item_id' => $raw->id,
            'quantity_consumed' => '2.000',
            'recoverable_waste_kg' => '0.000',
            'discarded_waste_kg' => '0.000',
            'processed_on' => '2026-11-15',
            'notes' => 'Processing feature test',
            'outputs' => [
                [
                    'stock_item_id' => $fabric->id,
                    'quantity_produced' => '10.000',
                    'line_cost' => '200.00',
                ],
            ],
        ], $overrides);
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


    public function test_administrator_can_dispatch_stock_into_destination_quarantine(): void
    {
        $admin = $this->administrator();
        $source = $this->makeStore();
        $this->assign($admin, $source, \App\Models\StoreStaff::ROLE_COORDINATOR);
        $destination = $this->makeStore();

        $item = $this->makeItem($source, $admin, 'Dispatch fabric', [
            'current_market_price' => '300.00',
        ]);

        $this->makeLevel($source, $item, '10.000', '200.000000', '2000.00');

        $consignment = app(\App\Actions\Stock\DispatchStockConsignment::class)->execute(
            $admin,
            $source,
            $destination,
            [
                'items' => [
                    ['stock_item_id' => $item->id, 'quantity' => '3.500'],
                ],
                'notes' => 'Transfer to destination store',
            ]
        );

        $this->assertSame(\App\Models\StockConsignment::STATUS_DISPATCHED, $consignment->status);
        $this->assertSame($source->id, $consignment->source_store_id);
        $this->assertSame($destination->id, $consignment->destination_store_id);
        $this->assertSame($admin->id, $consignment->dispatched_by);
        $this->assertSame('700.00', $consignment->total_cost);
        $this->assertSame('1050.00', $consignment->expected_sales_value);
        $this->assertSame('350.00', $consignment->expected_gross_return);
        $this->assertCount(1, $consignment->items);

        $sourceLevel = $this->level($source, $item);
        $this->assertSame('6.500', $sourceLevel->quantity);
        $this->assertSame('1300.00', $sourceLevel->inventory_value);
        $this->assertSame('200.000000', $sourceLevel->average_unit_cost);

        $destinationLevel = $this->level($destination, $item);
        $this->assertSame('0.000', $destinationLevel->quantity);
        $this->assertSame('3.500', $destinationLevel->quantity_quarantined);
        $this->assertSame('0.00', $destinationLevel->inventory_value);
        $this->assertSame('700.00', $destinationLevel->quarantine_value);

        // Consignment changes physical location, not permanent item ownership.
        $this->assertSame($source->id, $item->fresh()->store_id);

        $this->assertSame(
            2,
            StockMovement::query()
                ->where('source_type', \App\Models\StockConsignment::class)
                ->where('source_id', $consignment->id)
                ->count()
        );

        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $item->id,
            'store_id' => $source->id,
            'type' => StockMovement::TYPE_CONSIGNMENT_OUT,
            'quantity' => '3.500',
            'movement_value' => '700.00',
            'source_id' => $consignment->id,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $item->id,
            'store_id' => $destination->id,
            'type' => StockMovement::TYPE_CONSIGNMENT_IN,
            'quantity' => '3.500',
            'movement_value' => '700.00',
            'source_id' => $consignment->id,
        ]);
    }

    public function test_source_coordinator_and_assistant_can_dispatch(): void
    {
        $source = $this->makeStore();
        $destination = $this->makeStore();

        $coordinator = User::factory()->create(['is_active' => true]);
        $this->assign($coordinator, $source, StoreStaff::ROLE_COORDINATOR);

        $item = $this->makeItem($source, $coordinator, 'Coordinator dispatch fabric');
        $this->makeLevel($source, $item, '5.000', '100.000000', '500.00');

        $first = app(\App\Actions\Stock\DispatchStockConsignment::class)->execute(
            $coordinator,
            $source,
            $destination,
            [
                'items' => [
                    ['stock_item_id' => $item->id, 'quantity' => '2.000'],
                ],
            ]
        );

        $this->assertSame($coordinator->id, $first->dispatched_by);

        $assistant = User::factory()->create(['is_active' => true]);
        $this->assign($assistant, $source, StoreStaff::ROLE_ASSISTANT);

        $second = app(\App\Actions\Stock\DispatchStockConsignment::class)->execute(
            $assistant,
            $source,
            $destination,
            [
                'items' => [
                    ['stock_item_id' => $item->id, 'quantity' => '1.000'],
                ],
            ]
        );

        $this->assertSame($assistant->id, $second->dispatched_by);
        $this->assertDatabaseCount('stock_consignments', 2);
        $this->assertSame('2.000', $this->level($source, $item)->quantity);
    }

    public function test_dispatch_accepts_stock_originating_from_another_store_when_source_has_available_stock(): void
    {
        $admin = $this->administrator();
        $source = $this->makeStore();
        $this->assign($admin, $source, \App\Models\StoreStaff::ROLE_COORDINATOR);
        $destination = $this->makeStore();
        $owner = $this->makeStore();

        $foreignItem = $this->makeItem($owner, $admin, 'Foreign fabric');
        $this->makeLevel($source, $foreignItem, '5.000', '100.000000', '500.00');

        app(\App\Actions\Stock\DispatchStockConsignment::class)->execute(
            $admin,
            $source,
            $destination,
            [
                'items' => [
                    ['stock_item_id' => $foreignItem->id, 'quantity' => '1.000'],
                ],
            ]
        );

        $this->assertSame($owner->id, $foreignItem->fresh()->store_id);
        $this->assertSame('4.000', $this->level($source, $foreignItem)->quantity);
        $this->assertSame('1.000', $this->level($destination, $foreignItem)->quantity_quarantined);
        $this->assertDatabaseCount('stock_consignments', 1);
    }

    public function test_dispatch_rejects_quantity_above_available_source_stock(): void
    {
        $admin = $this->administrator();
        $source = $this->makeStore();
        $this->assign($admin, $source, \App\Models\StoreStaff::ROLE_COORDINATOR);
        $destination = $this->makeStore();

        $item = $this->makeItem($source, $admin, 'Limited fabric');
        $this->makeLevel($source, $item, '2.000', '100.000000', '200.00');

        try {
            app(\App\Actions\Stock\DispatchStockConsignment::class)->execute(
                $admin,
                $source,
                $destination,
                [
                    'items' => [
                        ['stock_item_id' => $item->id, 'quantity' => '3.000'],
                    ],
                ]
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
            $this->assertSame('2.000', $this->level($source, $item)->quantity);
            $this->assertDatabaseCount('stock_consignments', 0);
            $this->assertDatabaseCount('stock_movements', 0);

            return;
        }

        $this->fail('Dispatch accepted a quantity above available stock.');
    }

}
