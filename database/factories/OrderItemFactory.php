<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'quotation_item_id' => null,
            'product_specification_id' => null,
            'item_name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'quantity' => 1,
            'unit' => 'piece',
            'unit_price' => 30000,
            'line_total' => 30000,
            'sort_order' => 0,
        ];
    }
}
