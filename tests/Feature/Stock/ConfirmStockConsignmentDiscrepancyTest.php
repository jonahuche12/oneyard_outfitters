<?php

namespace Tests\Feature\Stock;

use App\Actions\Stock\AcknowledgeStockConsignment;
use App\Actions\Stock\ConfirmStockConsignmentDiscrepancy;
use App\Actions\Stock\DispatchStockConsignment;
use App\Models\StockConsignment;
use App\Models\StockConsignmentDiscrepancy;
use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ConfirmStockConsignmentDiscrepancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_damaged_stock_write_off_reduces_quarantine_and_records_audited_movement(): void
    {
        [$source, $destination, $item, $consignment, $line, $coordinator] =
            $this->discrepancyScenario();

        $discrepancy = $this->discrepancy($line, 'damaged');

        $resolved = app(ConfirmStockConsignmentDiscrepancy::class)->execute(
            $coordinator,
            $discrepancy,
            ['resolution_reason' => 'Supplier confirmed the damage report.']
        );

        $level = $this->level($destination, $item);

        $this->assertSame('1.000', $level->quantity_quarantined);
        $this->assertSame('10.00', $level->quarantine_value);
        $this->assertSame('2.000', $level->quantity);
        $this->assertSame('20.00', $level->inventory_value);

        $this->assertSame('resolved', $resolved->status);
        $this->assertSame('written_off', $resolved->resolution);
        $this->assertSame('Supplier confirmed the damage report.', $resolved->resolution_reason);
        $this->assertSame($coordinator->id, $resolved->resolved_by);
        $this->assertNotNull($resolved->resolved_at);

        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $item->id,
            'store_id' => $destination->id,
            'type' => StockMovement::TYPE_CONSIGNMENT_WRITE_OFF,
            'quantity' => '1.000',
            'unit_cost' => '10.000000',
            'movement_value' => '10.00',
            'source_type' => StockConsignmentDiscrepancy::class,
            'source_id' => $discrepancy->id,
            'created_by' => $coordinator->id,
        ]);

        $movement = StockMovement::query()
            ->where('source_type', StockConsignmentDiscrepancy::class)
            ->where('source_id', $discrepancy->id)
            ->firstOrFail();

        $this->assertStringContainsString(
            'Supplier confirmed the damage report.',
            $movement->reason
        );

        $this->assertSame($source->id, $item->fresh()->store_id);
        $this->assertSame(StockConsignment::STATUS_UNDER_REVIEW, $consignment->fresh()->status);
    }

    public function test_shortage_confirmation_resolves_discrepancy_without_an_additional_stock_movement(): void
    {
        [$source, $destination, $item, $consignment, $line, $coordinator] =
            $this->discrepancyScenario('shortage_only');

        $discrepancy = $this->discrepancy($line, 'shortage');
        $movementCount = StockMovement::query()->count();
        $levelBefore = $this->level($destination, $item);

        $resolved = app(ConfirmStockConsignmentDiscrepancy::class)->execute(
            $coordinator,
            $discrepancy,
            ['resolution_reason' => 'Receiving team confirmed the missing quantity.']
        );

        $levelAfter = $this->level($destination, $item);

        $this->assertSame('resolved', $resolved->status);
        $this->assertSame('confirmed', $resolved->resolution);
        $this->assertSame('Receiving team confirmed the missing quantity.', $resolved->resolution_reason);
        $this->assertSame($coordinator->id, $resolved->resolved_by);

        $this->assertSame($movementCount, StockMovement::query()->count());
        $this->assertSame($levelBefore->quantity, $levelAfter->quantity);
        $this->assertSame($levelBefore->quantity_quarantined, $levelAfter->quantity_quarantined);
        $this->assertSame($levelBefore->inventory_value, $levelAfter->inventory_value);
        $this->assertSame($levelBefore->quarantine_value, $levelAfter->quarantine_value);

        $this->assertSame($source->id, $item->fresh()->store_id);
        $this->assertSame(StockConsignment::STATUS_RECONCILED, $consignment->fresh()->status);
    }

    public function test_only_sending_store_coordinator_can_confirm_discrepancy(): void
    {
        [$source, $destination, , , $line, $sourceCoordinator, $destinationCoordinator] =
            $this->discrepancyScenario();

        $discrepancy = $this->discrepancy($line, 'damaged');

        $sourceAssistant = User::factory()->create(['is_active' => true]);
        $this->assign($sourceAssistant, $source, StoreStaff::ROLE_ASSISTANT);

        $unassignedAdministrator = User::factory()->create(['is_active' => true]);
        $movementCount = StockMovement::query()->count();

        foreach ([$sourceAssistant, $destinationCoordinator, $unassignedAdministrator] as $actor) {
            try {
                app(ConfirmStockConsignmentDiscrepancy::class)->execute(
                    $actor,
                    $discrepancy,
                    ['resolution_reason' => 'Attempted unauthorized confirmation.']
                );
                $this->fail('An unauthorized user confirmed a discrepancy.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }

        $this->assertSame('open', $discrepancy->fresh()->status);
        $this->assertSame('2.000', $this->level($destination, $line->item)->quantity_quarantined);
        $this->assertSame($movementCount, StockMovement::query()->count());
    }

    public function test_repeated_damaged_confirmation_cannot_create_a_second_write_off(): void
    {
        [, $destination, $item, , $line, $coordinator] =
            $this->discrepancyScenario();

        $discrepancy = $this->discrepancy($line, 'damaged');
        $service = app(ConfirmStockConsignmentDiscrepancy::class);

        $service->execute($coordinator, $discrepancy, [
            'resolution_reason' => 'First confirmation.',
        ]);

        $movementCount = StockMovement::query()->count();
        $levelAfterFirstConfirmation = $this->level($destination, $item);

        try {
            $service->execute($coordinator, $discrepancy, [
                'resolution_reason' => 'Attempted second confirmation.',
            ]);
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('discrepancy', $exception->errors());
            $this->assertSame($movementCount, StockMovement::query()->count());
            $this->assertSame(
                $levelAfterFirstConfirmation->quantity_quarantined,
                $this->level($destination, $item)->quantity_quarantined
            );
            return;
        }

        $this->fail('A resolved discrepancy was confirmed a second time.');
    }

    public function test_confirmation_requires_a_non_empty_resolution_reason(): void
    {
        [, $destination, $item, , $line, $coordinator] =
            $this->discrepancyScenario();

        $discrepancy = $this->discrepancy($line, 'damaged');
        $movementCount = StockMovement::query()->count();

        try {
            app(ConfirmStockConsignmentDiscrepancy::class)->execute(
                $coordinator,
                $discrepancy,
                ['resolution_reason' => '   ']
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('resolution_reason', $exception->errors());
            $this->assertSame('open', $discrepancy->fresh()->status);
            $this->assertSame($movementCount, StockMovement::query()->count());
            $this->assertSame('2.000', $this->level($destination, $item)->quantity_quarantined);
            return;
        }

        $this->fail('A discrepancy was confirmed without a meaningful reason.');
    }

    private function discrepancyScenario(string $mode = 'damaged_and_shortage'): array
    {
        $sourceCoordinator = User::factory()->create(['is_active' => true]);
        $source = $this->makeStore();
        $destination = $this->makeStore();

        $this->assign($sourceCoordinator, $source, StoreStaff::ROLE_COORDINATOR);

        $item = StockItem::query()->create([
            'store_id' => $source->id,
            'description' => 'Discrepancy test stock',
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

        $destinationCoordinator = User::factory()->create(['is_active' => true]);
        $this->assign($destinationCoordinator, $destination, StoreStaff::ROLE_COORDINATOR);

        $quantities = $mode === 'shortage_only'
            ? ['quantity_good' => '3.000', 'quantity_damaged' => '0.000', 'quantity_shortage' => '2.000']
            : ['quantity_good' => '2.000', 'quantity_damaged' => '1.000', 'quantity_shortage' => '1.000'];

        app(AcknowledgeStockConsignment::class)->execute(
            $destinationCoordinator,
            $consignment,
            ['items' => [array_merge([
                'stock_consignment_item_id' => $line->id,
            ], $quantities)]]
        );

        return [$source, $destination, $item, $consignment->fresh(), $line->fresh(), $sourceCoordinator, $destinationCoordinator];
    }

    private function discrepancy($line, string $type): StockConsignmentDiscrepancy
    {
        return StockConsignmentDiscrepancy::query()
            ->where('stock_consignment_item_id', $line->id)
            ->where('type', $type)
            ->firstOrFail();
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

    private function level(Store $store, StockItem $item): StockLevel
    {
        return StockLevel::query()
            ->where('store_id', $store->id)
            ->where('stock_item_id', $item->id)
            ->firstOrFail();
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
