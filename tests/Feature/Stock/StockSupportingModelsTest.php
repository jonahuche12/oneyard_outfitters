<?php

namespace Tests\Feature\Stock;

use App\Models\Intake;
use App\Models\IntakeItem;
use App\Models\StockItem;
use App\Models\StockItemApproval;
use App\Models\StockItemPriceHistory;
use App\Models\StockProcessingStatusEvent;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockSupportingModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_intake_can_record_multiple_item_lines(): void
    {
        [$store, $user] = $this->context();

        $intake = $this->makeIntake($store, $user);
        $firstItem = $this->makeItem($store, $user, 'Blue Ankara');
        $secondItem = $this->makeItem($store, $user, 'White Cotton');

        $firstLine = IntakeItem::query()->create([
            'intake_id' => $intake->id,
            'stock_item_id' => $firstItem->id,
            'quantity' => '20.500',
            'unit_cost' => '100.123456',
            'line_cost' => '2052.53',
        ]);

        $secondLine = IntakeItem::query()->create([
            'intake_id' => $intake->id,
            'stock_item_id' => $secondItem->id,
            'quantity' => '10.000',
            'unit_cost' => '200.000000',
            'line_cost' => '2000.00',
        ]);

        $this->assertCount(2, $intake->items);
        $this->assertTrue($firstLine->intake->is($intake));
        $this->assertTrue($firstLine->item->is($firstItem));
        $this->assertTrue($secondLine->item->is($secondItem));
        $this->assertSame('20.500', $firstLine->fresh()->quantity);
        $this->assertSame('100.123456', $firstLine->fresh()->unit_cost);
        $this->assertSame('2052.53', $firstLine->fresh()->line_cost);
    }

    public function test_stock_item_approval_history_resolves_reviewer(): void
    {
        [$store, $user] = $this->context();
        $item = $this->makeItem($store, $user);

        $approval = StockItemApproval::query()->create([
            'stock_item_id' => $item->id,
            'decision' => StockItemApproval::DECISION_APPROVED,
            'reason' => 'Specification checked',
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        $this->assertTrue($approval->item->is($item));
        $this->assertTrue($approval->reviewer->is($user));
        $this->assertSame([$approval->id], $item->approvals->pluck('id')->all());
        $this->assertInstanceOf(
            \Illuminate\Support\Carbon::class,
            $approval->fresh()->reviewed_at
        );
    }

    public function test_market_price_history_preserves_each_recorded_price(): void
    {
        [$store, $user] = $this->context();
        $item = $this->makeItem($store, $user);

        $first = StockItemPriceHistory::query()->create([
            'stock_item_id' => $item->id,
            'market_price' => '2500.00',
            'effective_at' => now()->subDay(),
            'reason' => 'Initial market assessment',
            'recorded_by' => $user->id,
        ]);

        $second = StockItemPriceHistory::query()->create([
            'stock_item_id' => $item->id,
            'market_price' => '2750.50',
            'effective_at' => now(),
            'reason' => 'Supplier market update',
            'recorded_by' => $user->id,
        ]);

        $this->assertTrue($first->item->is($item));
        $this->assertTrue($second->recorder->is($user));
        $this->assertCount(2, $item->priceHistory);
        $this->assertSame('2500.00', $first->fresh()->market_price);
        $this->assertSame('2750.50', $second->fresh()->market_price);
    }

    public function test_processing_status_events_preserve_status_history(): void
    {
        [$store, $user] = $this->context();
        $item = $this->makeItem($store, $user);

        $event = StockProcessingStatusEvent::query()->create([
            'stock_item_id' => $item->id,
            'event_type' => 'started',
            'previous_status' => StockItem::PROCESSING_PENDING,
            'new_status' => StockItem::PROCESSING_PROCESSING,
            'reason' => 'Processing started',
            'recorded_by' => $user->id,
            'recorded_at' => now(),
        ]);

        $this->assertTrue($event->item->is($item));
        $this->assertTrue($event->recorder->is($user));
        $this->assertSame(
            [$event->id],
            $item->processingStatusEvents->pluck('id')->all()
        );
        $this->assertSame(
            StockItem::PROCESSING_PENDING,
            $event->fresh()->previous_status
        );
        $this->assertSame(
            StockItem::PROCESSING_PROCESSING,
            $event->fresh()->new_status
        );
    }

    private function context(): array
    {
        $store = Store::query()->create([
            'name' => 'Store '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
        ]);

        return [$store, User::factory()->create()];
    }

    private function makeIntake(Store $store, User $user): Intake
    {
        return Intake::query()->create([
            'store_id' => $store->id,
            'type' => Intake::TYPE_PICKING,
            'received_on' => now()->toDateString(),
            'total_cost' => '5000.00',
            'received_by' => $user->id,
        ]);
    }

    private function makeItem(Store $store, User $user, string $description = 'Ankara fabric'): StockItem
    {
        return StockItem::query()->create([
            'store_id' => $store->id,
            'description' => $description,
            'unit' => StockItem::UNIT_YARD,
            'unit_cost' => '100.000000',
            'created_by' => $user->id,
        ]);
    }
}
