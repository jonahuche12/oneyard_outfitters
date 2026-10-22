<?php

namespace App\Http\Controllers;

use App\Actions\Measurements\SaveMeasurementDraft;

use App\Actions\Measurements\ResolveMeasurementLink;
use App\Actions\Measurements\SubmitMeasurements;
use App\Actions\Measurements\ValidateMeasurementPayload;
use App\Models\MeasurementSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
            $latest?->load(['individualMeasurements', 'sizeQuantities']);

            $states[$item->id] = [
                'status' => $latest?->status,
                'reason' => $latest?->status === 'correction_requested' ? $latest->correction_reason : null,
                'open' => ! $latest || in_array($latest->status, ['draft', 'correction_requested'], true),
                'profile' => ValidateMeasurementPayload::profileFor($item->product_type),
                'strict' => strtolower((string) $item->unit) === 'piece',
                'draftPeople' => $latest?->status === 'draft'
                    ? $latest->individualMeasurements->sortBy('position')->map(fn ($person) => [
                        'position' => $person->position,
                        'label' => $person->person_label,
                        'measurements' => $person->measurements,
                        'notes' => $person->notes,
                    ])->values()->all()
                    : [],
                'draftSizes' => $latest?->status === 'draft'
                    ? $latest->sizeQuantities->pluck('quantity', 'size_label')->all()
                    : [],
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

    public function savePerson(Request $request, string $token, int $item): JsonResponse
    {
        $person = (array) $request->input('person', []);
        $draft = app(SaveMeasurementDraft::class)->saveEntry($token, $item, null, $person);
        $entry = $draft->individualMeasurements->sortBy('position')->last();

        return response()->json([
            'ok' => true,
            'position' => $entry->position,
            'label' => $entry->person_label,
            'measurements' => $entry->measurements,
            'notes' => $entry->notes,
            'saved_count' => $draft->individualMeasurements->count(),
        ]);
    }

    public function updatePerson(Request $request, string $token, int $item, int $position): JsonResponse
    {
        $person = (array) $request->input('person', []);
        $draft = app(SaveMeasurementDraft::class)->saveEntry($token, $item, $position, $person);
        $entry = $draft->individualMeasurements->firstWhere('position', $position);

        return response()->json([
            'ok' => true,
            'position' => $entry->position,
            'label' => $entry->person_label,
            'measurements' => $entry->measurements,
            'notes' => $entry->notes,
            'saved_count' => $draft->individualMeasurements->count(),
        ]);
    }

    public function removePerson(string $token, int $item, int $position): JsonResponse
    {
        $draft = app(SaveMeasurementDraft::class)->removeEntry($token, $item, $position);

        return response()->json([
            'ok' => true,
            'saved_count' => $draft?->individualMeasurements->count() ?? 0,
        ]);
    }

    public function saveSizes(Request $request, string $token, int $item): JsonResponse
    {
        $draft = app(SaveMeasurementDraft::class)->saveSizes(
            $token,
            $item,
            (array) $request->input('sizes', [])
        );

        return response()->json([
            'ok' => true,
            'saved_count' => $draft->sizeQuantities->count(),
        ]);
    }

    public function uploadSpreadsheet(Request $request, string $token, int $item): JsonResponse
    {
        // Authorise the link and the item BEFORE touching the uploaded file.
        $link = app(ResolveMeasurementLink::class)->execute($token);
        $orderItem = $link
            ? \App\Models\OrderItem::query()
                ->where('order_id', $link->order_id)
                ->where('measurement_eligibility', 'required')
                ->find($item)
            : null;

        if (! $link || ! $orderItem) {
            throw \Illuminate\Validation\ValidationException::withMessages(['link' => 'This link is no longer valid.']);
        }

        $request->validate(['file' => ['required', 'file', 'max:2048']]);

        $upload = $request->file('file');
        if (! $upload->isValid()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'The upload failed. Please try again.']);
        }

        $path = $upload->getRealPath();
        $name = $upload->getClientOriginalName();

        try {
            $parser = app(\App\Actions\Measurements\ParseMeasurementSpreadsheet::class);
            $strict = strtolower((string) $orderItem->unit) === 'piece';

            $parsed = $request->input('kind') === 'sizes'
                ? $parser->sizes($path, $name)
                : $parser->individual($path, $name, $orderItem->product_type, $strict ? (int) round((float) $orderItem->quantity) : null);

            $draft = app(SaveMeasurementDraft::class)->replaceFromImport($token, $item, $parsed);
        } finally {
            if ($path && is_file($path)) {
                @unlink($path);
            }
        }

        return response()->json([
            'ok' => true,
            'saved_count' => $draft->individualMeasurements->count() + $draft->sizeQuantities->count(),
            'ignored' => $parsed['ignored'],
            'limit' => $parsed['limit'],
        ]);
    }

    public function template(Request $request, string $token, int $item)
    {
        $link = app(ResolveMeasurementLink::class)->execute($token);
        abort_unless($link, 404);

        $orderItem = \App\Models\OrderItem::query()
            ->where('order_id', $link->order_id)
            ->where('measurement_eligibility', 'required')
            ->find($item);
        abort_unless($orderItem, 404);

        if ($request->query('kind') === 'sizes') {
            $header = ['Size', 'Quantity'];
            $example = array_map(fn ($size) => [$size, ''], config('measurements.sizes'));
            $name = 'sizes-template.csv';
        } else {
            $profile = ValidateMeasurementPayload::profileFor($orderItem->product_type);
            abort_unless($profile, 404);
            $header = array_merge(['Name'], array_map(fn ($f) => $f['label'], array_values($profile)));
            $example = [array_fill(0, count($header), '')];
            $name = 'measurements-template.csv';
        }

        return response()->streamDownload(function () use ($header, $example) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($example as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv', 'Cache-Control' => 'no-store, private']);
    }

    private function protect(Response $response): Response
    {
        return $response
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('Cache-Control', 'no-store, private');
    }
}
