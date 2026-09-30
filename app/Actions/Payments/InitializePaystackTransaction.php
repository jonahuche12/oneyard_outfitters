<?php

namespace App\Actions\Payments;

use App\Models\PaymentTransaction;
use App\Models\QuotationRecipient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class InitializePaystackTransaction
{
    public function execute(
        QuotationRecipient $recipient,
        int $paymentPercentage,
        float $paymentAmount
    ): PaymentTransaction {
        $existing = $recipient->paymentTransactions()
            ->where('status', 'initialized')
            ->where('amount', $paymentAmount)
            ->latest('id')
            ->first();

        if ($existing && $existing->authorization_url) {
            return $existing;
        }

        $reference = 'OY-' . Str::upper(Str::random(24));

        $transaction = $recipient->paymentTransactions()->create([
            'reference' => $reference,
            'amount' => $paymentAmount,
            'currency' => 'NGN',
            'gateway' => 'paystack',
            'status' => 'initialized',
            'initialized_at' => now(),
        ]);

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->acceptJson()
            ->post(
                rtrim(config('services.paystack.base_url'), '/') . '/transaction/initialize',
                [
                    'email' => $recipient->email,
                    'amount' => (int) round($paymentAmount * 100),
                    'currency' => 'NGN',
                    'reference' => $reference,
                    'callback_url' => route(
                        'public.quotations.payment.callback',
                        $recipient->access_token
                    ),
                    'metadata' => [
                        'quotation_recipient_id' => $recipient->id,
                        'payment_percentage' => $paymentPercentage,
                    ],
                ]
            );

        if ($response->failed() || ! $response->json('status')) {
            $transaction->update([
                'status' => 'failed',
            ]);

            throw new RuntimeException(
                $response->json('message', 'Unable to initialize Paystack transaction.')
            );
        }

        $data = $response->json('data');

        $transaction->update([
            'access_code' => $data['access_code'] ?? null,
            'authorization_url' => $data['authorization_url'] ?? null,
        ]);

        return $transaction->fresh();
    }
}
