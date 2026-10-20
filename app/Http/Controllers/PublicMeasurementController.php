<?php

namespace App\Http\Controllers;

use App\Actions\Measurements\ResolveMeasurementLink;
use Illuminate\Http\Response;

class PublicMeasurementController extends Controller
{
    public function show(string $token): Response
    {
        $link = app(ResolveMeasurementLink::class)->execute($token);

        if (! $link) {
            return $this->protect(response()->view('measurements.public.invalid', [], 404));
        }

        $order = $link->order()->with('organization')->first();
        $items = $order->items()->where('measurement_eligibility', 'required')->orderBy('sort_order')->get();
        $contact = $link->contact;

        return $this->protect(response()->view('measurements.public.show', [
            'order' => $order,
            'organization' => $order->organization,
            'items' => $items,
            'contactName' => trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? '')),
            'expiresAt' => $link->expires_at,
        ]));
    }

    private function protect(Response $response): Response
    {
        return $response
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('Cache-Control', 'no-store, private');
    }
}
