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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_are_derived_from_ids(): void
    {
        [$store, $user] = $this->context();

        $intake = $this->makeIntake($store, $user);
        $raw = $this->makeItem($intake, $user, ['unit' => StockItem::UNIT_BALE, 'is_raw' => true]);
        $processing = $this->makeProcessing($store, $user, $raw);

        $this->assertSame(sprintf('INT-%06d', $intake->id), $intake->number);
        $this->assertSame(sprintf('PRC-%06d', $processing->id), $processing->number);
        $this->assertMatchesRegularExpression('/^INT-\d{6}$/', $intake->number);
        $this->assertMatchesRegularExpression('/^PRC-\d{6}$/', $processing->number);
    }

    public function test_intake_stock_item_and_level_relationships(): void
    {
        [$store, $user] = $this->context();

        $intake = $this->makeIntake($store, $user);
        $item = $this->makeItem($intake, $user);
        $level = StockLevel::query()->create([
            'stock_item_id' => $item->id,
            'store_id' => $store->id,
            'quantity' => '40.000',
        ]);

        $this->assertTrue($intake->store->is($store));
        $this->assertTrue($intake->receiver->is($user));
        $this->assertSame([$item->id], $intake->stockItems->pluck('id')->all());
        $this->assertTrue($item->intake->is($intake));
        $this->assertTrue($item->creator->is($user));
        $this->assertSame([$level->id], $item->levels->pluck('id')->all());
        $this->assertTrue($level->item->is($item));
        $this->assertTrue($level->store->is($store));
    }

    public function test_effective_price_falls_back_to_the_suggested_price(): void
    {
        [$store, $user] = $this->context();
        $other = Store::query()->create(['name' => 'Other '.Str::random(6), 'code' => strtoupper(Str::random(8))]);

        $item = $this->makeItem($this->makeIntake($store, $user), $user, ['suggested_price' => '2500.00']);

        $own = StockLevel::query()->create(['stock_item_id' => $item->id, 'store_id' => $store->id, 'store_price' => '3000.00']);
        $inherited = StockLevel::query()->create(['stock_item_id' => $item->id, 'store_id' => $other->id]);

        $this->assertSame('3000.00', $own->fresh()->effectivePrice());
        $this->assertSame('2500.00', $inherited->fresh()->effectivePrice());

        $unpriced = $this->makeItem($this->makeIntake($store, $user), $user);
        $unpricedLevel = StockLevel::query()->create(['stock_item_id' => $unpriced->id, 'store_id' => $store->id]);

        $this->assertNull($unpricedLevel->fresh()->effectivePrice());
    }

    public function test_stock_movements_are_append_only(): void
    {
        [$store, $user] = $this->context();
        $item = $this->makeItem($this->makeIntake($store, $user), $user);

        $movement = StockMovement::query()->create([
            'stock_item_id' => $item->id,
            'store_id' => $store->id,
            'type' => StockMovement::TYPE_INTAKE,
            'quantity' => '25.500',
            'created_by' => $user->id,
        ]);

        try {
            $movement->update(['reason' => 'tampering']);
            $this->fail('Expected the movement update to be refused.');
        } catch (\LogicException) {
            // expected
        }

        try {
            $movement->delete();
            $this->fail('Expected the movement delete to be refused.');
        } catch (\LogicException) {
            // expected
        }

        $this->assertSame(1, StockMovement::query()->count());
        $this->assertNull($movement->fresh()->reason);
        $this->assertSame('25.500', $movement->fresh()->quantity);
    }

    public function test_a_movement_resolves_its_source_and_actor(): void
    {
        [$store, $user] = $this->context();
        $intake = $this->makeIntake($store, $user);
        $item = $this->makeItem($intake, $user);

        $movement = StockMovement::query()->create([
            'stock_item_id' => $item->id,
            'store_id' => $store->id,
            'type' => StockMovement::TYPE_INTAKE,
            'quantity' => '40.000',
            'source_type' => Intake::class,
            'source_id' => $intake->id,
            'reason' => 'Initial receipt',
            'created_by' => $user->id,
        ]);

        $fresh = $movement->fresh();

        $this->assertNotNull($fresh->created_at);
        $this->assertTrue($fresh->source->is($intake));
        $this->assertTrue($fresh->actor->is($user));
        $this->assertTrue($fresh->item->is($item));
        $this->assertTrue($fresh->store->is($store));
        $this->assertSame([$movement->id], $item->movements->pluck('id')->all());
    }

    public function test_processing_relationships(): void
    {
        [$store, $user] = $this->context();
        $intake = $this->makeIntake($store, $user, ['type' => Intake::TYPE_BALE]);
        $raw = $this->makeItem($intake, $user, ['unit' => StockItem::UNIT_BALE, 'is_raw' => true, 'unit_cost' => '5000.000000']);
        $yard = $this->makeItem($intake, $user);

        $processing = $this->makeProcessing($store, $user, $raw);
        $output = StockProcessingOutput::query()->create([
            'stock_processing_id' => $processing->id,
            'stock_item_id' => $yard->id,
            'quantity_produced' => '48.000',
            'line_cost' => '4800.00',
        ]);

        $this->assertTrue($processing->store->is($store));
        $this->assertTrue($processing->rawItem->is($raw));
        $this->assertTrue($processing->processor->is($user));
        $this->assertSame([$output->id], $processing->outputs->pluck('id')->all());
        $this->assertTrue($output->item->is($yard));
        $this->assertTrue($output->processing->is($processing));
        $this->assertSame([$output->id], $yard->processingOutputs->pluck('id')->all());
        $this->assertTrue($yard->processingOutputs->first()->processing->is($processing));
        $this->assertSame('48.000', $output->fresh()->quantity_produced);
        $this->assertSame([$processing->id], $raw->processingsAsRaw->pluck('id')->all());
        $this->assertSame('-200.00', $processing->fresh()->cost_difference);
    }

    public function test_type_and_unit_constants_are_exposed(): void
    {
        $this->assertSame(['picking', 'bale', 'lot'], Intake::TYPES);
        $this->assertSame(['kg', 'lb'], Intake::WEIGHT_UNITS);
        $this->assertSame(['yard', 'kg', 'bale'], StockItem::UNITS);
        $this->assertSame([
            'opening_stock',
            'intake',
            'processing_out',
            'processing_in',
            'consignment_out',
            'consignment_in',
            'consignment_release',
            'consignment_shortage',
            'consignment_write_off',
            'consignment_return_out',
            'consignment_return_in',
            'sale_out',
        ], StockMovement::TYPES);
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
}
