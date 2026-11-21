<?php

namespace Tests\Feature\Stock;

use App\Models\Store;

use App\Actions\Stock\AcknowledgeStockConsignment;
use App\Actions\Stock\DispatchStockConsignment;
use App\Models\Role;
use App\Models\StockConsignment;
use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AcknowledgeStockConsignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_good_stock_is_released_and_damage_and_shortage_are_recorded(): void
    {
        [, $source, $destination, $item, $consignment, $line] = $this->dispatchedConsignment();
        $coordinator = User::factory()->create(['is_active' => true]);
        $this->assign($coordinator, $destination, StoreStaff::ROLE_COORDINATOR);

        $ack = app(AcknowledgeStockConsignment::class)->execute($coordinator, $consignment, [
            'items' => [[
                'stock_consignment_item_id' => $line->id,
                'quantity_good' => '2.000',
                'quantity_damaged' => '1.000',
                'quantity_shortage' => '1.000',
            ]],
        ]);

        $level = StockLevel::where('store_id', $destination->id)
            ->where('stock_item_id', $item->id)->firstOrFail();

        $this->assertSame('2.000', $level->quantity);
        $this->assertSame('2.000', $level->quantity_quarantined);
        $this->assertSame('20.00', $level->inventory_value);
        $this->assertSame('20.00', $level->quarantine_value);
        $this->assertSame(StockConsignment::STATUS_UNDER_REVIEW, $consignment->fresh()->status);
        $this->assertDatabaseCount('stock_consignment_discrepancies', 2);
        $this->assertDatabaseHas('stock_consignment_discrepancies', [
            'acknowledgement_item_id' => $ack->items->first()->id,
            'type' => 'damaged',
            'quantity' => '1.000',
            'status' => 'open',
        ]);
        $this->assertDatabaseHas('stock_consignment_discrepancies', [
            'type' => 'shortage',
            'quantity' => '1.000',
            'status' => 'open',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'store_id' => $destination->id,
            'type' => StockMovement::TYPE_CONSIGNMENT_RELEASE,
            'quantity' => '2.000',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'store_id' => $destination->id,
            'type' => StockMovement::TYPE_CONSIGNMENT_SHORTAGE,
            'quantity' => '1.000',
        ]);
        $this->assertSame($source->id, $item->fresh()->store_id);
    }

    public function test_under_review_consignment_accepts_its_remaining_quantity(): void
    {
        [, , $destination, , $consignment, $line] = $this->dispatchedConsignment();
        $coordinator = User::factory()->create(['is_active' => true]);
        $this->assign($coordinator, $destination, StoreStaff::ROLE_COORDINATOR);
        $service = app(AcknowledgeStockConsignment::class);

        $service->execute($coordinator, $consignment, ['items' => [[
            'stock_consignment_item_id' => $line->id,
            'quantity_good' => '1.000',
            'quantity_damaged' => '1.000',
            'quantity_shortage' => '0.000',
        ]]]);

        $service->execute($coordinator, $consignment, ['items' => [[
            'stock_consignment_item_id' => $line->id,
            'quantity_good' => '3.000',
            'quantity_damaged' => '0.000',
            'quantity_shortage' => '0.000',
        ]]]);

        $this->assertSame(StockConsignment::STATUS_UNDER_REVIEW, $consignment->fresh()->status);
        $this->assertSame('4.000', StockLevel::where('store_id', $destination->id)
            ->where('stock_item_id', $line->stock_item_id)->value('quantity'));
        $this->assertSame('1.000', StockLevel::where('store_id', $destination->id)
            ->where('stock_item_id', $line->stock_item_id)->value('quantity_quarantined'));
    }

    public function test_full_good_receipt_reconciles_and_releases_all_quarantine(): void
    {
        [, , $destination, $item, $consignment, $line] = $this->dispatchedConsignment();
        $coordinator = User::factory()->create(['is_active' => true]);
        $this->assign($coordinator, $destination, StoreStaff::ROLE_COORDINATOR);

        app(AcknowledgeStockConsignment::class)->execute($coordinator, $consignment, [
            'items' => [[
                'stock_consignment_item_id' => $line->id,
                'quantity_good' => '5.000',
                'quantity_damaged' => '0.000',
                'quantity_shortage' => '0.000',
            ]],
        ]);

        $level = StockLevel::where('store_id', $destination->id)
            ->where('stock_item_id', $item->id)->firstOrFail();

        $this->assertSame(StockConsignment::STATUS_RECONCILED, $consignment->fresh()->status);
        $this->assertSame('5.000', $level->quantity);
        $this->assertSame('0.000', $level->quantity_quarantined);
    }

    public function test_acknowledgement_cannot_exceed_outstanding_quantity(): void
    {
        [, , $destination, , $consignment, $line] = $this->dispatchedConsignment();
        $coordinator = User::factory()->create(['is_active' => true]);
        $this->assign($coordinator, $destination, StoreStaff::ROLE_COORDINATOR);
        $service = app(AcknowledgeStockConsignment::class);

        $service->execute($coordinator, $consignment, ['items' => [[
            'stock_consignment_item_id' => $line->id,
            'quantity_good' => '4.000',
            'quantity_damaged' => '0.000',
            'quantity_shortage' => '0.000',
        ]]]);

        try {
            $service->execute($coordinator, $consignment, ['items' => [[
                'stock_consignment_item_id' => $line->id,
                'quantity_good' => '2.000',
                'quantity_damaged' => '0.000',
                'quantity_shortage' => '0.000',
            ]]]);
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
            $this->assertDatabaseCount('stock_consignment_acknowledgements', 1);
            return;
        }

        $this->fail('Excess acknowledgement was accepted.');
    }

    private function dispatchedConsignment(): array
    {
        $sourceCoordinator = User::factory()->create(['is_active' => true]);
        $source = $this->makeStore();
        $destination = $this->makeStore();
        $this->assign($sourceCoordinator, $source, StoreStaff::ROLE_COORDINATOR);
        $item = $this->makeItem($source, $sourceCoordinator, 'Acknowledgement stock');
        $this->makeLevel($source, $item, '10.000', '10.000000', '100.00');

        $consignment = app(DispatchStockConsignment::class)->execute(
            $sourceCoordinator,
            $source,
            $destination,
            ['items' => [[
                'stock_item_id' => $item->id,
                'quantity' => '5.000',
            ]]]
        );

        return [$sourceCoordinator, $source, $destination, $item, $consignment, $consignment->items->firstOrFail()];
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
