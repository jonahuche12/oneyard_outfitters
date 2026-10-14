<?php

namespace Tests\Feature\Earnings;

use App\Actions\Earnings\CreateOrderEarningsPool;
use App\Models\Contact;
use App\Models\Order;
use App\Models\OrderEarningsPool;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class CreateOrderEarningsPoolTest extends TestCase
{
    use RefreshDatabase;

    private function deliveredOrder(string $total): Order
    {
        $organization = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $creator = User::factory()->create(['is_active' => true]);

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-' . uniqid(),
            'organization_id' => $organization->id,
            'contact_id' => $contact->id,
            'created_by' => $creator->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'expected_delivery_days' => 30,
            'status' => Quotation::STATUS_ACCEPTED,
            'subtotal' => $total,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => $total,
            'terms' => null,
            'notes' => 'Pool rounding test quotation.',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'organization_id' => $organization->id,
            'quotation_id' => $quotation->id,
            'contact_id' => $contact->id,
            'order_date' => now()->toDateString(),
            'expected_delivery_days' => 30,
            'expected_delivery_date' => now()->addDays(30)->toDateString(),
            'status' => Order::STATUS_READY,
            'subtotal' => $total,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => $total,
            'terms' => null,
            'notes' => 'Pool rounding test order.',
        ]);

        $order->update(['status' => Order::STATUS_DELIVERED]);

        return $order->fresh();
    }

    private function pool(Order $order): OrderEarningsPool
    {
        return app(CreateOrderEarningsPool::class)->execute($order);
    }

    public function test_pool_is_five_percent_of_the_order_total(): void
    {
        $pool = $this->pool($this->deliveredOrder('500000.00'));

        $this->assertSame('500000.00', (string) $pool->source_amount);
        $this->assertSame('5.00', (string) $pool->rate_percent);
        $this->assertSame('25000.00', (string) $pool->pool_amount);
        $this->assertSame(OrderEarningsPool::STATUS_PENDING, $pool->status);
    }

    public function test_pool_rounds_half_cents_up_deterministically(): void
    {
        $cases = [
            '10.10' => '0.51',   // 0.505 -> 0.51
            '10.30' => '0.52',   // 0.515 -> 0.52
            '0.10' => '0.01',    // 0.005 -> 0.01
            '0.09' => '0.00',    // 0.0045 -> 0.00
            '33280.00' => '1664.00',
            '100000.00' => '5000.00',
            '0.00' => '0.00',
        ];

        foreach ($cases as $total => $expectedPool) {
            $pool = $this->pool($this->deliveredOrder($total));

            $this->assertSame(
                $expectedPool,
                (string) $pool->pool_amount,
                "Pool for order total {$total}"
            );
        }
    }

    public function test_pool_records_quotation_reference_and_source_snapshot(): void
    {
        $order = $this->deliveredOrder('100000.00');
        $pool = $this->pool($order);

        $this->assertSame($order->quotation_id, $pool->quotation_id);
        $this->assertSame($order->quotation->quotation_number, $pool->quotation_number);
        $this->assertSame('order.total', $pool->source_snapshot['basis']);
        $this->assertSame('100000.00', $pool->source_snapshot['order_total']);
        $this->assertSame('5000.00', $pool->source_snapshot['pool_amount']);
    }

    public function test_repeated_creation_returns_the_same_pool(): void
    {
        $order = $this->deliveredOrder('100000.00');

        $first = $this->pool($order);
        $second = $this->pool($order);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(
            1,
            OrderEarningsPool::query()->where('order_id', $order->id)->count()
        );
    }

    public function test_pool_cannot_be_created_for_an_undelivered_order(): void
    {
        $order = $this->deliveredOrder('100000.00');
        OrderEarningsPool::query()->where('order_id', $order->id)->delete();
        $order->update(['status' => Order::STATUS_READY]);

        $this->expectException(LogicException::class);

        $this->pool($order->fresh());
    }
}
