<?php

namespace Tests\Feature\Stock;

use App\Actions\Stock\AcknowledgeStockConsignment;
use App\Actions\Stock\DispatchStockConsignment;
use App\Actions\Stock\DispatchStockConsignmentReturn;
use App\Actions\Stock\ReceiveStockConsignmentReturn;
use App\Models\StockConsignment;
use App\Models\StockConsignmentReturn;
use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\TestCase;

class StockConsignmentReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_dispatch_deducts_destination_stock_and_records_its_current_cost(): void
    {
        [$source, $destination, $item, $consignment, $line, $destinationCoordinator] =
            $this->returnScenario();

        $destinationLevel = $this->level($destination, $item);
        $destinationLevel->update([
            'average_unit_cost' => '12.000000',
            'inventory_value' => '48.00',
        ]);

        $stockReturn = app(DispatchStockConsignmentReturn::class)->execute(
            $destinationCoordinator,
            $consignment,
            ['items' => [[
                'stock_consignment_item_id' => $line->id,
                'quantity' => '2.000',
            ]]]
        );

        $destinationLevel = $this->level($destination, $item);

        $this->assertSame('2.000', $destinationLevel->quantity);
        $this->assertSame('24.00', $destinationLevel->inventory_value);
        $this->assertSame('1.000', $destinationLevel->quantity_quarantined);
        $this->assertSame('10.00', $destinationLevel->quarantine_value);

        // Dispatching a return does not credit the source store.
        $this->assertSame('5.000', $this->level($source, $item)->quantity);

        $this->assertDatabaseHas('stock_movements', [
            'store_id' => $destination->id,
            'stock_item_id' => $item->id,
            'type' => StockMovement::TYPE_CONSIGNMENT_RETURN_OUT,
            'quantity' => '2.000',
            'unit_cost' => '12.000000',
            'movement_value' => '24.00',
            'source_type' => StockConsignmentReturn::class,
            'source_id' => $stockReturn->id,
            'created_by' => $destinationCoordinator->id,
        ]);

        $this->assertNull($stockReturn->fresh()->received_at);
        $this->assertSame($source->id, $item->fresh()->store_id);
    }

    public function test_return_dispatch_cannot_exceed_good_quantity_after_previous_returns(): void
    {
        [, $destination, , $consignment, $line, $destinationCoordinator] =
            $this->returnScenario();

        $service = app(DispatchStockConsignmentReturn::class);

        $service->execute($destinationCoordinator, $consignment, [
            'items' => [[
                'stock_consignment_item_id' => $line->id,
                'quantity' => '3.000',
            ]],
        ]);

        $movementCount = StockMovement::query()->count();
        $quantityBefore = $this->level($destination, $line->item)->quantity;

        try {
            $service->execute($destinationCoordinator, $consignment, [
                'items' => [[
                    'stock_consignment_item_id' => $line->id,
                    'quantity' => '2.000',
                ]],
            ]);
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
            $this->assertSame($movementCount, StockMovement::query()->count());
            $this->assertSame(
                $quantityBefore,
                $this->level($destination, $line->item)->quantity
            );

            return;
        }

        $this->fail('A return exceeding the remaining acknowledged-good quantity was accepted.');
    }

    public function test_source_coordinator_receipt_credits_only_quantity_physically_received(): void
    {
        [$source, $destination, $item, $consignment, $line, $destinationCoordinator, $sourceCoordinator] =
            $this->returnScenario();

        $stockReturn = app(DispatchStockConsignmentReturn::class)->execute(
            $destinationCoordinator,
            $consignment,
            ['items' => [[
                'stock_consignment_item_id' => $line->id,
                'quantity' => '2.000',
            ]]]
        );

        $returnLine = $stockReturn->items->firstOrFail();

        $received = app(ReceiveStockConsignmentReturn::class)->execute(
            $sourceCoordinator,
            $stockReturn,
            ['items' => [[
                'stock_consignment_return_item_id' => $returnLine->id,
                'quantity_received' => '1.000',
            ]], 'notes' => 'One yard received; one yard not received.']
        );

        $sourceLevel = $this->level($source, $item);

        $this->assertSame('6.000', $sourceLevel->quantity);
        $this->assertSame('60.00', $sourceLevel->inventory_value);
        $this->assertSame('10.000000', $sourceLevel->average_unit_cost);

        $this->assertSame('1.000', $received->items->firstOrFail()->quantity_received);
        $this->assertNotNull($received->received_at);
        $this->assertSame('One yard received; one yard not received.', $received->receipt_notes);

        $this->assertDatabaseHas('stock_movements', [
            'store_id' => $source->id,
            'stock_item_id' => $item->id,
            'type' => StockMovement::TYPE_CONSIGNMENT_RETURN_IN,
            'quantity' => '1.000',
            'unit_cost' => '10.000000',
            'movement_value' => '10.00',
            'source_type' => StockConsignmentReturn::class,
            'source_id' => $stockReturn->id,
            'created_by' => $sourceCoordinator->id,
        ]);

        // The unreceived balance is not added to source inventory.
        $this->assertSame('6.000', $this->level($source, $item)->quantity);
        $this->assertSame('2.000', $this->level($destination, $item)->quantity);
        $this->assertSame($source->id, $item->fresh()->store_id);
    }

    public function test_only_source_store_coordinator_can_confirm_return_receipt(): void
    {
        [$source, , , $consignment, $line, $destinationCoordinator] =
            $this->returnScenario();

        $stockReturn = app(DispatchStockConsignmentReturn::class)->execute(
            $destinationCoordinator,
            $consignment,
            ['items' => [[
                'stock_consignment_item_id' => $line->id,
                'quantity' => '1.000',
            ]]]
        );

        $sourceAssistant = User::factory()->create(['is_active' => true]);
        $this->assign($sourceAssistant, $source, StoreStaff::ROLE_ASSISTANT);

        $unassigned = User::factory()->create(['is_active' => true]);
        $movementCount = StockMovement::query()->count();

        foreach ([$sourceAssistant, $destinationCoordinator, $unassigned] as $actor) {
            try {
                app(ReceiveStockConsignmentReturn::class)->execute(
                    $actor,
                    $stockReturn,
                    ['items' => [[
                        'stock_consignment_return_item_id' => $stockReturn->items->firstOrFail()->id,
                        'quantity_received' => '1.000',
                    ]]]
                );

                $this->fail('An unauthorized user confirmed a return receipt.');
            } catch (AuthorizationException $exception) {
                $this->assertInstanceOf(AuthorizationException::class, $exception);
            }
        }

        $this->assertNull($stockReturn->fresh()->received_at);
        $this->assertSame($movementCount, StockMovement::query()->count());
    }

    public function test_return_receipt_cannot_be_confirmed_twice(): void
    {
        [, , , $consignment, $line, $destinationCoordinator, $sourceCoordinator] =
            $this->returnScenario();

        $stockReturn = app(DispatchStockConsignmentReturn::class)->execute(
            $destinationCoordinator,
            $consignment,
            ['items' => [[
                'stock_consignment_item_id' => $line->id,
                'quantity' => '1.000',
            ]]]
        );

        $payload = ['items' => [[
            'stock_consignment_return_item_id' => $stockReturn->items->firstOrFail()->id,
            'quantity_received' => '1.000',
        ]]];

        $service = app(ReceiveStockConsignmentReturn::class);
        $service->execute($sourceCoordinator, $stockReturn, $payload);

        $movementCount = StockMovement::query()->count();

        try {
            $service->execute($sourceCoordinator, $stockReturn, $payload);
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
            $this->assertSame($movementCount, StockMovement::query()->count());

            return;
        }

        $this->fail('A return receipt was confirmed more than once.');
    }

    private function returnScenario(): array
    {
        $source = $this->makeStore();
        $destination = $this->makeStore();

        $sourceCoordinator = User::factory()->create(['is_active' => true]);
        $destinationCoordinator = User::factory()->create(['is_active' => true]);

        $this->assign($sourceCoordinator, $source, StoreStaff::ROLE_COORDINATOR);
        $this->assign($destinationCoordinator, $destination, StoreStaff::ROLE_COORDINATOR);

        $item = StockItem::query()->create([
            'store_id' => $source->id,
            'description' => 'Return test stock',
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

        $line = $consignment->items->firstOrFail();

        app(AcknowledgeStockConsignment::class)->execute(
            $destinationCoordinator,
            $consignment,
            ['items' => [[
                'stock_consignment_item_id' => $line->id,
                'quantity_good' => '4.000',
                'quantity_damaged' => '1.000',
                'quantity_shortage' => '0.000',
            ]]]
        );

        return [
            $source,
            $destination,
            $item,
            $consignment,
            $line,
            $destinationCoordinator,
            $sourceCoordinator,
        ];
    }

    private function makeStore(): Store
    {
        return Store::query()->create([
            'name' => 'Return Store '.Str::random(8),
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
