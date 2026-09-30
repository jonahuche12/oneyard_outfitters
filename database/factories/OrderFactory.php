<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_number' => 'ORD-' . fake()->unique()->numerify('######'),
            'organization_id' => Organization::factory(),
            'quotation_id' => Quotation::factory(),
            'contact_id' => Contact::factory(),
            'order_date' => now()->toDateString(),
            'status' => Order::STATUS_PENDING,
            'subtotal' => 30000,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 30000,
            'terms' => null,
            'notes' => null,
        ];
    }
}
