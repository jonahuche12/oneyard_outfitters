<?php

namespace App\Http\Controllers;

use App\Mail\DeliveryActivationInvitation;
use App\Models\Contact;
use App\Models\Delivery;
use App\Models\DeliveryActivationRecipient;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Delivery::class);

        $deliveries = Delivery::query()
            ->with([
                'order.organization',
                'creator',
                'confirmer',
            ])
            ->latest('id')
            ->paginate(20);

        return view('deliveries.index', compact('deliveries'));
    }

        public function show(Delivery $delivery): View
    {
        Gate::authorize('view', $delivery);

        $delivery->load([
            'order.organization',
            'order.organization.contacts',
            'order.contact',
            'order.items',
            'creator',
            'confirmer',
            'activationRecipients.contact',
        ]);

        return view('deliveries.show', compact('delivery'));
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('proceedToDelivery', $order);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
            'delivery_date' => ['nullable', 'date'],
        ]);

        $delivery = DB::transaction(function () use (
            $order,
            $validated
        ): Delivery {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::authorize('proceedToDelivery', $lockedOrder);

            return Delivery::create([
                'order_id' => $lockedOrder->id,
                'created_by' => auth()->id(),
                'status' => Delivery::STATUS_PENDING,
                'delivery_date' => $validated['delivery_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('deliveries.show', $delivery)
            ->with(
                'success',
                'The Order has been moved into Delivery.'
            );
    }

    public function claimOfflinePayment(
        Request $request,
        Delivery $delivery
    ): RedirectResponse {
        Gate::authorize('claimOfflinePayment', $delivery);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($delivery, $validated, $request): void {
            $lockedDelivery = Delivery::query()
                ->lockForUpdate()
                ->findOrFail($delivery->id);

            if (
                $lockedDelivery->status !== Delivery::STATUS_PENDING
                || $lockedDelivery->payment_arrangement !== Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE
                || ! in_array(
                    $lockedDelivery->offline_payment_status,
                    [null, Delivery::OFFLINE_PAYMENT_STATUS_REJECTED],
                    true
                )
            ) {
                abort(422, 'This delivery is not eligible for an offline payment claim.');
            }

            if ($lockedDelivery->activated_at === null) {
                abort(422, 'The delivery must be activated before an offline payment can be claimed.');
            }

            $lockedDelivery->forceFill([
                'offline_payment_status' => Delivery::OFFLINE_PAYMENT_STATUS_PENDING,
                'offline_payment_claimed_by' => $request->user()->id,
                'offline_payment_claimed_at' => now(),
                'offline_payment_reviewed_by' => null,
                'offline_payment_reviewed_at' => null,
                'offline_payment_review_notes' => $validated['notes'] ?? null,
            ])->save();
        });

        return back()->with(
            'success',
            'Offline payment claim submitted for Admin review.'
        );
    }

    public function confirmOfflinePayment(
        Request $request,
        Delivery $delivery
    ): RedirectResponse {
        Gate::authorize('confirmOfflinePayment', $delivery);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($delivery, $validated, $request): void {
            $lockedDelivery = Delivery::query()
                ->lockForUpdate()
                ->findOrFail($delivery->id);

            if (
                $lockedDelivery->status !== Delivery::STATUS_PENDING
                || $lockedDelivery->payment_arrangement !== Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE
                || $lockedDelivery->offline_payment_status !== Delivery::OFFLINE_PAYMENT_STATUS_PENDING
            ) {
                abort(422, 'This offline payment claim is no longer awaiting review.');
            }

            $lockedOrder = Order::query()
                ->with('quotation')
                ->lockForUpdate()
                ->findOrFail($lockedDelivery->order_id);

            if ($lockedOrder->status !== Order::STATUS_READY) {
                abort(422, 'The order is not ready for delivery completion.');
            }

            $quotation = $lockedOrder->quotation;

            if ($quotation === null) {
                abort(422, 'The order has no quotation attached.');
            }

            $paymentContactId = $quotation->contact_id;

            if ($paymentContactId === null) {
                $paymentContactId = $lockedOrder->organization
                    ->contacts()
                    ->where('is_primary', true)
                    ->where('is_active', true)
                    ->value('id');
            }

            if ($paymentContactId === null) {
                abort(422, 'The organization has no primary active contact for payment records.');
            }

            $quotationRecipient = $quotation->recipients()
                ->where('contact_id', $paymentContactId)
                ->first();

            if ($quotationRecipient === null) {
                $contact = $lockedOrder->organization
                    ->contacts()
                    ->whereKey($paymentContactId)
                    ->where('is_active', true)
                    ->first();

                if ($contact === null) {
                    abort(422, 'The payment contact is no longer available.');
                }

                if (blank($contact->email)) {
                    abort(422, 'The payment contact must have an email address before a payment record can be created.');
                }

                $quotationRecipient = $quotation->recipients()->create([
                    'contact_id' => $contact->id,
                    'email' => $contact->email,
                    'access_token' => Str::random(64),
                ]);
            }

            $totalPaid = (float) $quotation->payments()
                ->where('status', 'completed')
                ->sum('amount');

            $balanceDue = max(
                0,
                round((float) $lockedOrder->total - $totalPaid, 2)
            );

            if ($balanceDue > 0.01) {
                Payment::create([
                    'organization_id' => $lockedOrder->organization_id,
                    'quotation_id' => $lockedOrder->quotation_id,
                    'quotation_recipient_id' => $quotationRecipient->id,
                    'payment_transaction_id' => null,
                    'amount' => $balanceDue,
                    'payment_method' => Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE,
                    'reference' => 'DELIVERY-OFFLINE-' . $lockedDelivery->id . '-' . now()->format('YmdHis'),
                    'status' => 'completed',
                    'paid_at' => now(),
                    'notes' => $validated['notes']
                        ?: 'Offline payment claim confirmed by Admin.',
                ]);
            }

            $lockedDelivery->forceFill([
                'status' => Delivery::STATUS_CONFIRMED,
                'confirmed_by' => $request->user()->id,
                'confirmed_at' => now(),
                'delivery_date' => now()->toDateString(),
                'offline_payment_status' => Delivery::OFFLINE_PAYMENT_STATUS_CONFIRMED,
                'offline_payment_reviewed_by' => $request->user()->id,
                'offline_payment_reviewed_at' => now(),
                'offline_payment_review_notes' => $validated['notes']
                    ?? $lockedDelivery->offline_payment_review_notes,
            ])->save();

            $lockedOrder->update([
                'status' => Order::STATUS_DELIVERED,
            ]);
        });

        return back()->with(
            'success',
            'Offline payment confirmed and delivery completed.'
        );
    }

    public function rejectOfflinePayment(
        Request $request,
        Delivery $delivery
    ): RedirectResponse {
        Gate::authorize('rejectOfflinePayment', $delivery);

        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($delivery, $validated, $request): void {
            $lockedDelivery = Delivery::query()
                ->lockForUpdate()
                ->findOrFail($delivery->id);

            if (
                $lockedDelivery->status !== Delivery::STATUS_PENDING
                || $lockedDelivery->payment_arrangement !== Delivery::PAYMENT_ARRANGEMENT_PAID_OFFLINE
                || $lockedDelivery->offline_payment_status !== Delivery::OFFLINE_PAYMENT_STATUS_PENDING
            ) {
                abort(422, 'This offline payment claim is no longer awaiting review.');
            }

            $lockedDelivery->forceFill([
                'payment_arrangement' => null,
                'activated_at' => null,
                'offline_payment_status' => Delivery::OFFLINE_PAYMENT_STATUS_REJECTED,
                'offline_payment_reviewed_by' => $request->user()->id,
                'offline_payment_reviewed_at' => now(),
                'offline_payment_review_notes' => $validated['notes'],
            ])->save();

            $lockedDelivery->activationRecipients()
                ->update([
                    'status' => DeliveryActivationRecipient::STATUS_PENDING,
                    'activated_at' => null,
                ]);
        });

        return back()->with(
            'success',
            'Offline payment claim rejected. Delivery controls are available again.'
        );
    }

    public function sendActivationInvitations(
        Request $request,
        Delivery $delivery
    ): RedirectResponse {
        Gate::authorize('view', $delivery);

        if ($delivery->status === Delivery::STATUS_CONFIRMED) {
            return back()->with(
                'error',
                'Activation links cannot be sent because this Delivery has already been completed.'
            );
        }

        if ($delivery->activated_at !== null) {
            return back()->with(
                'error',
                'Activation links cannot be sent because this Delivery has already been activated.'
            );
        }

        $validated = $request->validate([
            'contact_ids' => ['required', 'array', 'min:1'],
            'contact_ids.*' => ['integer', 'distinct'],
        ]);

        $contacts = $delivery->order->organization
            ->contacts()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereIn('id', $validated['contact_ids'])
            ->get();

        if ($contacts->count() !== count($validated['contact_ids'])) {
            return back()
                ->withErrors([
                    'contact_ids' => 'One or more selected contacts are invalid, inactive, or do not have an email address.',
                ])
                ->withInput();
        }

        $recipientIds = [];

        DB::transaction(function () use (
            $delivery,
            $contacts,
            &$recipientIds
        ): void {
            foreach ($contacts as $contact) {
                $recipient = DeliveryActivationRecipient::query()
                    ->where('delivery_id', $delivery->id)
                    ->where('contact_id', $contact->id)
                    ->first();

                if ($recipient) {
                    $recipient->update([
                        'email' => $contact->email,
                        'access_token' => Str::random(64),
                        'status' => DeliveryActivationRecipient::STATUS_PENDING,
                        'notified_at' => now(),
                        'viewed_at' => null,
                        'activated_at' => null,
                    ]);
                } else {
                    $recipient = DeliveryActivationRecipient::create([
                        'delivery_id' => $delivery->id,
                        'contact_id' => $contact->id,
                        'email' => $contact->email,
                        'access_token' => Str::random(64),
                        'status' => DeliveryActivationRecipient::STATUS_PENDING,
                        'notified_at' => now(),
                    ]);
                }

                $recipientIds[] = $recipient->id;
            }
        });

        $recipients = DeliveryActivationRecipient::query()
            ->with([
                'delivery.order.organization',
                'contact',
            ])
            ->whereIn('id', $recipientIds)
            ->get();

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->queue(
                new DeliveryActivationInvitation(
                    $recipient->delivery,
                    $recipient->contact,
                    $recipient->access_token
                )
            );
        }

        return redirect()
            ->route('deliveries.show', $delivery)
            ->with(
                'success',
                'Delivery activation invitation(s) have been sent to the selected contact(s).'
            );
    }

    public function confirm(
        Request $request,
        Delivery $delivery
    ): RedirectResponse {
        Gate::authorize('confirm', $delivery);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
            'confirmation' => ['accepted'],
        ]);

        $balanceCollected = false;

        DB::transaction(function () use (
            $delivery,
            $validated,
            &$balanceCollected
        ): void {
            $lockedDelivery = Delivery::query()
                ->whereKey($delivery->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::authorize('confirm', $lockedDelivery);

            if ($lockedDelivery->activated_at === null) {
                abort(
                    422,
                    'The customer must activate the Delivery before it can be completed.'
                );
            }

            if ($lockedDelivery->payment_arrangement === null) {
                abort(
                    422,
                    'A payment arrangement must be selected before the Delivery can be completed.'
                );
            }

            $lockedOrder = Order::query()
                ->with('quotation')
                ->whereKey($lockedDelivery->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status !== Order::STATUS_READY) {
                abort(
                    422,
                    'Only an Order ready for Delivery can be completed.'
                );
            }

            $quotation = $lockedOrder->quotation;

            if ($quotation === null) {
                abort(
                    422,
                    'The Order cannot be completed because its quotation is missing.'
                );
            }

            /*
             * Resolve the contact that should own the delivery balance
             * payment.
             *
             * The quotation contact is preferred. If the quotation was
             * created without a contact, fall back to the organization's
             * primary active contact.
             */
            $recipientContactId = $quotation->contact_id;

            if ($recipientContactId === null) {
                $recipientContactId = $lockedOrder->organization
                    ->contacts()
                    ->where('is_primary', true)
                    ->where('is_active', true)
                    ->value('id');
            }

            if ($recipientContactId === null) {
                abort(
                    422,
                    'The Order cannot be completed because the organization has no primary active contact.'
                );
            }

            $recipientContact = $lockedOrder->organization
                ->contacts()
                ->whereKey($recipientContactId)
                ->where('is_active', true)
                ->first();

            if ($recipientContact === null) {
                abort(
                    422,
                    'The Order cannot be completed because the delivery payment contact is unavailable.'
                );
            }

            /*
             * A quotation recipient is required by the payment ledger.
             * Older/manual quotations may not have one, so create the
             * recipient from the resolved organization contact.
             */
            $quotationRecipient = $quotation->recipients()
                ->where('contact_id', $recipientContact->id)
                ->first();

            if ($quotationRecipient === null) {
                if (blank($recipientContact->email)) {
                    abort(
                        422,
                        'The Order cannot be completed because the delivery payment contact has no email address.'
                    );
                }

                $quotationRecipient = $quotation->recipients()->create([
                    'contact_id' => $recipientContact->id,
                    'email' => $recipientContact->email,
                    'access_token' => Str::random(64),
                ]);
            }

            $totalPaid = (float) $quotation->payments()
                ->where('status', 'completed')
                ->sum('amount');

            $balanceDue = max(
                0,
                round((float) $lockedOrder->total - $totalPaid, 2)
            );

            $balanceCollected = $balanceDue > 0.01;

            if ($balanceDue > 0.01) {
                Payment::create([
                    'organization_id' => $lockedOrder->organization_id,
                    'quotation_id' => $lockedOrder->quotation_id,
                    'quotation_recipient_id' => $quotationRecipient->id,
                    'payment_transaction_id' => null,
                    'amount' => $balanceDue,
                    'payment_method' => $lockedDelivery->payment_arrangement,
                    'reference' => 'DELIVERY-BALANCE-'
                        . $lockedDelivery->id
                        . '-'
                        . now()->format('YmdHis'),
                    'status' => 'completed',
                    'paid_at' => now(),
                    'notes' => $validated['notes']
                        ?: 'Outstanding balance received at delivery completion.',
                ]);
            }

            $lockedDelivery->update([
                'status' => Delivery::STATUS_CONFIRMED,
                'confirmed_by' => auth()->id(),
                'confirmed_at' => now(),
                'notes' => $validated['notes']
                    ?? $lockedDelivery->notes,
            ]);

            $lockedOrder->update([
                'status' => Order::STATUS_DELIVERED,
            ]);
        });

        return redirect()
            ->route('orders.show', $delivery->order_id)
            ->with(
                'success',
                $balanceCollected
                    ? 'Balance received, payment recorded, and delivery confirmed. The Order is now fully paid and delivered.'
                    : 'Delivery confirmed successfully. The Order was already fully paid and is now marked as delivered.'
            );
    }

}
