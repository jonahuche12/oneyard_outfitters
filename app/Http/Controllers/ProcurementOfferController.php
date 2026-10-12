<?php

namespace App\Http\Controllers;

use App\Http\Requests\Procurement\StoreProcurementOfferRequest;
use App\Http\Requests\Procurement\UpdateProcurementOfferRequest;
use App\Http\Requests\WithdrawProcurementOfferRequest;
use App\Jobs\SendProcurementOfferAcceptedWhatsApp;
use App\Mail\ProcurementOfferAccepted;
use App\Models\Procurement;
use App\Models\ProcurementOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class ProcurementOfferController extends Controller
{
    public function create(Procurement $procurement): View
    {
        Gate::authorize('submitOffer', $procurement);

        $this->ensureAcceptingOffers($procurement);

        return view('procurements.offers.create', [
            'procurement' => $procurement,
        ]);
    }

    public function store(
        StoreProcurementOfferRequest $request,
        Procurement $procurement
    ): RedirectResponse {
        Gate::authorize('submitOffer', $procurement);

        $this->ensureAcceptingOffers($procurement);

        $validated = $request->validated();

        DB::transaction(function () use (
            $request,
            $procurement,
            $validated
        ): void {
            // Lock the procurement row so the per-staff offer limit is counted atomically.
            Procurement::query()
                ->whereKey($procurement->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingOfferCount = ProcurementOffer::query()
                ->where('procurement_id', $procurement->id)
                ->where('user_id', $request->user()->id)
                ->count();

            if ($existingOfferCount >= 3) {
                abort(422, 'You have already submitted the maximum of 3 offers for this procurement.');
            }

            $quantity = (float) $validated['quantity'];
            $unitPrice = (float) $validated['unit_price'];

            ProcurementOffer::create([
                'procurement_id' => $procurement->id,
                'user_id' => $request->user()->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $quantity * $unitPrice,
                'notes' => $validated['notes'] ?? null,
                'status' => ProcurementOffer::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);
        });

        return redirect()
            ->route('procurements.show', $procurement)
            ->with(
                'success',
                'Your offer was submitted successfully.'
            );
    }

    public function show(ProcurementOffer $offer): View
    {
        Gate::authorize('view', $offer);

        $offer->load([
            'procurement.order.organization',
            'user:id,name',
        ]);

        return view('procurements.offers.show', [
            'offer' => $offer,
        ]);
    }

    public function edit(ProcurementOffer $offer): View
    {
        Gate::authorize('update', $offer);

        $offer->load('procurement');

        return view('procurements.offers.edit', [
            'offer' => $offer,
            'procurement' => $offer->procurement,
        ]);
    }

    public function update(
        UpdateProcurementOfferRequest $request,
        ProcurementOffer $offer
    ): RedirectResponse {
        Gate::authorize('update', $offer);

        $validated = $request->validated();

        DB::transaction(function () use ($offer, $validated): void {
            $quantity = (float) $validated['quantity'];
            $unitPrice = (float) $validated['unit_price'];

            $offer->update([
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $quantity * $unitPrice,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('procurements.offers.show', $offer)
            ->with(
                'success',
                'Your offer was updated successfully.'
            );
    }

    public function accept(ProcurementOffer $offer): RedirectResponse
    {
        Gate::authorize('accept', $offer);

        DB::transaction(function () use ($offer): void {
            $procurement = Procurement::query()
                ->whereKey($offer->procurement_id)
                ->lockForUpdate()
                ->firstOrFail();

            $selectedOffer = ProcurementOffer::query()
                ->whereKey($offer->id)
                ->where('procurement_id', $procurement->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $selectedOffer->status === ProcurementOffer::STATUS_SUBMITTED,
                422,
                'Only a submitted offer can be accepted.'
            );

            $selectedOffer->update([
                'status' => ProcurementOffer::STATUS_ACCEPTED,
            ]);
        });

        $message = 'The procurement offer was accepted successfully.';

        $offer->loadMissing([
            'user:id,name,email,phone',
            'procurement:id,item_name,unit,required_by',
        ]);

        // Email is an independent notification channel, not a WhatsApp fallback.
        if (filled($offer->user?->email)) {
            try {
                Mail::to($offer->user->email)
                    ->queue(new ProcurementOfferAccepted($offer));

                $message .= ' An email notification has been queued for the staff member.';
            } catch (Throwable $exception) {
                report($exception);
                Log::error('Accepted procurement offer email could not be queued.', [
                    'offer_id' => $offer->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        } else {
            Log::warning('Accepted procurement offer has no staff email recipient.', [
                'offer_id' => $offer->id,
            ]);
        }

        if (config('services.whatsapp.enabled')) {
            if (blank($offer->user?->phone)) {
                $message .= ' WhatsApp was not queued because this staff member has no phone number saved.';
            } else {
                try {
                    SendProcurementOfferAcceptedWhatsApp::dispatch($offer->id);

                    $message .= ' A WhatsApp notification has been queued for the staff member.';
                } catch (Throwable $exception) {
                    report($exception);
                    Log::error('Accepted procurement offer WhatsApp notification could not be queued.', [
                        'offer_id' => $offer->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return redirect()
            ->route('procurements.show', $offer->procurement_id)
            ->with('success', $message);
    }

    public function reject(ProcurementOffer $offer): RedirectResponse
    {
        Gate::authorize('reject', $offer);

        DB::transaction(function () use ($offer): void {
            $procurement = Procurement::query()
                ->whereKey($offer->procurement_id)
                ->lockForUpdate()
                ->firstOrFail();

            $selectedOffer = ProcurementOffer::query()
                ->whereKey($offer->id)
                ->where('procurement_id', $procurement->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $selectedOffer->status === ProcurementOffer::STATUS_SUBMITTED,
                422,
                'Only a submitted offer can be rejected.'
            );

            $selectedOffer->update([
                'status' => ProcurementOffer::STATUS_REJECTED,
            ]);
        });

        return redirect()
            ->route('procurements.show', $offer->procurement_id)
            ->with(
                'success',
                'The procurement offer was rejected successfully.'
            );
    }

    public function withdraw(
        WithdrawProcurementOfferRequest $request,
        ProcurementOffer $offer
    ): RedirectResponse {
        Gate::authorize('withdraw', $offer);

        $offer->update([
            'status' => ProcurementOffer::STATUS_WITHDRAWN,
            'withdrawal_reason' => $request->validated('withdrawal_reason'),
        ]);

        return redirect()
            ->route('procurements.show', $offer->procurement_id)
            ->with(
                'success',
                'Your offer was withdrawn successfully.'
            );
    }

    private function ensureAcceptingOffers(
        Procurement $procurement
    ): void {
        abort_unless(
            $procurement->status === Procurement::STATUS_READY,
            403,
            'This procurement is not currently accepting offers.'
        );

        abort_if(
            $procurement->offer_deadline !== null
                && $procurement->offer_deadline->isPast(),
            403,
            'The offer deadline for this procurement has passed.'
        );
    }
}
