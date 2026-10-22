<?php

namespace App\Http\Controllers;

use App\Actions\Measurements\IssueMeasurementLink;
use App\Actions\Measurements\ReviewMeasurementSubmission;
use App\Jobs\SendMeasurementLinkEmail;
use App\Models\Contact;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use App\Models\MeasurementSubmission;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MeasurementReviewController extends Controller
{
    public function accept(Request $request, Order $order, MeasurementSubmission $submission): RedirectResponse
    {
        $this->ensureBelongsToOrder($order, $submission);

        app(ReviewMeasurementSubmission::class)->accept($request->user(), $submission);

        return $this->back($order)->with('status', 'Measurements accepted.');
    }

    public function requestCorrection(Request $request, Order $order, MeasurementSubmission $submission): RedirectResponse
    {
        $this->ensureBelongsToOrder($order, $submission);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'contact_ids' => ['required', 'array', 'min:1', 'max:10'],
            'contact_ids.*' => ['integer', 'distinct'],
        ]);

        // Checked BEFORE the review commits, so a bad selection never leaves
        // an item in "correction requested" with nobody told.
        $contacts = Contact::query()
            ->where('organization_id', $order->organization_id)
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereIn('id', $validated['contact_ids'])
            ->get();

        if ($contacts->count() !== count($validated['contact_ids'])) {
            throw ValidationException::withMessages([
                'contact_ids' => 'Every selected contact must be an active contact of this organization with an email address.',
            ]);
        }

        app(ReviewMeasurementSubmission::class)
            ->requestCorrection($request->user(), $submission, $validated['reason']);

        // The review is committed; a link or mail problem never undoes it.
        $text = $this->outstandingCorrections($order);
        $sent = [];
        $failed = [];

        foreach ($contacts as $contact) {
            $name = trim($contact->first_name . ' ' . $contact->last_name);

            try {
                $result = app(IssueMeasurementLink::class)->execute($request->user(), $order, $contact);
            } catch (ValidationException $e) {
                $failed[] = "{$name}: " . collect($e->errors())->flatten()->first();
                continue;
            } catch (AuthorizationException) {
                $failed[] = "{$name}: you may not issue measurement links.";
                continue;
            }

            SendMeasurementLinkEmail::dispatch($result['link'], $result['token'], $text);
            $sent[] = $name;
        }

        $redirect = $this->back($order);

        if ($failed !== []) {
            $redirect = $redirect->withErrors(['measurements' => 'Not emailed: ' . implode(' | ', $failed)]);
        }

        $notice = $sent !== []
            ? 'A fresh ' . IssueMeasurementLink::EXPIRY_DAYS . '-day link is being emailed to ' . implode(', ', $sent) . '.'
            : 'Nobody could be emailed. Send a new link from the measurement links panel.';

        return $redirect->with('status', 'Correction requested. Previous entries were cleared for re-entry. ' . $notice);
    }

    /** One line per item that currently needs correcting: "Item: reason". */
    private function outstandingCorrections(Order $order): string
    {
        $lines = [];

        foreach ($order->items()->where('measurement_eligibility', 'required')->orderBy('sort_order')->get() as $item) {
            $latest = MeasurementSubmission::query()
                ->where('order_item_id', $item->id)
                ->orderByDesc('revision')
                ->first();

            if ($latest && $latest->status === 'correction_requested') {
                $lines[] = "{$item->item_name}: {$latest->correction_reason}";
            }
        }

        return implode("\n", $lines);
    }

    private function ensureBelongsToOrder(Order $order, MeasurementSubmission $submission): void
    {
        abort_unless(
            $submission->orderItem()->where('order_id', $order->id)->exists(),
            404
        );
    }

    private function back(Order $order): RedirectResponse
    {
        return redirect()->to(route('orders.show', $order) . '#measurement-submissions');
    }
}
