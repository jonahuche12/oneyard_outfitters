<?php

namespace Tests\Feature\Stores;

use App\Models\Customer;
use App\Models\Store;
use App\Models\StockSale;
use App\Models\StockSalePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogicException;
use Tests\TestCase;

class StockSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_tables_have_the_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('stock_sales', [
            'id', 'store_id', 'sale_number', 'customer_id',
            'customer_name', 'customer_phone', 'customer_email',
            'total_amount', 'sold_at', 'created_by',
            'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('stock_sale_items', [
            'id', 'stock_sale_id', 'stock_item_id', 'quantity',
            'unit_selling_price', 'line_total',
            'unit_cost_snapshot', 'line_cost_snapshot',
            'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('stock_sale_payments', [
            'id', 'stock_sale_id', 'amount', 'payment_method',
            'reference', 'paid_at', 'notes', 'received_by',
            'created_at', 'updated_at',
        ]));
    }

    public function test_store_sale_customer_creator_and_payment_relationships_work(): void
    {
        $store = $this->makeStore();
        $customer = $this->makeCustomer($store);
        $creator = User::factory()->create();
        $receiver = User::factory()->create();

        $sale = $this->makeSale($store, $creator, $customer);

        $payment = $sale->payments()->create([
            'amount' => 8000,
            'payment_method' => 'cash',
            'paid_at' => now(),
            'received_by' => $receiver->id,
        ]);

        $this->assertTrue($sale->store->is($store));
        $this->assertTrue($sale->customer->is($customer));
        $this->assertTrue($sale->creator->is($creator));
        $this->assertTrue($store->sales()->whereKey($sale->id)->exists());
        $this->assertTrue($sale->payments()->whereKey($payment->id)->exists());
        $this->assertTrue($payment->receiver->is($receiver));
        $this->assertSame('20000.00', $sale->total_amount);
        $this->assertSame('8000.00', $payment->amount);
    }

    public function test_an_anonymous_sale_can_have_no_customer(): void
    {
        $sale = $this->makeSale(
            $this->makeStore(),
            User::factory()->create(),
            null
        );

        $this->assertNull($sale->customer_id);
        $this->assertNull($sale->customer);
        $this->assertDatabaseHas('stock_sales', [
            'id' => $sale->id,
            'customer_id' => null,
        ]);
    }

    public function test_sale_number_must_be_unique_within_a_store_only(): void
    {
        $firstStore = $this->makeStore();
        $secondStore = $this->makeStore();
        $creator = User::factory()->create();

        $this->makeSale($firstStore, $creator, null, 'SALE-001');
        $this->makeSale($secondStore, $creator, null, 'SALE-001');

        $this->assertSame(2, StockSale::query()
            ->where('sale_number', 'SALE-001')
            ->count());
    }

    public function test_recorded_sale_cannot_be_updated_or_deleted(): void
    {
        $sale = $this->makeSale(
            $this->makeStore(),
            User::factory()->create(),
            null
        );

        try {
            $sale->update(['total_amount' => 100]);
            $this->fail('Expected updating a recorded sale to be rejected.');
        } catch (LogicException $exception) {
            $this->assertSame('Recorded sales are immutable.', $exception->getMessage());
        }

        try {
            $sale->delete();
            $this->fail('Expected deleting a recorded sale to be rejected.');
        } catch (LogicException $exception) {
            $this->assertSame('Recorded sales cannot be deleted.', $exception->getMessage());
        }

        $this->assertDatabaseHas('stock_sales', [
            'id' => $sale->id,
            'total_amount' => 20000,
        ]);
    }

    public function test_recorded_payment_cannot_be_updated_or_deleted(): void
    {
        $sale = $this->makeSale(
            $this->makeStore(),
            User::factory()->create(),
            null
        );

        $payment = $sale->payments()->create([
            'amount' => 5000,
            'payment_method' => 'cash',
            'paid_at' => now(),
            'received_by' => $sale->created_by,
        ]);

        try {
            $payment->update(['amount' => 1000]);
            $this->fail('Expected updating a payment to be rejected.');
        } catch (LogicException $exception) {
            $this->assertSame('Payment entries are immutable.', $exception->getMessage());
        }

        try {
            $payment->delete();
            $this->fail('Expected deleting a payment to be rejected.');
        } catch (LogicException $exception) {
            $this->assertSame('Payment entries cannot be deleted.', $exception->getMessage());
        }

        $this->assertDatabaseHas('stock_sale_payments', [
            'id' => $payment->id,
            'amount' => 5000,
        ]);
    }

    private function makeStore(): Store
    {
        return Store::query()->create([
            'name' => 'Store '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
        ]);
    }

    private function makeCustomer(Store $store): Customer
    {
        return Customer::query()->create([
            'store_id' => $store->id,
            'customer_code' => 'CUS-'.strtoupper(Str::random(8)),
            'name' => 'Customer '.Str::random(6),
            'phone' => '08012345678',
        ]);
    }

    private function makeSale(
        Store $store,
        User $creator,
        ?Customer $customer,
        ?string $saleNumber = null
    ): StockSale {
        return StockSale::query()->create([
            'store_id' => $store->id,
            'sale_number' => $saleNumber ?? 'SALE-'.strtoupper(Str::random(8)),
            'customer_id' => $customer?->id,
            'customer_name' => $customer?->name,
            'customer_phone' => $customer?->phone,
            'total_amount' => 20000,
            'sold_at' => now(),
            'created_by' => $creator->id,
        ]);
    }
}
