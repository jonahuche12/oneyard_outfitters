<?php

namespace Tests\Feature\Stock;

use App\Models\StockConsignment;
use App\Models\StockConsignmentAcknowledgement;
use App\Models\StockConsignmentAcknowledgementItem;
use App\Models\StockConsignmentDiscrepancy;
use App\Models\StockConsignmentExpense;
use App\Models\StockConsignmentItem;
use App\Models\StockItem;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockConsignmentModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_consignment_resolves_stores_users_and_item_lines(): void
    {
        [$source, $destination, $user] = $this->context();

        $consignment = $this->makeConsignment($source, $destination, $user);
        $item = $this->makeItem($source, $user);
        $line = $this->makeLine($consignment, $item);

        $fresh = $consignment->fresh();

        $this->assertTrue($fresh->sourceStore->is($source));
        $this->assertTrue($fresh->destinationStore->is($destination));
        $this->assertTrue($fresh->creator->is($user));
        $this->assertTrue($fresh->dispatcher->is($user));
        $this->assertTrue($line->consignment->is($consignment));
        $this->assertTrue($line->item->is($item));
        $this->assertSame([$line->id], $fresh->items->pluck('id')->all());

        $this->assertSame('12500.00', $fresh->total_cost);
        $this->assertSame('18000.00', $fresh->expected_sales_value);
        $this->assertSame('5500.00', $fresh->expected_gross_return);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->dispatched_at);
        $this->assertSame('40.500', $line->fresh()->quantity_dispatched);
        $this->assertSame('308.641975', $line->fresh()->unit_cost);
    }

    public function test_acknowledgements_preserve_multiple_events_and_item_quantities(): void
    {
        [$source, $destination, $user] = $this->context();

        $consignment = $this->makeConsignment($source, $destination, $user);
        $line = $this->makeLine($consignment, $this->makeItem($source, $user));

        $first = $this->makeAcknowledgement($consignment, $user, now()->subHour());
        $firstItem = $this->makeAcknowledgementItem($first, $line, [
            'quantity_good' => '10.000',
            'quantity_damaged' => '1.000',
            'quantity_shortage' => '0.000',
        ]);

        $second = $this->makeAcknowledgement($consignment, $user, now());
        $secondItem = $this->makeAcknowledgementItem($second, $line, [
            'quantity_good' => '20.000',
            'quantity_damaged' => '0.000',
            'quantity_shortage' => '0.000',
        ]);

        $this->assertTrue($first->consignment->is($consignment));
        $this->assertTrue($first->acknowledger->is($user));
        $this->assertTrue($firstItem->acknowledgement->is($first));
        $this->assertTrue($firstItem->consignmentItem->is($line));
        $this->assertSame([$first->id, $second->id], $consignment->fresh()
            ->acknowledgements->sortBy('id')->pluck('id')->all());
        $this->assertCount(2, $line->fresh()->acknowledgementItems);
        $this->assertSame('10.000', $firstItem->fresh()->quantity_good);
        $this->assertSame('1.000', $firstItem->fresh()->quantity_damaged);
        $this->assertSame('20.000', $secondItem->fresh()->quantity_good);
    }

    public function test_discrepancy_resolves_consignment_line_acknowledgement_and_users(): void
    {
        [$source, $destination, $user] = $this->context();

        $consignment = $this->makeConsignment($source, $destination, $user);
        $line = $this->makeLine($consignment, $this->makeItem($source, $user));
        $acknowledgement = $this->makeAcknowledgement($consignment, $user, now());

        $acknowledgementItem = $this->makeAcknowledgementItem(
            $acknowledgement,
            $line,
            [
                'quantity_good' => '30.000',
                'quantity_damaged' => '2.000',
                'quantity_shortage' => '8.500',
            ]
        );

        $discrepancy = StockConsignmentDiscrepancy::query()->create([
            'stock_consignment_item_id' => $line->id,
            'acknowledgement_item_id' => $acknowledgementItem->id,
            'type' => 'shortage',
            'quantity' => '8.500',
            'status' => 'resolved',
            'reported_by' => $user->id,
            'reported_at' => now()->subMinutes(30),
            'resolution' => 'written_off',
            'resolution_reason' => 'Shortage confirmed after investigation',
            'resolved_by' => $user->id,
            'resolved_at' => now(),
        ]);

        $fresh = $discrepancy->fresh();

        $this->assertTrue($fresh->consignmentItem->is($line));
        $this->assertTrue($fresh->acknowledgementItem->is($acknowledgementItem));
        $this->assertTrue($fresh->reporter->is($user));
        $this->assertTrue($fresh->resolver->is($user));
        $this->assertSame([$discrepancy->id], $line->discrepancies->pluck('id')->all());
        $this->assertSame(
            [$discrepancy->id],
            $acknowledgementItem->discrepancies->pluck('id')->all()
        );
        $this->assertSame('8.500', $fresh->quantity);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->reported_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->resolved_at);
    }

    public function test_consignment_expenses_preserve_each_expense_and_recorder(): void
    {
        [$source, $destination, $user] = $this->context();

        $consignment = $this->makeConsignment($source, $destination, $user);

        $transport = $this->makeExpense($consignment, $user, [
            'category' => 'transport',
            'description' => 'Vehicle hire',
            'amount' => '1500.00',
        ]);

        $handling = $this->makeExpense($consignment, $user, [
            'category' => 'handling',
            'description' => 'Loading and unloading',
            'amount' => '500.00',
        ]);

        $this->assertTrue($transport->consignment->is($consignment));
        $this->assertTrue($transport->recorder->is($user));
        $this->assertSame(
            [$transport->id, $handling->id],
            $consignment->fresh()->expenses->sortBy('id')->pluck('id')->all()
        );
        $this->assertSame('1500.00', $transport->fresh()->amount);
        $this->assertSame('500.00', $handling->fresh()->amount);
        $this->assertInstanceOf(
            \Illuminate\Support\Carbon::class,
            $transport->fresh()->incurred_at
        );
    }

    public function test_store_stock_item_and_user_consignment_relationships_are_available(): void
    {
        [$source, $destination, $user] = $this->context();

        $consignment = $this->makeConsignment($source, $destination, $user);
        $item = $this->makeItem($source, $user);
        $line = $this->makeLine($consignment, $item);

        $acknowledgement = $this->makeAcknowledgement($consignment, $user, now());
        $acknowledgementItem = $this->makeAcknowledgementItem(
            $acknowledgement,
            $line,
            ['quantity_good' => '40.500']
        );

        $this->makeExpense($consignment, $user, [
            'category' => 'transport',
            'description' => 'Delivery',
            'amount' => '100.00',
        ]);

        StockConsignmentDiscrepancy::query()->create([
            'stock_consignment_item_id' => $line->id,
            'acknowledgement_item_id' => $acknowledgementItem->id,
            'type' => 'damage',
            'quantity' => '0.000',
            'status' => 'resolved',
            'reported_by' => $user->id,
            'reported_at' => now(),
            'resolution' => 'released',
            'resolution_reason' => 'Inspection completed',
            'resolved_by' => $user->id,
            'resolved_at' => now(),
        ]);

        $this->assertSame(
            [$consignment->id],
            $source->sourceConsignments->pluck('id')->all()
        );
        $this->assertSame(
            [$consignment->id],
            $destination->destinationConsignments->pluck('id')->all()
        );
        $this->assertSame([$line->id], $item->consignmentItems->pluck('id')->all());

        $this->assertSame([$consignment->id], $user->consignmentsCreated->pluck('id')->all());
        $this->assertSame([$consignment->id], $user->consignmentsDispatched->pluck('id')->all());
        $this->assertSame(
            [$acknowledgement->id],
            $user->consignmentAcknowledgements->pluck('id')->all()
        );
        $this->assertCount(1, $user->consignmentDiscrepanciesReported);
        $this->assertCount(1, $user->consignmentDiscrepanciesResolved);
        $this->assertCount(1, $user->consignmentExpensesRecorded);
    }

    private function context(): array
    {
        $source = Store::query()->create([
            'name' => 'General '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
            'type' => Store::TYPE_WAREHOUSE,
        ]);

        $destination = Store::query()->create([
            'name' => 'Destination '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
            'type' => Store::TYPE_STORE,
        ]);

        return [$source, $destination, User::factory()->create()];
    }

    private function makeConsignment(
        Store $source,
        Store $destination,
        User $user
    ): StockConsignment {
        return StockConsignment::query()->create([
            'reference' => 'SC-'.strtoupper(Str::random(12)),
            'source_store_id' => $source->id,
            'destination_store_id' => $destination->id,
            'status' => StockConsignment::STATUS_DISPATCHED,
            'dispatched_at' => now(),
            'dispatched_by' => $user->id,
            'total_cost' => '12500.00',
            'expected_sales_value' => '18000.00',
            'expected_gross_return' => '5500.00',
            'created_by' => $user->id,
        ]);
    }

    private function makeItem(Store $source, User $user): StockItem
    {
        return StockItem::query()->create([
            'store_id' => $source->id,
            'description' => 'Ankara '.Str::random(6),
            'unit' => StockItem::UNIT_YARD,
            'unit_cost' => '308.641975',
            'created_by' => $user->id,
        ]);
    }

    private function makeLine(
        StockConsignment $consignment,
        StockItem $item
    ): StockConsignmentItem {
        return StockConsignmentItem::query()->create([
            'stock_consignment_id' => $consignment->id,
            'stock_item_id' => $item->id,
            'quantity_dispatched' => '40.500',
            'unit_cost' => '308.641975',
            'line_cost' => '12500.00',
            'market_price' => '444.44',
            'expected_sales_value' => '18000.00',
            'expected_gross_return' => '5500.00',
        ]);
    }

    private function makeAcknowledgement(
        StockConsignment $consignment,
        User $user,
        $at
    ): StockConsignmentAcknowledgement {
        return StockConsignmentAcknowledgement::query()->create([
            'stock_consignment_id' => $consignment->id,
            'acknowledged_by' => $user->id,
            'acknowledged_at' => $at,
        ]);
    }

    private function makeAcknowledgementItem(
        StockConsignmentAcknowledgement $acknowledgement,
        StockConsignmentItem $line,
        array $overrides = []
    ): StockConsignmentAcknowledgementItem {
        return StockConsignmentAcknowledgementItem::query()->create(array_merge([
            'acknowledgement_id' => $acknowledgement->id,
            'stock_consignment_item_id' => $line->id,
            'quantity_good' => '0.000',
            'quantity_damaged' => '0.000',
            'quantity_shortage' => '0.000',
        ], $overrides));
    }

    private function makeExpense(
        StockConsignment $consignment,
        User $user,
        array $overrides
    ): StockConsignmentExpense {
        return StockConsignmentExpense::query()->create(array_merge([
            'stock_consignment_id' => $consignment->id,
            'category' => 'other',
            'description' => 'Consignment expense',
            'amount' => '100.00',
            'incurred_at' => now(),
            'recorded_by' => $user->id,
        ], $overrides));
    }
}
