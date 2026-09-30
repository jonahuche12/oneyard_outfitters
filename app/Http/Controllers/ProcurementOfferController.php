<?php

namespace App\Http\Controllers;

use App\Http\Requests\Procurement\StoreProcurementOfferRequest;
use App\Http\Requests\Procurement\UpdateProcurementOfferRequest;
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

    public function withdraw(ProcurementOffer $offer): RedirectResponse
    {
        Gate::authorize('withdraw', $offer);

        $offer->update([
            'status' => ProcurementOffer::STATUS_WITHDRAWN,
        ]);

        return redirect()
            ->route('procurements.show', $offer->procurement_id)
            ->with(
                'success',
                'Your offer was cancelled successfully.'
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
