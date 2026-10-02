<?php

namespace App\Http\Controllers;

use App\Http\Requests\Procurement\StoreProcurementOfferRequest;
use App\Http\Requests\Procurement\UpdateProcurementOfferRequest;
use App\Http\Requests\WithdrawProcurementOfferRequest;
use App\Models\Procurement;
use App\Models\ProcurementOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

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
            $lockedProcurement = Procurement::query()
                ->whereKey($procurement->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                $lockedProcurement->offers()
                    ->where('status', ProcurementOffer::STATUS_ACCEPTED)
                    ->exists(),
                403,
                'This procurement already has an accepted offer.'
            );

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

            ProcurementOffer::query()
                ->where('procurement_id', $procurement->id)
                ->where('status', ProcurementOffer::STATUS_SUBMITTED)
                ->where('id', '!=', $selectedOffer->id)
                ->update([
                    'status' => ProcurementOffer::STATUS_REJECTED,
                ]);
        });

        return redirect()
            ->route('procurements.show', $offer->procurement_id)
            ->with(
                'success',
                'The procurement offer was accepted successfully.'
            );
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

        abort_if(
            $procurement->offers()
                ->where('status', ProcurementOffer::STATUS_ACCEPTED)
                ->exists(),
            403,
            'This procurement already has an accepted offer.'
        );
    }
}
