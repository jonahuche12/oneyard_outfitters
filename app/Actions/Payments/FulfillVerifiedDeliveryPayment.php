<?php

namespace App\Actions\Payments;

use App\Models\Delivery;
use App\Models\DeliveryActivationRecipient;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\QuotationRecipient;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FulfillVerifiedDeliveryPayment
{
    public function execute(
        PaymentTransaction $transaction
    ): Payment {
        return DB::transaction(function () use ($transaction): Payment {
            $lockedTransaction = PaymentTransaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTransaction->status !== 'paid') {
                throw new RuntimeException(
                    'The delivery payment has not been verified.'
                );
            }

            if ($lockedTransaction->delivery_id === null) {
                throw new RuntimeException(
                    'This payment transaction is not linked to a Delivery.'
                );
            }

            $delivery = Delivery::query()
                ->with([
                    'order.quotation',
                    'order.contact',
                    'activationRecipients',
                ])
                ->whereKey($lockedTransaction->delivery_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($delivery->status !== Delivery::STATUS_PENDING) {
                throw new RuntimeException(
                    'This Delivery is no longer available for payment activation.'
                );
            }

            $order = $delivery->order;
            $quotation = $order->quotation;

            if ($quotation === null) {
                throw new RuntimeException(
                    'The Order quotation could not be found.'
                );
            }

            $quotationRecipient = QuotationRecipient::query()
                ->where('quotation_id', $quotation->id)
                ->where('contact_id', $order->contact_id)
                ->first();

            if ($quotationRecipient === null) {
                throw new RuntimeException(
                    'The quotation recipient could not be found.'
                );
            }

            if (
                (int) $lockedTransaction->quotation_recipient_id
                !== (int) $quotationRecipient->id
            ) {
                throw new RuntimeException(
                    'The payment transaction does not belong to the Order recipient.'
                );
            }

            /*
             * Idempotency:
             *
             * If the Paystack callback is received more than once,
             * do not create another Payment or activate anything twice.
             */
            $existingPayment = Payment::query()
                ->where(
                    'payment_transaction_id',
                    $lockedTransaction->id
                )
                ->first();

            if ($existingPayment) {
                $this->activateDelivery(
                    $delivery,
                    $lockedTransaction
                );

                return $existingPayment;
            }

            $totalPaid = (float) $quotation->payments()
                ->where('status', 'completed')
                ->sum('amount');

            /*
             * The current transaction has already been marked paid,
             * but it does not yet exist in the completed Payment ledger.
             */
            $expectedBalance = max(
                0,
                round(
                    (float) $order->total - $totalPaid,
                    2
                )
            );

            $transactionAmount = round(
                (float) $lockedTransaction->amount,
                2
            );

            if (
                abs($transactionAmount - $expectedBalance)
                > 0.01
            ) {
                throw new RuntimeException(
                    'The verified payment amount does not match the outstanding Order balance.'
                );
            }

            $payment = Payment::create([
                'organization_id' => $order->organization_id,
                'quotation_id' => $quotation->id,
                'quotation_recipient_id' => $quotationRecipient->id,
                'payment_transaction_id' => $lockedTransaction->id,
                'amount' => $transactionAmount,
                'payment_method' => 'paystack',
                'reference' => $lockedTransaction->reference,
                'status' => 'completed',
                'paid_at' => $lockedTransaction->paid_at ?? now(),
                'notes' => 'Delivery balance paid through Paystack.',
            ]);

            /*
             * Pay Now is considered activated only after:
             *
             * 1. Paystack verification succeeded.
             * 2. The verified amount matched the outstanding balance.
             * 3. The completed Payment was recorded.
             */
            $this->activateDelivery(
                $delivery,
                $lockedTransaction
            );

            return $payment;
        });
    }

    private function activateDelivery(
        Delivery $delivery,
        PaymentTransaction $transaction
    ): void {
        $delivery->update([
            'payment_arrangement' => Delivery::PAYMENT_ARRANGEMENT_PAY_NOW,
            'activated_at' => now(),
        ]);

        $recipient = DeliveryActivationRecipient::query()
            ->where('delivery_id', $delivery->id)
            ->where(
                'contact_id',
                $delivery->order->contact_id
            )
            ->lockForUpdate()
            ->first();

        if ($recipient === null) {
            throw new RuntimeException(
                'The Delivery activation recipient could not be found.'
            );
        }

        $recipient->update([
            'status' => DeliveryActivationRecipient::STATUS_ACTIVATED,
            'activated_at' => now(),
        ]);
    }
}
