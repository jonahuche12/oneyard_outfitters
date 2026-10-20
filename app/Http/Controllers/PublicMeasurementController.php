<?php

namespace App\Http\Controllers;

use App\Actions\Measurements\ResolveMeasurementLink;
use App\Actions\Measurements\SubmitMeasurements;
use App\Actions\Measurements\ValidateMeasurementPayload;
use App\Models\MeasurementSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicMeasurementController extends Controller
{
    public function show(string $token): Response
    {
        $link = app(ResolveMeasurementLink::class)->execute($token);

        if (! $link) {
            return $this->protect(response()->view('measurements.public.invalid', ['title' => 'Link not valid — Oneyard Outfitters'], 404));
        }

        $order = $link->order()->with('organization')->first();
        $items = $order->items()->where('measurement_eligibility', 'required')->orderBy('sort_order')->get();
        $contact = $link->contact;

        $states = [];
        foreach ($items as $item) {
            $latest = MeasurementSubmission::where('order_item_id', $item->id)->orderByDesc('revision')->first();
            $states[$item->id] = [
                'status' => $latest?->status,
                'reason' => $latest?->status === 'correction_requested' ? $latest->correction_reason : null,
                'open' => ! $latest || $latest->status === 'correction_requested',
                'profile' => ValidateMeasurementPayload::profileFor($item->product_type),
                'strict' => strtolower((string) $item->unit) === 'piece',
            ];
        }

        return $this->protect(response()->view('measurements.public.show', [
            'token' => $token,
            'title' => 'Measurements — Oneyard Outfitters',
            'order' => $order,
            'organization' => $order->organization,
            'items' => $items,
            'states' => $states,
            'contactName' => trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? '')),
            'expiresAt' => $link->expires_at,
        ]));
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        // Throws ValidationException (redirects back with errors and input) on any problem.
        app(SubmitMeasurements::class)->execute($token, (array) $request->input('items', []));

        return redirect()
            ->route('measurements.public.show', ['token' => $token])
            ->with('status', 'Thank you. Your measurements have been received and are under review.')
            ->header('Referrer-Policy', 'no-referrer');
    }

    private function protect(Response $response): Response
    {
        return $response
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('Cache-Control', 'no-store, private');
    }
}
