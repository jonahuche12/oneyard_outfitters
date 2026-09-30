<?php

namespace App\Actions\Payments;

use App\Models\Order;
use App\Models\Quotation;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Mail\OrderCreated;
use App\Mail\PaymentConfirmed;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FulfillVerifiedQuotationPayment
{
    public function execute(PaymentTransaction $transaction): Order
    {
        return DB::transaction(function () use ($transaction): Order {
            $transaction = PaymentTransaction::query()
                ->with([
                    'quotationRecipient.quotation.items',
                    'quotationRecipient.quotation.organization',
                    'quotationRecipient.contact',
                ])
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            if ($transaction->status !== 'paid') {
                throw new RuntimeException(
                    'Only a verified paid transaction can be fulfilled.'
                );
            }

            $recipient = $transaction->quotationRecipient;
            $quotation = $recipient->quotation;

            if ($recipient->response_status !== 'accepted') {
                throw new RuntimeException(
                    'Only an accepted quotation recipient can be fulfilled.'
                );
            }

            if ($quotation->status !== Quotation::STATUS_ACCEPTED) {
                throw new RuntimeException(
                    'Only an accepted quotation can be fulfilled.'
                );
            }

            $expectedAmount = (float) $recipient->payment_amount;
            $transactionAmount = (float) $transaction->amount;

            if (abs($transactionAmount - $expectedAmount) > 0.01) {
                throw new RuntimeException(
                    'The verified payment amount does not match the recipient payment amount.'
                );
            }

            $existingOrder = Order::query()
                ->where('quotation_id', $quotation->id)
                ->first();

            if ($existingOrder) {
                return $existingOrder->load('items');
            }

            $payment = Payment::query()
                ->where('reference', $transaction->reference)
                ->first();

            if (! $payment) {
                $payment = Payment::create([
                    'organization_id' => $quotation->organization_id,
                    'quotation_id' => $quotation->id,
                    'quotation_recipient_id' => $recipient->id,
                    'payment_transaction_id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'payment_method' => $transaction->gateway,
                    'reference' => $transaction->reference,
                    'status' => 'completed',
                    'paid_at' => $transaction->paid_at ?? now(),
                ]);
            }

            $orderDate = now();

            $order = Order::create([
                'order_number' => 'TMP-' . uniqid('', true),
                'organization_id' => $quotation->organization_id,
                'quotation_id' => $quotation->id,
                'contact_id' => $quotation->contact_id,
                'order_date' => $orderDate->toDateString(),
                'expected_delivery_days' => $quotation->expected_delivery_days,
                'expected_delivery_date' => $orderDate
                    ->copy()
                    ->addDays($quotation->expected_delivery_days)
                    ->toDateString(),
                'status' => Order::STATUS_PENDING,
                'subtotal' => $quotation->subtotal,
                'discount' => $quotation->discount,
                'additional_charges' => $quotation->additional_charges,
                'total' => $quotation->total,
                'terms' => $quotation->terms,
                'notes' => $quotation->notes,
            ]);

            $order->update([
                'order_number' => 'ORD-' . str_pad(
                    (string) $order->id,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),
            ]);

            foreach ($quotation->items as $quotationItem) {
                $order->items()->create([
                    'quotation_item_id' => $quotationItem->id,
                    'product_specification_id' =>
                        $quotationItem->product_specification_id,
                    'item_name' => $quotationItem->item_name,
                    'description' => $quotationItem->description,
                    'quantity' => $quotationItem->quantity,
                    'unit' => $quotationItem->unit,
                    'unit_price' => $quotationItem->unit_price,
                    'line_total' => $quotationItem->line_total,
                    'sort_order' => $quotationItem->sort_order,
                ]);
            }

            $order = $order->fresh([
                'items',
                'quotation',
                'organization',
            ]);

            $payment = $payment->fresh([
                'quotation',
                'organization',
                'quotationRecipient',
            ]);

            DB::afterCommit(function () use ($payment, $order): void {
                $customerEmail = $payment->quotationRecipient?->email;

                if ($customerEmail) {
                    Mail::to($customerEmail)
                        ->queue(new PaymentConfirmed($payment));
                }

                User::query()
                    ->where('is_active', true)
                    ->whereHas(
                        'roles.permissions',
                        fn ($query) => $query->where('slug', 'orders.view')
                    )
                    ->get()
                    ->each(function (User $user) use ($order): void {
                        Mail::to($user->email)
                            ->queue(new OrderCreated($order));
                    });
            });

            return $order;
        });
    }
}
