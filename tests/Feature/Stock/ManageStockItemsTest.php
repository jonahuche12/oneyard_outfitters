<?php

namespace Tests\Feature\Stock;

use App\Actions\Stock\CreateStockItem;
use App\Actions\Stock\UpdateStockItem;
use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ManageStockItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_coordinator_registers_stock_with_opening_balance_and_ledger_entry(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $item = app(CreateStockItem::class)->execute($coordinator, $store, [
            'description' => '  Blue Ankara  ',
            'category' => 'Fabric',
            'grade' => 'Premium',
            'unit' => StockItem::UNIT_YARD,
            'quantity' => '12.500',
            'unit_cost' => '125.500000',
        ]);

        $this->assertSame($store->id, $item->store_id);
        $this->assertSame('Blue Ankara', $item->description);
        $this->assertSame($coordinator->id, $item->created_by);

        $this->assertDatabaseHas('stock_levels', [
            'stock_item_id' => $item->id,
            'store_id' => $store->id,
            'quantity' => '12.500',
            'average_unit_cost' => '125.500000',
            'inventory_value' => '1568.75',
            'quantity_quarantined' => 0,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $item->id,
            'store_id' => $store->id,
            'type' => StockMovement::TYPE_OPENING_STOCK,
            'quantity' => '12.500',
            'unit_cost' => '125.500000',
            'movement_value' => '1568.75',
            'source_type' => StockItem::class,
            'source_id' => $item->id,
            'created_by' => $coordinator->id,
        ]);

        $this->assertDatabaseHas('stock_items', [
            'id' => $item->id,
            'store_id' => $store->id,
            'intake_id' => null,
        ]);

        $this->assertDatabaseCount('intakes', 0);
    }

    public function test_coordinator_can_edit_item_details_without_changing_inventory_or_ledger(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $item = app(CreateStockItem::class)->execute($coordinator, $store, [
            'description' => 'Original fabric',
            'category' => 'Fabric',
            'grade' => 'Standard',
            'unit' => StockItem::UNIT_YARD,
            'quantity' => '12.500',
            'unit_cost' => '125.500000',
        ]);

        $level = StockLevel::query()->where('stock_item_id', $item->id)->firstOrFail();
        $movement = StockMovement::query()->where('stock_item_id', $item->id)->firstOrFail();

        app(UpdateStockItem::class)->execute($coordinator, $item, [
            'description' => 'Updated fabric',
            'category' => 'Premium fabric',
            'grade' => 'Premium',
            'suggested_price' => '250.00',
            'current_market_price' => '275.00',
        ]);

        $this->assertDatabaseHas('stock_items', [
            'id' => $item->id,
            'store_id' => $store->id,
            'description' => 'Updated fabric',
            'category' => 'Premium fabric',
            'grade' => 'Premium',
            'suggested_price' => '250.00',
            'current_market_price' => '275.00',
        ]);

        $this->assertDatabaseHas('stock_levels', [
            'id' => $level->id,
            'quantity' => $level->quantity,
            'average_unit_cost' => $level->average_unit_cost,
            'inventory_value' => $level->inventory_value,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'id' => $movement->id,
            'type' => StockMovement::TYPE_OPENING_STOCK,
            'quantity' => $movement->quantity,
            'unit_cost' => $movement->unit_cost,
            'movement_value' => $movement->movement_value,
        ]);

        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_assistant_cannot_register_or_edit_stock_item_master(): void
    {
        $assistant = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($assistant, $store, StoreStaff::ROLE_ASSISTANT);

        $this->expectException(AuthorizationException::class);

        app(CreateStockItem::class)->execute($assistant, $store, [
            'description' => 'Cotton lining',
            'unit' => StockItem::UNIT_YARD,
            'quantity' => '10',
            'unit_cost' => '50',
        ]);
    }

    public function test_coordinator_cannot_register_stock_for_another_store(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $assignedStore = $this->makeStore();
        $otherStore = $this->makeStore();
        $this->assign($coordinator, $assignedStore, StoreStaff::ROLE_COORDINATOR);

        $this->expectException(AuthorizationException::class);

        app(CreateStockItem::class)->execute($coordinator, $otherStore, [
            'description' => 'Cotton lining',
            'unit' => StockItem::UNIT_YARD,
            'quantity' => '10',
            'unit_cost' => '50',
        ]);
    }

    public function test_inactive_coordinator_cannot_register_stock(): void
    {
        $coordinator = User::factory()->create(['is_active' => false]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $this->expectException(AuthorizationException::class);

        app(CreateStockItem::class)->execute($coordinator, $store, [
            'description' => 'Cotton lining',
            'unit' => StockItem::UNIT_YARD,
            'quantity' => '10',
            'unit_cost' => '50',
        ]);
    }

    public function test_registration_rejects_zero_opening_quantity(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $this->expectException(ValidationException::class);

        app(CreateStockItem::class)->execute($coordinator, $store, [
            'description' => 'Cotton lining',
            'unit' => StockItem::UNIT_YARD,
            'quantity' => '0',
            'unit_cost' => '50',
        ]);
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
            'assigned_by' => null,
        ]);
    }
}
