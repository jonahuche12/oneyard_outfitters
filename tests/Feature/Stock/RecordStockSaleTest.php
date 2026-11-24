<?php

namespace Tests\Feature\Stock;

use App\Actions\Stock\RecordStockSale;
use App\Actions\Stock\RecordStockSalePayment;
use App\Models\Customer;
use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecordStockSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_payment_above_sale_total_is_split_into_payment_and_tip(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $item = $this->makeItem($store, $coordinator, 'Payment test item');
        $this->makeLevel($store, $item, '5.000', '10.000000', '50.00');

        $sale = app(RecordStockSale::class)->execute($coordinator, $store, [
            'items' => [[
                'stock_item_id' => $item->id,
                'quantity' => '1.000',
                'unit_selling_price' => '20.00',
            ]],
            'initial_payment_amount' => '25.00',
            'initial_payment_method' => 'cash',
        ]);

        $payment = $sale->payments()->firstOrFail();

        $this->assertSame('20.00', $payment->amount);
        $this->assertSame('5.00', $payment->tip_amount);
        $this->assertSame('20.00', $sale->total_amount);
        $this->assertEquals(20, (float) $sale->payments()->sum('amount'));
        $this->assertEquals(5, (float) $sale->payments()->sum('tip_amount'));
    }
    public function test_initial_payment_can_be_marked_paid_in_full_without_entering_amount(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $item = $this->makeItem($store, $coordinator, 'Full payment item');
        $this->makeLevel($store, $item, '5.000', '10.000000', '50.00');

        $sale = app(RecordStockSale::class)->execute($coordinator, $store, [
            'items' => [[
                'stock_item_id' => $item->id,
                'quantity' => '1.000',
                'unit_selling_price' => '20.00',
            ]],
            'initial_payment_full' => true,
            'initial_payment_method' => 'cash',
        ]);

        $payment = $sale->payments()->firstOrFail();

        $this->assertSame('20.00', $payment->amount);
        $this->assertSame('0.00', $payment->tip_amount);
        $this->assertEquals(0, (float) $sale->total_amount - (float) $sale->payments()->sum('amount'));
    }

    public function test_coordinator_can_record_a_multi_item_part_paid_sale_and_deduct_stock(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $customer = $this->makeCustomer($store);

        $itemA = $this->makeItem($store, $coordinator, 'Ankara fabric');
        $itemB = $this->makeItem($store, $coordinator, 'Cotton fabric');

        $this->makeLevel($store, $itemA, '5.000', '10.000000', '50.00');
        $this->makeLevel($store, $itemB, '3.000', '20.000000', '60.00');

        $sale = app(RecordStockSale::class)->execute($coordinator, $store, [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'stock_item_id' => $itemA->id,
                    'quantity' => '2.000',
                    'unit_selling_price' => '20.00',
                ],
                [
                    'stock_item_id' => $itemB->id,
                    'quantity' => '1.000',
                    'unit_selling_price' => '30.00',
                ],
            ],
            'initial_payment_amount' => '40.00',
            'initial_payment_method' => 'cash',
        ]);

        $this->assertSame($store->id, $sale->store_id);
        $this->assertSame($customer->id, $sale->customer_id);
        $this->assertSame($customer->name, $sale->customer_name);
        $this->assertSame($customer->phone, $sale->customer_phone);
        $this->assertSame('70.00', $sale->total_amount);
        $this->assertSame($coordinator->id, $sale->created_by);
        $this->assertCount(2, $sale->items);
        $this->assertCount(1, $sale->payments);
        $this->assertSame('40.00', $sale->payments->first()->amount);

        $this->assertSame('3.000', $this->level($store, $itemA)->quantity);
        $this->assertSame('30.00', $this->level($store, $itemA)->inventory_value);
        $this->assertSame('2.000', $this->level($store, $itemB)->quantity);
        $this->assertSame('40.00', $this->level($store, $itemB)->inventory_value);

        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $itemA->id,
            'type' => \App\Models\StockMovement::TYPE_SALE_OUT,
            'quantity' => '2.000',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $itemB->id,
            'type' => \App\Models\StockMovement::TYPE_SALE_OUT,
            'quantity' => '1.000',
        ]);
    }

    public function test_assistant_can_record_a_fully_paid_anonymous_sale(): void
    {
        $assistant = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($assistant, $store, StoreStaff::ROLE_ASSISTANT);

        $item = $this->makeItem($store, $assistant, 'Walk-in sale item');
        $this->makeLevel($store, $item, '5.000', '10.000000', '50.00');

        $sale = app(RecordStockSale::class)->execute($assistant, $store, [
            'items' => [
                [
                    'stock_item_id' => $item->id,
                    'quantity' => '2.000',
                    'unit_selling_price' => '15.00',
                ],
            ],
            'initial_payment_amount' => '30.00',
            'initial_payment_method' => 'cash',
        ]);

        $this->assertNull($sale->customer_id);
        $this->assertNull($sale->customer_name);
        $this->assertSame('30.00', $sale->total_amount);
        $this->assertSame($assistant->id, $sale->created_by);
        $this->assertSame('30.00', $sale->payments->first()->amount);
        $this->assertSame('3.000', $this->level($store, $item)->quantity);
    }

    public function test_sale_with_outstanding_balance_requires_a_customer(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $item = $this->makeItem($store, $coordinator, 'Credit sale item');
        $this->makeLevel($store, $item, '5.000', '10.000000', '50.00');

        try {
            app(RecordStockSale::class)->execute($coordinator, $store, [
                'items' => [
                    [
                        'stock_item_id' => $item->id,
                        'quantity' => '2.000',
                        'unit_selling_price' => '20.00',
                    ],
                ],
                'initial_payment_amount' => '20.00',
                'initial_payment_method' => 'cash',
            ]);

            $this->fail('A sale with an outstanding balance must require a customer.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('stock_sales', 0);
            $this->assertDatabaseCount('stock_sale_payments', 0);
            $this->assertDatabaseCount('stock_movements', 0);
            $this->assertSame('5.000', $this->level($store, $item)->quantity);
        }
    }

    public function test_unassigned_user_cannot_record_a_sale_for_a_store(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();

        $item = $this->makeItem($store, $user, 'Restricted sale item');
        $this->makeLevel($store, $item, '5.000', '10.000000', '50.00');

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        app(RecordStockSale::class)->execute($user, $store, [
            'items' => [
                [
                    'stock_item_id' => $item->id,
                    'quantity' => '1.000',
                    'unit_selling_price' => '20.00',
                ],
            ],
            'initial_payment_amount' => '20.00',
            'initial_payment_method' => 'cash',
        ]);
    }

    public function test_sale_rejects_customer_belonging_to_another_store(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $otherStore = $this->makeStore();

        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $foreignCustomer = $this->makeCustomer($otherStore);
        $item = $this->makeItem($store, $coordinator, 'Customer boundary item');
        $this->makeLevel($store, $item, '5.000', '10.000000', '50.00');

        try {
            app(RecordStockSale::class)->execute($coordinator, $store, [
                'customer_id' => $foreignCustomer->id,
                'items' => [
                    [
                        'stock_item_id' => $item->id,
                        'quantity' => '1.000',
                        'unit_selling_price' => '20.00',
                    ],
                ],
                'initial_payment_amount' => '20.00',
                'initial_payment_method' => 'cash',
            ]);

            $this->fail('A customer belonging to another store must be rejected.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('stock_sales', 0);
            $this->assertDatabaseCount('stock_sale_payments', 0);
            $this->assertSame('5.000', $this->level($store, $item)->quantity);
        }
    }

    public function test_sale_cannot_consume_quarantined_stock(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $item = $this->makeItem($store, $coordinator, 'Quarantined sale item');

        $level = $this->makeLevel(
            $store,
            $item,
            '1.000',
            '10.000000',
            '10.00'
        );

        $level->update([
            'quantity_quarantined' => '5.000',
            'quarantine_value' => '50.00',
        ]);

        try {
            app(RecordStockSale::class)->execute($coordinator, $store, [
                'items' => [
                    [
                        'stock_item_id' => $item->id,
                        'quantity' => '2.000',
                        'unit_selling_price' => '20.00',
                    ],
                ],
                'initial_payment_amount' => '40.00',
                'initial_payment_method' => 'cash',
            ]);

            $this->fail('Sale must reject quantities above available stock.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('stock_sales', 0);
            $this->assertDatabaseCount('stock_movements', 0);
            $this->assertSame('1.000', $this->level($store, $item)->quantity);
            $this->assertSame(
                '5.000',
                $this->level($store, $item)->quantity_quarantined
            );
        }
    }

    public function test_line_total_can_be_used_to_calculate_unit_selling_price(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $customer = $this->makeCustomer($store);
        $item = $this->makeItem($store, $coordinator, 'Line total pricing item');
        $this->makeLevel($store, $item, '5.000', '10.000000', '50.00');

        $sale = app(RecordStockSale::class)->execute($coordinator, $store, [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'stock_item_id' => $item->id,
                    'quantity' => '2.000',
                    'line_total' => '50.00',
                ],
            ],
        ]);

        $line = $sale->items->firstOrFail();

        $this->assertSame('50.00', $line->line_total);
        $this->assertSame('25.00', $line->unit_selling_price);
        $this->assertSame('50.00', $sale->total_amount);
        $this->assertSame('3.000', $this->level($store, $item)->quantity);
    }

    public function test_mismatched_unit_price_and_line_total_are_rejected(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $item = $this->makeItem($store, $coordinator, 'Mismatched pricing item');
        $this->makeLevel($store, $item, '5.000', '10.000000', '50.00');

        try {
            app(RecordStockSale::class)->execute($coordinator, $store, [
                'items' => [
                    [
                        'stock_item_id' => $item->id,
                        'quantity' => '2.000',
                        'unit_selling_price' => '20.00',
                        'line_total' => '50.00',
                    ],
                ],
                'initial_payment_amount' => '40.00',
                'initial_payment_method' => 'cash',
            ]);

            $this->fail('Mismatched unit price and line total must be rejected.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('stock_sales', 0);
            $this->assertDatabaseCount('stock_movements', 0);
            $this->assertSame('5.000', $this->level($store, $item)->quantity);
        }
    }

    public function test_duplicate_stock_item_lines_are_rejected(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $item = $this->makeItem($store, $coordinator, 'Duplicate line item');
        $this->makeLevel($store, $item, '5.000', '10.000000', '50.00');

        try {
            app(RecordStockSale::class)->execute($coordinator, $store, [
                'items' => [
                    [
                        'stock_item_id' => $item->id,
                        'quantity' => '1.000',
                        'unit_selling_price' => '20.00',
                    ],
                    [
                        'stock_item_id' => $item->id,
                        'quantity' => '1.000',
                        'unit_selling_price' => '20.00',
                    ],
                ],
                'initial_payment_amount' => '40.00',
                'initial_payment_method' => 'cash',
            ]);

            $this->fail('Duplicate stock item lines must be rejected.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('stock_sales', 0);
            $this->assertDatabaseCount('stock_movements', 0);
            $this->assertSame('5.000', $this->level($store, $item)->quantity);
        }
    }

    public function test_sale_rejects_foreign_origin_stock_without_a_balance_at_the_store(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $originStore = $this->makeStore();

        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $foreignItem = $this->makeItem(
            $originStore,
            $coordinator,
            'Foreign-origin item'
        );

        // The item has no StockLevel at the selling store.
        $this->makeLevel(
            $originStore,
            $foreignItem,
            '5.000',
            '10.000000',
            '50.00'
        );

        try {
            app(RecordStockSale::class)->execute($coordinator, $store, [
                'items' => [
                    [
                        'stock_item_id' => $foreignItem->id,
                        'quantity' => '1.000',
                        'unit_selling_price' => '20.00',
                    ],
                ],
                'initial_payment_amount' => '20.00',
                'initial_payment_method' => 'cash',
            ]);

            $this->fail('A store must not sell stock without its own stock balance.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('stock_sales', 0);
            $this->assertDatabaseCount('stock_sale_items', 0);
            $this->assertDatabaseCount('stock_movements', 0);
            $this->assertSame('5.000', $this->level($originStore, $foreignItem)->quantity);
        }
    }

    public function test_invalid_line_prevents_entire_multi_item_sale_and_stock_deduction(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $itemA = $this->makeItem($store, $coordinator, 'First valid item');
        $itemB = $this->makeItem($store, $coordinator, 'Second insufficient item');

        $this->makeLevel($store, $itemA, '5.000', '10.000000', '50.00');
        $this->makeLevel($store, $itemB, '1.000', '20.000000', '20.00');

        try {
            app(RecordStockSale::class)->execute($coordinator, $store, [
                'items' => [
                    [
                        'stock_item_id' => $itemA->id,
                        'quantity' => '2.000',
                        'unit_selling_price' => '20.00',
                    ],
                    [
                        'stock_item_id' => $itemB->id,
                        'quantity' => '2.000',
                        'unit_selling_price' => '30.00',
                    ],
                ],
                'initial_payment_amount' => '100.00',
                'initial_payment_method' => 'cash',
            ]);

            $this->fail('An invalid item line must prevent the entire sale.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('stock_sales', 0);
            $this->assertDatabaseCount('stock_sale_items', 0);
            $this->assertDatabaseCount('stock_sale_payments', 0);
            $this->assertDatabaseCount('stock_movements', 0);

            $this->assertSame('5.000', $this->level($store, $itemA)->quantity);
            $this->assertSame('1.000', $this->level($store, $itemB)->quantity);
        }
    }


    public function test_coordinator_can_record_subsequent_sale_payment(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $sale = $this->makeSaleWithOutstandingBalance($actor, $store);

        $originalTotal = $sale->total_amount;
        $originalMovementCount = \App\Models\StockMovement::query()->count();

        $payment = app(RecordStockSalePayment::class)->execute(
            $actor,
            $store,
            $sale,
            [
                'amount' => '25.00',
                'payment_method' => 'cash',
                'reference' => 'PAY-001',
            ]
        );

        $this->assertDatabaseHas('stock_sale_payments', [
            'id' => $payment->id,
            'stock_sale_id' => $sale->id,
            'amount' => '25.00',
            'payment_method' => 'cash',
            'reference' => 'PAY-001',
            'received_by' => $actor->id,
        ]);

        $this->assertSame($originalTotal, $sale->fresh()->total_amount);
        $this->assertSame($originalMovementCount, \App\Models\StockMovement::query()->count());

        $this->assertEquals(
            25,
            (float) $sale->payments()->sum('amount')
        );
    }

    public function test_assistant_can_record_subsequent_sale_payment(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $sale = $this->makeSaleWithOutstandingBalance(
            $actor,
            $store,
            StoreStaff::ROLE_ASSISTANT
        );

        $payment = app(RecordStockSalePayment::class)->execute(
            $actor,
            $store,
            $sale,
            [
                'amount' => '10.00',
                'payment_method' => 'transfer',
            ]
        );

        $this->assertDatabaseHas('stock_sale_payments', [
            'id' => $payment->id,
            'stock_sale_id' => $sale->id,
            'amount' => '10.00',
            'received_by' => $actor->id,
        ]);
    }

    public function test_payment_above_outstanding_balance_is_split_into_payment_and_tip(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $sale = $this->makeSaleWithOutstandingBalance($actor, $store);

        $payment = app(RecordStockSalePayment::class)->execute(
            $actor,
            $store,
            $sale,
            [
                'amount' => '101.00',
                'payment_method' => 'cash',
            ]
        );

        $this->assertSame('100.00', $payment->amount);
        $this->assertSame('1.00', $payment->tip_amount);
        $this->assertEquals(100, (float) $sale->payments()->sum('amount'));
        $this->assertEquals(1, (float) $sale->payments()->sum('tip_amount'));
        $this->assertEquals(
            0,
            (float) $sale->total_amount - (float) $sale->payments()->sum('amount')
        );
    }

    public function test_tip_can_be_recorded_after_sale_is_fully_paid(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $sale = $this->makeSaleWithOutstandingBalance($actor, $store);

        app(RecordStockSalePayment::class)->execute($actor, $store, $sale, [
            'amount' => '100.00',
            'payment_method' => 'cash',
        ]);

        $tipPayment = app(RecordStockSalePayment::class)->execute(
            $actor,
            $store,
            $sale,
            [
                'amount' => '5.00',
                'payment_method' => 'cash',
            ]
        );

        $this->assertSame('0.00', $tipPayment->amount);
        $this->assertSame('5.00', $tipPayment->tip_amount);
        $this->assertEquals(100, (float) $sale->payments()->sum('amount'));
        $this->assertEquals(5, (float) $sale->payments()->sum('tip_amount'));
    }

    public function test_unassigned_user_cannot_record_sale_payment(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $sale = $this->makeSaleWithOutstandingBalance($actor, $store);

        $unassigned = User::factory()->create(['is_active' => true]);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        app(RecordStockSalePayment::class)->execute(
            $unassigned,
            $store,
            $sale,
            [
                'amount' => '10.00',
                'payment_method' => 'cash',
            ]
        );
    }

    public function test_user_cannot_record_payment_for_sale_from_another_store(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $assignedStore = $this->makeStore();
        $sale = $this->makeSaleWithOutstandingBalance($actor, $assignedStore);

        $otherStore = $this->makeStore();
        $otherActor = User::factory()->create(['is_active' => true]);
        $this->assign($otherActor, $otherStore, StoreStaff::ROLE_COORDINATOR);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        app(RecordStockSalePayment::class)->execute(
            $otherActor,
            $otherStore,
            $sale,
            [
                'amount' => '10.00',
                'payment_method' => 'cash',
            ]
        );
    }

    public function test_payment_amount_must_be_positive(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $sale = $this->makeSaleWithOutstandingBalance($actor, $store);

        try {
            app(RecordStockSalePayment::class)->execute(
                $actor,
                $store,
                $sale,
                [
                    'amount' => '0',
                    'payment_method' => 'cash',
                ]
            );

            $this->fail('Expected zero payment to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }

        $this->assertSame(0, $sale->payments()->count());
    }

    private function makeSaleWithOutstandingBalance(User $actor, Store $store, string $role = StoreStaff::ROLE_COORDINATOR): \App\Models\StockSale
    {
        $this->assign($actor, $store, $role);

        $item = $this->makeItem($store, $actor, 'Payment test stock');
        $this->makeLevel($store, $item, '10.000', '5.000000', '50.00');

        $customer = $this->makeCustomer($store);

        return app(RecordStockSale::class)->execute(
            $actor,
            $store,
            [
                'customer_id' => $customer->id,
                'items' => [
                    [
                        'stock_item_id' => $item->id,
                        'quantity' => '2',
                        'unit_selling_price' => '50.00',
                    ],
                ],
            ]
        );
    }

    private function makeStore(): Store
    {
        $store = Store::query()->create([
            'name' => 'Store '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
            'type' => Store::TYPE_STORE,
            'is_active' => true,
        ]);

        // Sales are only allowed on an open business day.
        $store->dailyCashRecords()->create([
            'business_date' => today()->toDateString(),
            'opening_cash' => '1000.00',
            'opening_bank' => '5000.00',
            'opened_by' => User::factory()->create(['is_active' => true])->id,
        ]);

        return $store;
    }

    private function makeItem(Store $store, User $creator, string $description): StockItem
    {
        return StockItem::query()->create([
            'store_id' => $store->id,
            'description' => $description,
            'category' => 'fabric',
            'unit' => StockItem::UNIT_YARD,
            'is_raw' => false,
            'approval_status' => StockItem::APPROVAL_APPROVED,
            'processing_status' => StockItem::PROCESSING_PENDING,
            'created_by' => $creator->id,
        ]);
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

    private function makeCustomer(Store $store): Customer
    {
        return Customer::query()->create([
            'store_id' => $store->id,
            'customer_code' => 'CUS-'.strtoupper(Str::random(8)),
            'name' => 'Customer '.Str::random(6),
            'phone' => '08012345678',
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
}
