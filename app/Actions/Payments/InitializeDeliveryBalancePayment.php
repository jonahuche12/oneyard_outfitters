<?php

namespace App\Actions\Payments;

use App\Models\Delivery;
use App\Models\PaymentTransaction;
use App\Models\QuotationRecipient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class InitializeDeliveryBalancePayment
{
    public function execute(
        Delivery $delivery,
        string $callbackUrl
    ): PaymentTransaction {
        return DB::transaction(function () use (
            $delivery,
            $callbackUrl
        ): PaymentTransaction {
            $lockedDelivery = Delivery::query()
                ->with([
                    'order.quotation',
                    'order.contact',
                ])
                ->whereKey($delivery->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedDelivery->status !== Delivery::STATUS_PENDING) {
                throw new RuntimeException(
                    'This Delivery is no longer available for payment.'
                );
            }

            if (
                $lockedDelivery->payment_arrangement
                !== Delivery::PAYMENT_ARRANGEMENT_PAY_NOW
            ) {
                throw new RuntimeException(
                    'This Delivery is not configured for Pay Now.'
                );
            }

            $order = $lockedDelivery->order;
            $quotation = $order->quotation;

            if ($quotation === null) {
                throw new RuntimeException(
                    'The Order quotation could not be found.'
                );
            }

            $recipient = QuotationRecipient::query()
                ->where('quotation_id', $quotation->id)
                ->where('contact_id', $order->contact_id)
                ->first();

            if ($recipient === null) {
                throw new RuntimeException(
                    'The quotation recipient could not be found.'
                );
            }

            $totalPaid = (float) $quotation->payments()
                ->where('status', 'completed')
                ->sum('amount');

            $balanceDue = max(
                0,
                round((float) $order->total - $totalPaid, 2)
            );

            if ($balanceDue <= 0.01) {
                throw new RuntimeException(
                    'There is no outstanding balance to pay.'
                );
            }

            $existingTransaction = PaymentTransaction::query()
                ->where('delivery_id', $lockedDelivery->id)
                ->where('status', 'initialized')
                ->latest('id')
                ->first();

            if ($existingTransaction) {
                return $existingTransaction;
            }

            $transaction = $recipient->paymentTransactions()->create([
                'delivery_id' => $lockedDelivery->id,
                'amount' => $balanceDue,
                'reference' => 'DELIVERY-'
                    . $lockedDelivery->id
                    . '-'
                    . Str::upper(Str::random(16)),
                'gateway' => 'paystack',
                'status' => 'initialized',
                'initialized_at' => now(),
            ]);

            $response = Http::withToken(
                config('services.paystack.secret_key')
            )->acceptJson()->post(
                rtrim(config('services.paystack.base_url'), '/')
                . '/transaction/initialize',
                [
                    'email' => $recipient->email,
                    'amount' => (int) round($balanceDue * 100),
                    'reference' => $transaction->reference,
                    'callback_url' => $callbackUrl,
                ]
            );

            if (! $response->successful() || ! $response->json('status')) {
                $transaction->update([
                    'status' => 'failed',
                ]);

                throw new RuntimeException(
                    $response->json(
                        'message',
                        'Unable to initialize Paystack transaction.'
                    )
                );
            }

            $authorizationUrl = $response->json('data.authorization_url');

            if (! is_string($authorizationUrl) || $authorizationUrl === '') {
                $transaction->update([
                    'status' => 'failed',
                ]);

                throw new RuntimeException(
                    'Paystack did not return a payment authorization URL.'
                );
            }

            $transaction->update([
                'gateway_transaction_id' => $response->json('data.id')
                    ? (string) $response->json('data.id')
                    : null,
                'access_code' => $response->json('data.access_code'),
                'authorization_url' => $authorizationUrl,
            ]);

            return $transaction->fresh();
        });
    }
}
