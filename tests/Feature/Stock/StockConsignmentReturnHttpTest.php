<?php

namespace Tests\Feature\Stock;

use App\Actions\Stock\AcknowledgeStockConsignment;
use App\Actions\Stock\DispatchStockConsignment;
use App\Models\StockConsignment;
use App\Models\StockConsignmentReturn;
use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockConsignmentReturnHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_destination_assistant_can_dispatch_a_return_through_http(): void
    {
        [$source, $destination, $item, $consignment, $line, $destinationAssistant, , $destinationCoordinator] =
            $this->returnScenario(StoreStaff::ROLE_ASSISTANT);

        $this->acknowledgeGoodQuantity(
            $destinationCoordinator,
            $consignment,
            $line,
            '5.000'
        );

        $this->actingAs($destinationAssistant)
            ->get(route('stock.consignments.returns.create', $consignment))
            ->assertOk()
            ->assertSee('Dispatch return')
            ->assertSee('HTTP return stock');

        $response = $this->actingAs($destinationAssistant)
            ->post(route('stock.consignments.returns.store', $consignment), [
                'items' => [[
                    'stock_consignment_item_id' => $line->id,
                    'quantity' => '2.000',
                ]],
                'notes' => 'Return submitted through HTTP.',
            ]);

        $stockReturn = StockConsignmentReturn::query()->firstOrFail();

        $response->assertRedirect(route('stock.consignments.show', $consignment));

        $this->assertSame($destinationAssistant->id, $stockReturn->dispatched_by);
        $this->assertSame('2.000', $stockReturn->items->firstOrFail()->quantity_dispatched);
        $this->assertSame('3.000', $this->level($destination, $item)->quantity);
        $this->assertSame($source->id, $item->fresh()->store_id);
    }

    public function test_source_coordinator_can_confirm_return_receipt_through_http(): void
    {
        [$source, , $item, $consignment, $line, $destinationCoordinator, $sourceCoordinator] =
            $this->returnScenario();

        $this->acknowledgeGoodQuantity(
            $destinationCoordinator,
            $consignment,
            $line,
            '5.000'
        );

        $stockReturn = $this->dispatchReturn(
            $destinationCoordinator,
            $consignment,
            $line,
            '2.000'
        );

        $returnLine = $stockReturn->items->firstOrFail();

        $this->actingAs($sourceCoordinator)
            ->get(route('stock.consignments.returns.receive', $stockReturn))
            ->assertOk()
            ->assertSee('Confirm return receipt');

        $response = $this->actingAs($sourceCoordinator)
            ->post(route('stock.consignments.returns.confirm', $stockReturn), [
                'items' => [[
                    'stock_consignment_return_item_id' => $returnLine->id,
                    'quantity_received' => '1.000',
                ]],
                'notes' => 'One yard physically received.',
            ]);

        $response->assertRedirect(route('stock.consignments.show', $consignment));

        $this->assertDatabaseHas('stock_consignment_returns', [
            'id' => $stockReturn->id,
            'received_by' => $sourceCoordinator->id,
            'receipt_notes' => 'One yard physically received.',
        ]);

        $this->assertSame('6.000', $this->level($source, $item)->quantity);

        $this->assertDatabaseHas('stock_movements', [
            'store_id' => $source->id,
            'stock_item_id' => $item->id,
            'type' => 'consignment_return_in',
            'quantity' => '1.000',
            'source_id' => $stockReturn->id,
        ]);
    }

    public function test_source_assistant_cannot_view_or_confirm_return_receipt(): void
    {
        [, , , $consignment, $line, $destinationCoordinator, $sourceCoordinator] =
            $this->returnScenario();

        $this->acknowledgeGoodQuantity(
            $destinationCoordinator,
            $consignment,
            $line,
            '5.000'
        );

        $stockReturn = $this->dispatchReturn(
            $destinationCoordinator,
            $consignment,
            $line,
            '1.000'
        );

        $sourceAssistant = User::factory()->create(['is_active' => true]);
        $this->assign($sourceAssistant, $consignment->sourceStore, StoreStaff::ROLE_ASSISTANT);

        $this->actingAs($sourceAssistant)
            ->get(route('stock.consignments.returns.receive', $stockReturn))
            ->assertForbidden();

        $this->actingAs($sourceAssistant)
            ->post(route('stock.consignments.returns.confirm', $stockReturn), [
                'items' => [[
                    'stock_consignment_return_item_id' => $stockReturn->items->firstOrFail()->id,
                    'quantity_received' => '1.000',
                ]],
            ])
            ->assertForbidden();

        $this->assertNull($stockReturn->fresh()->received_at);
    }

    public function test_source_store_staff_cannot_dispatch_a_return(): void
    {
        [, , , $consignment, $line, , $sourceCoordinator, $destinationCoordinator] =
            $this->returnScenario();

        $this->acknowledgeGoodQuantity(
            $destinationCoordinator,
            $consignment,
            $line,
            '5.000'
        );

        $this->actingAs($sourceCoordinator)
            ->get(route('stock.consignments.returns.create', $consignment))
            ->assertForbidden();

        $this->actingAs($sourceCoordinator)
            ->post(route('stock.consignments.returns.store', $consignment), [
                'items' => [[
                    'stock_consignment_item_id' => $line->id,
                    'quantity' => '1.000',
                ]],
                'notes' => 'Unauthorized return attempt.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('stock_consignment_returns', 0);
    }

    private function returnScenario(string $destinationRole = StoreStaff::ROLE_COORDINATOR): array
    {
        $source = $this->makeStore();
        $destination = $this->makeStore();

        $sourceCoordinator = User::factory()->create(['is_active' => true]);
        $destinationCoordinator = User::factory()->create(['is_active' => true]);

        $this->assign($sourceCoordinator, $source, StoreStaff::ROLE_COORDINATOR);
        $this->assign($destinationCoordinator, $destination, StoreStaff::ROLE_COORDINATOR);

        $destinationOperator = $destinationCoordinator;

        if ($destinationRole === StoreStaff::ROLE_ASSISTANT) {
            $destinationOperator = User::factory()->create(['is_active' => true]);
            $this->assign($destinationOperator, $destination, StoreStaff::ROLE_ASSISTANT);
        }

        $item = StockItem::query()->create([
            'store_id' => $source->id,
            'description' => 'HTTP return stock',
            'category' => 'fabric',
            'unit' => StockItem::UNIT_YARD,
            'is_raw' => false,
            'approval_status' => StockItem::APPROVAL_APPROVED,
            'processing_status' => StockItem::PROCESSING_PENDING,
            'created_by' => $sourceCoordinator->id,
        ]);

        StockLevel::query()->create([
            'store_id' => $source->id,
            'stock_item_id' => $item->id,
            'quantity' => '10.000',
            'quantity_quarantined' => '0.000',
            'average_unit_cost' => '10.000000',
            'inventory_value' => '100.00',
            'quarantine_value' => '0.00',
        ]);

        $consignment = app(DispatchStockConsignment::class)->execute(
            $sourceCoordinator,
            $source,
            $destination,
            ['items' => [[
                'stock_item_id' => $item->id,
                'quantity' => '5.000',
            ]]]
        );

        return [
            $source,
            $destination,
            $item,
            $consignment,
            $consignment->items->firstOrFail(),
            $destinationOperator,
            $sourceCoordinator,
            $destinationCoordinator,
        ];
    }

    private function acknowledgeGoodQuantity(
        User $coordinator,
        StockConsignment $consignment,
        $line,
        string $quantity
    ): void {
        if (bccomp($quantity, '0.000', 3) === 0) {
            return;
        }

        app(AcknowledgeStockConsignment::class)->execute(
            $coordinator,
            $consignment,
            ['items' => [[
                'stock_consignment_item_id' => $line->id,
                'quantity_good' => $quantity,
                'quantity_damaged' => '0.000',
                'quantity_shortage' => bcsub('5.000', $quantity, 3),
            ]]]
        );
    }

    private function dispatchReturn(
        User $operator,
        StockConsignment $consignment,
        $line,
        string $quantity
    ): StockConsignmentReturn {
        return app(\App\Actions\Stock\DispatchStockConsignmentReturn::class)->execute(
            $operator,
            $consignment,
            ['items' => [[
                'stock_consignment_item_id' => $line->id,
                'quantity' => $quantity,
            ]]]
        );
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

    private function level(Store $store, StockItem $item): StockLevel
    {
        return StockLevel::query()
            ->where('store_id', $store->id)
            ->where('stock_item_id', $item->id)
            ->firstOrFail();
    }
}
