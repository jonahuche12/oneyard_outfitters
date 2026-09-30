<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\QuotationRecipient;
use App\Models\PaymentTransaction;
use App\Actions\Payments\InitializePaystackTransaction;
use App\Actions\Payments\FulfillVerifiedQuotationPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class QuotationController extends Controller
{
    public function show(string $token): View
    {
        $recipient = $this->findRecipient($token);

        if (! $recipient->viewed_at) {
            $recipient->update([
                'viewed_at' => now(),
            ]);
        }

        return view('public.quotations.show', [
            'recipient' => $recipient,
            'quotation' => $recipient->quotation,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $recipient = $this->findRecipient($token);

        if ($recipient->response_status !== 'pending') {
            return back()->withErrors([
                'quotation' => 'This quotation has already received your response.',
            ]);
        }

        if ($recipient->quotation->status !== Quotation::STATUS_SENT) {
            return back()->withErrors([
                'quotation' => 'This quotation is no longer available for response.',
            ]);
        }

        $validated = $request->validate([
            'payment_percentage' => [
                'required',
                'integer',
                'in:30,60,80',
            ],
        ]);

        $percentage = (int) $validated['payment_percentage'];

        $paymentAmount = round(
            ((float) $recipient->quotation->total * $percentage) / 100,
            2
        );

        $recipient->update([
            'payment_percentage' => $percentage,
            'payment_amount' => $paymentAmount,
            'amount_paid' => 0,
        ]);

        $transaction = app(InitializePaystackTransaction::class)->execute(
            $recipient->fresh(),
            $percentage,
            $paymentAmount
        );

        return redirect()->away($transaction->authorization_url);
    }

    public function paymentCallback(string $token): RedirectResponse
    {
        $recipient = $this->findRecipient($token);
        $reference = request()->query('reference');

        if (! $reference) {
            return redirect()
                ->route('public.quotations.show', $recipient->access_token)
                ->withErrors([
                    'quotation' => 'Payment reference was not provided.',
                ]);
        }

        $transaction = PaymentTransaction::query()
            ->where('quotation_recipient_id', $recipient->id)
            ->where('reference', $reference)
            ->first();

        if (! $transaction) {
            return redirect()
                ->route('public.quotations.show', $recipient->access_token)
                ->withErrors([
                    'quotation' => 'Payment transaction was not found.',
                ]);
        }

        if ($transaction->status === 'paid') {
            return redirect()
                ->route('public.quotations.show', $recipient->access_token)
                ->with('status', 'Payment has already been confirmed.');
        }

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->acceptJson()
            ->get(
                rtrim(config('services.paystack.base_url'), '/') .
                '/transaction/verify/' .
                $transaction->reference
            );

        if (
            $response->failed() ||
            ! $response->json('status') ||
            $response->json('data.status') !== 'success'
        ) {
            $transaction->update([
                'status' => 'failed',
            ]);

            return redirect()
                ->route('public.quotations.show', $recipient->access_token)
                ->withErrors([
                    'quotation' => 'Payment could not be confirmed.',
                ]);
        }

        $gatewayTransactionId = $response->json('data.id');
        $paidAmount = ((int) $response->json('data.amount')) / 100;
        $expectedAmount = (float) $transaction->amount;

        if (abs($paidAmount - $expectedAmount) > 0.01) {
            $transaction->update([
                'status' => 'failed',
            ]);

            return redirect()
                ->route('public.quotations.show', $recipient->access_token)
                ->withErrors([
                    'quotation' =>
                        'The confirmed payment amount does not match the quotation payment.',
                ]);
        }

        $transaction->update([
            'status' => 'paid',
            'gateway_transaction_id' => $gatewayTransactionId
                ? (string) $gatewayTransactionId
                : null,
            'paid_at' => now(),
        ]);

        $recipient->update([
            'response_status' => 'accepted',
            'rejection_feedback' => null,
            'responded_at' => $recipient->responded_at ?: now(),
            'amount_paid' => $transaction->amount,
        ]);

        $this->syncQuotationStatus($recipient->quotation);

        if ($recipient->quotation->fresh()->status === Quotation::STATUS_ACCEPTED) {
            app(FulfillVerifiedQuotationPayment::class)->execute(
                $transaction->fresh()
            );
        }

        return redirect()
            ->route('public.quotations.show', $recipient->access_token)
            ->with('status', 'Payment confirmed. Your quotation has been accepted.');
    }

    public function reject(Request $request, string $token): RedirectResponse
    {
        $recipient = $this->findRecipient($token);

        if ($recipient->response_status !== 'pending') {
            return back()->withErrors([
                'quotation' => 'This quotation has already received your response.',
            ]);
        }

        if ($recipient->quotation->status === Quotation::STATUS_ACCEPTED) {
            return back()->withErrors([
                'quotation' => 'This quotation has already been accepted and cannot be rejected.',
            ]);
        }

        if ($recipient->quotation->status === Quotation::STATUS_REJECTED) {
            return back()->withErrors([
                'quotation' => 'This quotation has already been rejected and cannot receive another response.',
            ]);
        }

        if ($recipient->quotation->status !== Quotation::STATUS_SENT) {
            return back()->withErrors([
                'quotation' => 'This quotation is no longer available for response.',
            ]);
        }

        $validated = $request->validate([
            'rejection_feedback' => [
                'required',
                'string',
                'min:5',
                'max:5000',
            ],
        ]);

        $recipient->update([
            'response_status' => 'rejected',
            'rejection_feedback' => $validated['rejection_feedback'],
            'responded_at' => now(),
        ]);

        $this->syncQuotationStatus($recipient->quotation);

        return redirect()
            ->route('public.quotations.show', $recipient->access_token)
            ->with('status', 'Quotation rejected. Your feedback has been recorded.');
    }

    private function findRecipient(string $token): QuotationRecipient
    {
        return QuotationRecipient::query()
            ->with([
                'quotation.organization',
                'quotation.items',
                'contact',
            ])
            ->where('access_token', $token)
            ->firstOrFail();
    }

    private function syncQuotationStatus(Quotation $quotation): void
    {
        $recipients = $quotation->recipients()->get();

        if ($recipients->isEmpty()) {
            return;
        }

        /*
         * A single accepted recipient is sufficient to accept the quotation.
         * Acceptance is payment-driven: once one recipient completes the
         * approved payment flow, the quotation becomes accepted immediately.
         */
        if ($recipients->contains(
            fn (QuotationRecipient $recipient) =>
                $recipient->response_status === 'accepted'
        )) {
            $quotation->update([
                'status' => Quotation::STATUS_ACCEPTED,
            ]);

            return;
        }

        if ($recipients->every(
            fn (QuotationRecipient $recipient) =>
                $recipient->response_status === 'rejected'
        )) {
            $quotation->update([
                'status' => Quotation::STATUS_REJECTED,
            ]);
        }
    }
}
