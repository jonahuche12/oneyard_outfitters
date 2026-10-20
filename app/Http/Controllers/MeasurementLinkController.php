<?php

namespace App\Http\Controllers;

use App\Actions\Measurements\IssueMeasurementLink;
use App\Actions\Measurements\RevokeMeasurementLink;
use App\Mail\MeasurementLinkInvitation;
use App\Models\Contact;
use App\Models\MeasurementLink;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class MeasurementLinkController extends Controller
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'contact_ids' => ['required', 'array', 'min:1', 'max:10'],
            'contact_ids.*' => ['integer', 'distinct'],
        ]);

        $contacts = Contact::query()->whereIn('id', $validated['contact_ids'])->get();
        abort_if($contacts->count() !== count($validated['contact_ids']), 404);

        $sent = [];
        $failed = [];

        foreach ($contacts as $contact) {
            try {
                $result = app(IssueMeasurementLink::class)->execute($request->user(), $order, $contact);
            } catch (ValidationException $e) {
                $failed[] = trim($contact->first_name . ' ' . $contact->last_name) . ': ' . collect($e->errors())->flatten()->first();
                continue;
            }

            \App\Jobs\SendMeasurementLinkEmail::dispatch($result['link'], $result['token']);
            $sent[] = trim($contact->first_name . ' ' . $contact->last_name);
        }

        $redirect = redirect()->route('orders.show', $order);

        if ($failed !== []) {
            $redirect = $redirect->withErrors(['measurements' => implode(' | ', $failed)]);
        }

        return $sent !== []
            ? $redirect->with('status', 'Measurement link queued for ' . implode(', ', $sent) . '.')
            : $redirect;
    }

    public function revoke(Request $request, Order $order, MeasurementLink $link): RedirectResponse
    {
        abort_unless((int) $link->order_id === (int) $order->id, 404);

        app(RevokeMeasurementLink::class)->execute($request->user(), $link, $request->only(['reason']));

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Measurement link revoked.');
    }
}
