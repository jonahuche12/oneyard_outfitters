<?php

namespace Tests\Feature\Stock;

use App\Models\Intake;
use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockProcessing;
use App\Models\StockProcessingOutput;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tables_and_columns_exist(): void
    {
        foreach (['intakes', 'stock_items', 'stock_levels', 'stock_movements', 'stock_processings', 'stock_processing_outputs'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }

        $this->assertTrue(Schema::hasColumns('intakes', [
            'id', 'store_id', 'type', 'supplier_name', 'reference', 'received_on', 'bale_count',
            'weight', 'weight_unit', 'weight_kg', 'rate', 'total_cost', 'notes', 'received_by',
            'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('stock_items', [
            'id', 'name', 'code', 'store_id', 'intake_id', 'description', 'category', 'grade', 'unit', 'is_raw',
            'unit_cost', 'suggested_price', 'current_market_price', 'approval_status',
            'approved_by', 'approved_at', 'rejection_reason', 'processing_status',
            'processing_completed_at', 'created_by', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('stock_levels', [
            'id', 'stock_item_id', 'store_id', 'quantity', 'quantity_quarantined',
            'average_unit_cost', 'inventory_value', 'quarantine_value',
            'store_price', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('stock_movements', [
            'id', 'stock_item_id', 'store_id', 'type', 'quantity', 'unit_cost',
            'movement_value', 'source_type', 'source_id', 'reversal_of_id',
            'reason', 'created_by', 'created_at',
        ]));
        $this->assertFalse(Schema::hasColumn('stock_movements', 'updated_at'));

        $this->assertTrue(Schema::hasColumns('stock_processings', [
            'id', 'store_id', 'raw_stock_item_id', 'quantity_consumed', 'consumed_cost',
            'allocated_cost', 'cost_difference', 'recoverable_waste_kg',
            'discarded_waste_kg', 'processed_on', 'notes', 'processed_by',
            'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('stock_processing_outputs', [
            'id', 'stock_processing_id', 'stock_item_id', 'quantity_produced',
            'line_cost', 'created_at', 'updated_at',
        ]));
    }

    public function test_required_unique_indexes_exist(): void
    {
        $this->assertUniqueIndex('stock_items', ['name']);
        $this->assertUniqueIndex('stock_items', ['code']);
        $this->assertUniqueIndex('stock_levels', ['stock_item_id', 'store_id']);
        $this->assertUniqueIndex(
            'stock_processing_outputs',
            ['stock_processing_id', 'stock_item_id']
        );
    }

    public function test_required_foreign_keys_exist(): void
    {
        $this->assertForeignKey('intakes', 'store_id', 'stores');
        $this->assertForeignKey('intakes', 'received_by', 'users');
        $this->assertForeignKey('stock_items', 'store_id', 'stores');
        $this->assertForeignKey('stock_items', 'intake_id', 'intakes');
        $this->assertForeignKey('stock_items', 'created_by', 'users');
        $this->assertForeignKey('stock_levels', 'stock_item_id', 'stock_items');
        $this->assertForeignKey('stock_levels', 'store_id', 'stores');
        $this->assertForeignKey('stock_movements', 'stock_item_id', 'stock_items');
        $this->assertForeignKey('stock_movements', 'store_id', 'stores');
        $this->assertForeignKey('stock_movements', 'created_by', 'users');
        $this->assertForeignKey('stock_processings', 'store_id', 'stores');
        $this->assertForeignKey('stock_processings', 'raw_stock_item_id', 'stock_items');
        $this->assertForeignKey('stock_processings', 'processed_by', 'users');
        $this->assertForeignKey('stock_processing_outputs', 'stock_processing_id', 'stock_processings');
        $this->assertForeignKey('stock_processing_outputs', 'stock_item_id', 'stock_items');
    }

    public function test_a_stock_level_pair_must_be_unique(): void
    {
        [$store, $user] = $this->context();
        $item = $this->makeItem($this->makeIntake($store, $user), $user);

        StockLevel::query()->create(['stock_item_id' => $item->id, 'store_id' => $store->id]);

        $this->expectException(QueryException::class);

        StockLevel::query()->create(['stock_item_id' => $item->id, 'store_id' => $store->id]);
    }

    public function test_quantities_and_costs_keep_their_precision(): void
    {
        [$store, $user] = $this->context();

        $intake = $this->makeIntake($store, $user, [
            'type' => Intake::TYPE_LOT,
            'weight' => '12.345',
            'weight_unit' => Intake::WEIGHT_UNIT_KG,
            'weight_kg' => '12.345',
            'rate' => '1250.5000',
            'total_cost' => '15437.57',
        ]);
        $item = $this->makeItem($intake, $user, [
            'unit_cost' => '0.333333',
            'suggested_price' => '2500.50',
        ]);
        $level = StockLevel::query()->create([
            'stock_item_id' => $item->id,
            'store_id' => $store->id,
            'quantity' => '12.345',
            'store_price' => '2600.00',
        ]);

        $this->assertSame('12.345', $intake->fresh()->weight);
        $this->assertSame('1250.5000', $intake->fresh()->rate);
        $this->assertSame('15437.57', $intake->fresh()->total_cost);
        $this->assertSame('0.333333', $item->fresh()->unit_cost);
        $this->assertSame('2500.50', $item->fresh()->suggested_price);
        $this->assertSame('12.345', $level->fresh()->quantity);
        $this->assertSame('2600.00', $level->fresh()->store_price);
    }

    public function test_a_store_with_stock_records_cannot_be_deleted(): void
    {
        [$store, $user] = $this->context();
        $this->makeIntake($store, $user);

        $this->expectException(QueryException::class);

        $store->delete();
    }

    public function test_a_stock_item_with_movements_cannot_be_deleted(): void
    {
        [$store, $user] = $this->context();
        $item = $this->makeItem($this->makeIntake($store, $user), $user);

        StockMovement::query()->create([
            'stock_item_id' => $item->id,
            'store_id' => $store->id,
            'type' => StockMovement::TYPE_INTAKE,
            'quantity' => '10.000',
            'created_by' => $user->id,
        ]);

        $this->expectException(QueryException::class);

        $item->delete();
    }

    public function test_the_same_output_item_can_be_used_in_different_processing_sessions(): void
    {
        [$store, $user] = $this->context();
        $intake = $this->makeIntake($store, $user, ['type' => Intake::TYPE_BALE]);
        $raw = $this->makeItem($intake, $user, [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
            'unit_cost' => '5000.000000',
        ]);
        $yard = $this->makeItem($intake, $user);

        $first = $this->makeProcessing($store, $user, $raw);
        $second = $this->makeProcessing($store, $user, $raw);

        $firstOutput = StockProcessingOutput::query()->create([
            'stock_processing_id' => $first->id,
            'stock_item_id' => $yard->id,
            'quantity_produced' => '48.000',
            'line_cost' => '4800.00',
        ]);

        $secondOutput = StockProcessingOutput::query()->create([
            'stock_processing_id' => $second->id,
            'stock_item_id' => $yard->id,
            'quantity_produced' => '30.000',
            'line_cost' => '3000.00',
        ]);

        $this->assertNotSame($firstOutput->id, $secondOutput->id);
        $this->assertCount(2, $yard->fresh()->processingOutputs);
    }

    public function test_an_output_item_cannot_be_repeated_within_one_processing_session(): void
    {
        [$store, $user] = $this->context();
        $intake = $this->makeIntake($store, $user, ['type' => Intake::TYPE_BALE]);
        $raw = $this->makeItem($intake, $user, [
            'unit' => StockItem::UNIT_KG,
            'is_raw' => true,
            'unit_cost' => '5000.000000',
        ]);
        $yard = $this->makeItem($intake, $user);
        $processing = $this->makeProcessing($store, $user, $raw);

        StockProcessingOutput::query()->create([
            'stock_processing_id' => $processing->id,
            'stock_item_id' => $yard->id,
            'quantity_produced' => '48.000',
            'line_cost' => '4800.00',
        ]);

        $this->expectException(QueryException::class);

        StockProcessingOutput::query()->create([
            'stock_processing_id' => $processing->id,
            'stock_item_id' => $yard->id,
            'quantity_produced' => '12.000',
            'line_cost' => '1200.00',
        ]);
    }

    public function test_optional_columns_are_nullable(): void
    {
        [$store, $user] = $this->context();

        $intake = $this->makeIntake($store, $user)->fresh();
        $item = $this->makeItem($intake, $user)->fresh();
        $level = StockLevel::query()->create(['stock_item_id' => $item->id, 'store_id' => $store->id])->fresh();
        $movement = StockMovement::query()->create([
            'stock_item_id' => $item->id,
            'store_id' => $store->id,
            'type' => StockMovement::TYPE_INTAKE,
            'quantity' => '5.000',
            'created_by' => $user->id,
        ])->fresh();
        $processing = $this->makeProcessing($store, $user, $item)->fresh();

        $this->assertNull($intake->supplier_name);
        $this->assertNull($intake->reference);
        $this->assertNull($intake->bale_count);
        $this->assertNull($intake->weight);
        $this->assertNull($intake->weight_unit);
        $this->assertNull($intake->weight_kg);
        $this->assertNull($intake->rate);
        $this->assertNull($intake->notes);
        $this->assertNull($item->category);
        $this->assertNull($item->grade);
        $this->assertNull($item->suggested_price);
        $this->assertSame('0.000', $level->quantity);
        $this->assertNull($level->store_price);
        $this->assertNull($movement->reason);
        $this->assertNull($movement->source_type);
        $this->assertNull($movement->source_id);
        $this->assertNull($processing->notes);
    }

    // -------------------------------------------------------------------- Helpers

    private function context(): array
    {
        $store = Store::query()->create([
            'name' => 'Store '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
        ]);

        return [$store, User::factory()->create()];
    }

    private function makeIntake(Store $store, User $user, array $overrides = []): Intake
    {
        return Intake::query()->create(array_merge([
            'store_id' => $store->id,
            'type' => Intake::TYPE_PICKING,
            'received_on' => now()->toDateString(),
            'total_cost' => '1000.00',
            'received_by' => $user->id,
        ], $overrides));
    }

    private function makeItem(Intake $intake, User $user, array $overrides = []): StockItem
    {
        return StockItem::query()->create(array_merge([
            'store_id' => $intake->store_id,
            'intake_id' => $intake->id,
            'description' => 'Ankara blue floral',
            'unit' => StockItem::UNIT_YARD,
            'unit_cost' => '100.000000',
            'created_by' => $user->id,
        ], $overrides));
    }

    private function makeProcessing(Store $store, User $user, StockItem $raw): StockProcessing
    {
        return StockProcessing::query()->create([
            'store_id' => $store->id,
            'raw_stock_item_id' => $raw->id,
            'quantity_consumed' => '1.000',
            'consumed_cost' => '5000.00',
            'allocated_cost' => '4800.00',
            'cost_difference' => '-200.00',
            'processed_by' => $user->id,
        ]);
    }

    private function assertUniqueIndex(string $table, array $columns): void
    {
        $found = collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => $index['unique'] && $index['columns'] === $columns);

        $this->assertTrue($found, "Missing unique index on {$table} (".implode(', ', $columns).')');
    }

    private function assertForeignKey(string $table, string $column, string $foreignTable): void
    {
        $found = collect(Schema::getForeignKeys($table))
            ->contains(fn (array $key) => $key['columns'] === [$column] && $key['foreign_table'] === $foreignTable);

        $this->assertTrue($found, "Missing foreign key {$table}.{$column} -> {$foreignTable}");
    }
}
