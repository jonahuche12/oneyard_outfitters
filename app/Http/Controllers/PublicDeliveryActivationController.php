<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\DeliveryActivationRecipient;
use Illuminate\Support\Facades\Http;
use App\Models\PaymentTransaction;
use App\Actions\Payments\FulfillVerifiedDeliveryPayment;
use App\Actions\Payments\InitializeDeliveryBalancePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicDeliveryActivationController extends Controller
{
    public function show(string $token): View
    {
        $recipient = DeliveryActivationRecipient::query()
            ->with([
                'delivery.order.organization',
                'delivery.order.items',
                'delivery.order.quotation.payments',
                'contact',
            ])
            ->where('access_token', $token)
            ->firstOrFail();

        $order = $recipient->delivery->order;

        $totalPaid = (float) $order->quotation->payments
            ->where('status', 'completed')
            ->sum(fn ($payment) => (float) $payment->amount);

        $balanceDue = max(
            0,
            round((float) $order->total - $totalPaid, 2)
        );

        if (
            $recipient->status === DeliveryActivationRecipient::STATUS_PENDING
            && $recipient->viewed_at === null
        ) {
            $recipient->update([
                'viewed_at' => now(),
            ]);
        }

        return view(
            'public.deliveries.activate',
            compact(
                'recipient',
                'balanceDue'
            )
        );
    }

    public function activate(
        Request $request,
        string $token
    ): RedirectResponse {
        $validated = $request->validate([
            'payment_arrangement' => [
                'required',
                'in:pay_now,pay_on_delivery,paid_offline',
            ],
        ]);

        $recipient = DeliveryActivationRecipient::query()
            ->with('delivery')
            ->where('access_token', $token)
            ->firstOrFail();

        if (
            $recipient->status !== DeliveryActivationRecipient::STATUS_PENDING
        ) {
            return redirect()
                ->route('public.deliveries.activate', $token)
                ->with('error', 'This delivery activation link has already been used.');
        }

        $delivery = $recipient->delivery;

        if ($delivery->status !== Delivery::STATUS_PENDING) {
            return redirect()
                ->route('public.deliveries.activate', $token)
                ->with('error', 'This delivery is no longer available for activation.');
        }

        if (
            $delivery->payment_arrangement === Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE
            && $delivery->offline_payment_status === Delivery::OFFLINE_PAYMENT_STATUS_PENDING
        ) {
            return redirect()
                ->route('public.deliveries.activate', $token)
                ->with(
                    'info',
                    'Your offline payment claim is awaiting Admin verification. Please wait for the claim to be reviewed.'
                );
        }

        if (
            $validated['payment_arrangement']
            === Delivery::PAYMENT_ARRANGEMENT_PAY_NOW
        ) {
            return redirect()
                ->route('public.deliveries.activate', $token)
                ->with(
                    'info',
                    'Online payment for the delivery balance is not yet available. Please use Pay on Delivery or return later when online payment is enabled.'
                );
        }

        if (
            $validated['payment_arrangement']
            === Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE
        ) {
            $delivery->update([
                'payment_arrangement' => Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE,
                'offline_payment_status' => Delivery::OFFLINE_PAYMENT_STATUS_PENDING,
                'offline_payment_claimed_by' => null,
                'offline_payment_claimed_at' => now(),
                'offline_payment_reviewed_by' => null,
                'offline_payment_reviewed_at' => null,
                'offline_payment_review_notes' => null,
                'activated_at' => null,
            ]);

            $recipient->update([
                'status' => DeliveryActivationRecipient::STATUS_PENDING,
                'activated_at' => null,
            ]);

            return redirect()
                ->route('public.deliveries.activate', $token)
                ->with(
                    'success',
                    'Your offline payment claim has been submitted for Admin verification. The delivery will remain pending until the claim is confirmed.'
                );
        }

        $delivery->update([
            'payment_arrangement' => Delivery::PAYMENT_ARRANGEMENT_PAY_ON_DELIVERY,
            'activated_at' => now(),
        ]);

        $recipient->update([
            'status' => DeliveryActivationRecipient::STATUS_ACTIVATED,
            'activated_at' => now(),
        ]);

        return redirect()
            ->route('public.deliveries.activate', $token)
            ->with(
                'success',
                'Your delivery has been activated. Payment will be completed when the order is delivered.'
            );
    }

    public function payNow(
        Request $request,
        string $token
    ): RedirectResponse {
        $recipient = DeliveryActivationRecipient::query()
            ->with('delivery.order.quotation')
            ->where('access_token', $token)
            ->firstOrFail();

        if (
            $recipient->status
            !== DeliveryActivationRecipient::STATUS_PENDING
            && $recipient->status
            !== DeliveryActivationRecipient::STATUS_ACTIVATED
        ) {
            return redirect()
                ->route('public.deliveries.activate', $token)
                ->with(
                    'error',
                    'This Delivery activation link is no longer available.'
                );
        }

        $delivery = $recipient->delivery;

        if ($delivery->status !== Delivery::STATUS_PENDING) {
            return redirect()
                ->route('public.deliveries.activate', $token)
                ->with(
                    'error',
                    'This Delivery is no longer available for payment.'
                );
        }

        $delivery->update([
            'payment_arrangement' => Delivery::PAYMENT_ARRANGEMENT_PAY_NOW,
        ]);

        try {
            $transaction = app(
                InitializeDeliveryBalancePayment::class
            )->execute(
                $delivery,
                route(
                    'public.deliveries.payment.callback',
                    $token
                )
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('public.deliveries.activate', $token)
                ->with(
                    'error',
                    $e->getMessage()
                );
        }

        if (
            ! is_string($transaction->authorization_url)
            || $transaction->authorization_url === ''
        ) {
            return redirect()
                ->route('public.deliveries.activate', $token)
                ->with(
                    'error',
                    'Paystack did not return a payment authorization link. Please try again.'
                );
        }

        return redirect()->away($transaction->authorization_url);
    }

    public function paymentCallback(string $token): RedirectResponse
    {
        $recipient = DeliveryActivationRecipient::query()
            ->with('delivery')
            ->where('access_token', $token)
            ->firstOrFail();

        $delivery = $recipient->delivery;

        $quotation = $delivery->order->quotation;

        $quotationRecipient = $quotation?->recipients()
            ->where('contact_id', $delivery->order->contact_id)
            ->first();

        $transaction = PaymentTransaction::query()
            ->where('delivery_id', $delivery->id)
            ->when(
                $quotationRecipient,
                fn ($query) => $query->where(
                    'quotation_recipient_id',
                    $quotationRecipient->id
                )
            )
            ->latest('id')
            ->first();

        if (! $transaction) {
            return redirect()
                ->route('public.deliveries.activate', $token)
                ->with(
                    'error',
                    'Payment transaction was not found.'
                );
        }

        if ($transaction->status === 'paid') {
            try {
                app(FulfillVerifiedDeliveryPayment::class)
                    ->execute($transaction);
            } catch (\Throwable $e) {
                report($e);

                return redirect()
                    ->route('public.deliveries.activate', $token)
                    ->with(
                        'error',
                        'Payment was received but could not be finalized automatically. Please contact Oneyard Outfitters.'
                    );
            }

            return redirect()
                ->route('public.deliveries.activate', $token)
                ->with(
                    'success',
                    'Payment received successfully. Your Delivery balance is now fully paid.'
                );
        }

        $response = Http::withToken(
            config('services.paystack.secret_key')
        )->acceptJson()->get(
            rtrim(config('services.paystack.base_url'), '/')
            . '/transaction/verify/'
            . $transaction->reference
        );

        if (! $response->successful() || ! $response->json('status')) {
            return redirect()
                ->route('public.deliveries.activate', $token)
                ->with(
                    'error',
                    'Paystack could not verify this payment.'
                );
        }

        $gatewayStatus = $response->json('data.status');

        if ($gatewayStatus !== 'success') {
            return redirect()
                ->route('public.deliveries.activate', $token)
                ->with(
                    'error',
                    'The Paystack payment was not completed.'
                );
        }

        $gatewayTransactionId = $response->json('data.id');

        $expectedAmount = (float) $transaction->amount;
        $paidAmount = ((float) $response->json('data.amount')) / 100;

        if (abs($paidAmount - $expectedAmount) > 0.01) {
            $transaction->update([
                'status' => 'failed',
            ]);

            return redirect()
                ->route('public.deliveries.activate', $token)
                ->with(
                    'error',
                    'The verified Paystack amount does not match the Delivery balance.'
                );
        }

        $transaction->update([
            'status' => 'paid',
            'gateway_transaction_id' => $gatewayTransactionId
                ? (string) $gatewayTransactionId
                : $transaction->gateway_transaction_id,
            'paid_at' => now(),
        ]);

        try {
            app(FulfillVerifiedDeliveryPayment::class)
                ->execute($transaction);
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('public.deliveries.activate', $token)
                ->with(
                    'error',
                    'Payment was verified but could not be finalized automatically. Please contact Oneyard Outfitters.'
                );
        }

        return redirect()
            ->route('public.deliveries.activate', $token)
            ->with(
                'success',
                'Payment received successfully. Your Delivery balance is now fully paid.'
            );
    }

}
