<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Quotation;
use App\Models\QuotationRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'quotation_id' => Quotation::factory(),
            'quotation_recipient_id' => QuotationRecipient::factory(),
            'payment_transaction_id' => PaymentTransaction::factory(),
            'amount' => 30000,
            'payment_method' => 'paystack',
            'reference' => 'OY-PAY-' . fake()->unique()->numerify('######'),
            'status' => 'completed',
            'paid_at' => now(),
            'notes' => null,
        ];
    }
}
