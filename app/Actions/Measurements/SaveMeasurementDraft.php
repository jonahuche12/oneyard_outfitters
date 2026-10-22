<?php

namespace App\Actions\Measurements;

use App\Models\IndividualMeasurement;
use App\Models\MeasurementLink;
use App\Models\MeasurementSizeQuantity;
use App\Models\MeasurementSubmission;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saves in-progress measurements for ONE order item as a draft submission
 * (measurement_submissions.status = 'draft'). A draft never counts toward the
 * production gate and is not reviewable. Everything is all-or-nothing.
 */
class SaveMeasurementDraft
{
    /**
     * Add (position null) or edit (position given) one person's entry.
     *
     * @throws ValidationException
     */
    public function saveEntry(string $rawToken, int $itemId, ?int $position, array $person): MeasurementSubmission
    {
        return $this->run($rawToken, $itemId, function (OrderItem $item, ?MeasurementSubmission $latest, MeasurementLink $link) use ($position, $person) {
            $clean = $this->validatedPerson($item, $person);
            $draft = $this->draftFor($item, $latest, $link, ValidateMeasurementPayload::METHOD_INDIVIDUAL);

            if ($position === null) {
                $count = IndividualMeasurement::query()->where('measurement_submission_id', $draft->id)->count();
                $max = (int) config('measurements.max_people_per_item');

                if ($count >= $max) {
                    $this->fail("items.{$item->id}", "No more than {$max} people can be saved for one item.");
                }
                if ($this->isStrict($item) && $count >= $this->quantity($item)) {
                    $this->fail("items.{$item->id}", 'All ' . $this->quantity($item) . ' entries for this item are already saved.');
                }

                IndividualMeasurement::query()->forceCreate([
                    'measurement_submission_id' => $draft->id,
                    'person_label' => $clean['label'],
                    'measurements' => $clean['measurements'],
                    'notes' => $clean['notes'],
                    'position' => $count + 1,
                ]);
            } else {
                $row = IndividualMeasurement::query()
                    ->where('measurement_submission_id', $draft->id)
                    ->where('position', $position)
                    ->first();

                if (! $row) {
                    $this->fail("items.{$item->id}", 'That entry no longer exists.');
                }

                $row->forceFill([
                    'person_label' => $clean['label'],
                    'measurements' => $clean['measurements'],
                    'notes' => $clean['notes'],
                ])->save();
            }

            $draft->touch();

            return $draft->fresh(['individualMeasurements', 'sizeQuantities']);
        });
    }

    /**
     * Remove one entry and renumber the rest. Deletes the draft when it becomes empty.
     *
     * @return MeasurementSubmission|null  null when the draft was deleted
     *
     * @throws ValidationException
     */
    public function removeEntry(string $rawToken, int $itemId, int $position): ?MeasurementSubmission
    {
        return $this->run($rawToken, $itemId, function (OrderItem $item, ?MeasurementSubmission $latest) use ($position) {
            if (! $latest || $latest->status !== 'draft') {
                $this->fail("items.{$item->id}", 'There is no saved entry to remove.');
            }

            $row = IndividualMeasurement::query()
                ->where('measurement_submission_id', $latest->id)
                ->where('position', $position)
                ->first();

            if (! $row) {
                $this->fail("items.{$item->id}", 'That entry no longer exists.');
            }

            $row->delete();

            IndividualMeasurement::query()
                ->where('measurement_submission_id', $latest->id)
                ->where('position', '>', $position)
                ->orderBy('position')
                ->get()
                ->each(fn (IndividualMeasurement $r) => $r->forceFill(['position' => $r->position - 1])->save());

            $remaining = IndividualMeasurement::query()->where('measurement_submission_id', $latest->id)->count()
                + MeasurementSizeQuantity::query()->where('measurement_submission_id', $latest->id)->count();

            if ($remaining === 0) {
                $latest->delete();

                return null;
            }

            $latest->touch();

            return $latest->fresh(['individualMeasurements', 'sizeQuantities']);
        });
    }

    /**
     * Save the commercial-size grid as a draft (replaces any individual entries).
     * Totals are not reconciled here; that happens on submit.
     *
     * @param  array<string, mixed>  $sizes
     *
     * @throws ValidationException
     */
    public function saveSizes(string $rawToken, int $itemId, array $sizes): MeasurementSubmission
    {
        return $this->run($rawToken, $itemId, function (OrderItem $item, ?MeasurementSubmission $latest, MeasurementLink $link) use ($sizes) {
            $clean = app(ValidateMeasurementPayload::class)->draftSizes($sizes);
            $draft = $this->draftFor($item, $latest, $link, ValidateMeasurementPayload::METHOD_SIZE_QUANTITY);

            MeasurementSizeQuantity::query()->where('measurement_submission_id', $draft->id)->delete();

            foreach ($clean as $label => $qty) {
                MeasurementSizeQuantity::query()->forceCreate([
                    'measurement_submission_id' => $draft->id,
                    'size_label' => $label,
                    'quantity' => $qty,
                ]);
            }

            $draft->touch();

            return $draft->fresh(['individualMeasurements', 'sizeQuantities']);
        });
    }

    /**
     * Replace the item's draft entries with rows parsed from an uploaded file.
     *
     * @param  array{method:string, people:array, sizes:array<string,int>}  $parsed
     *
     * @throws ValidationException
     */
    public function replaceFromImport(string $rawToken, int $itemId, array $parsed): MeasurementSubmission
    {
        return $this->run($rawToken, $itemId, function (OrderItem $item, ?MeasurementSubmission $latest, MeasurementLink $link) use ($parsed) {
            $draft = $this->draftFor($item, $latest, $link, $parsed['method']);

            IndividualMeasurement::query()->where('measurement_submission_id', $draft->id)->delete();
            MeasurementSizeQuantity::query()->where('measurement_submission_id', $draft->id)->delete();

            foreach (array_values($parsed['people']) as $i => $person) {
                IndividualMeasurement::query()->forceCreate([
                    'measurement_submission_id' => $draft->id,
                    'person_label' => $person['label'],
                    'measurements' => $person['measurements'],
                    'notes' => $person['notes'],
                    'position' => $i + 1,
                ]);
            }

            foreach ($parsed['sizes'] as $label => $qty) {
                MeasurementSizeQuantity::query()->forceCreate([
                    'measurement_submission_id' => $draft->id,
                    'size_label' => $label,
                    'quantity' => $qty,
                ]);
            }

            $draft->forceFill(['input_source' => 'excel'])->save();

            return $draft->fresh(['individualMeasurements', 'sizeQuantities']);
        });
    }

    private function run(string $rawToken, int $itemId, callable $work): mixed
    {
        $link = app(ResolveMeasurementLink::class)->execute($rawToken);

        if (! $link) {
            $this->fail('link', 'This link is no longer valid.');
        }

        return DB::transaction(function () use ($link, $itemId, $work) {
            $order = Order::query()->lockForUpdate()->findOrFail($link->order_id);

            $open = $order->status === Order::STATUS_APPROVED
                || ($order->status === Order::STATUS_IN_PRODUCTION && ! $order->productionPlan()->exists());
            if (! $open) {
                $this->fail('link', 'This link is no longer valid.');
            }

            $item = OrderItem::query()
                ->where('order_id', $order->id)
                ->where('measurement_eligibility', 'required')
                ->lockForUpdate()
                ->find($itemId);

            if (! $item) {
                $this->fail("items.$itemId", 'This item cannot be edited.');
            }

            $latest = MeasurementSubmission::query()
                ->where('order_item_id', $item->id)
                ->orderByDesc('revision')
                ->first();

            if ($latest && ! in_array($latest->status, ['draft', 'correction_requested'], true)) {
                $this->fail("items.$itemId", 'This item has already been submitted.');
            }

            return $work($item, $latest, $link);
        });
    }

    private function draftFor(OrderItem $item, ?MeasurementSubmission $latest, MeasurementLink $link, string $method): MeasurementSubmission
    {
        if ($latest && $latest->status === 'draft') {
            if ($latest->measurement_method !== $method) {
                IndividualMeasurement::query()->where('measurement_submission_id', $latest->id)->delete();
                MeasurementSizeQuantity::query()->where('measurement_submission_id', $latest->id)->delete();
                $latest->forceFill(['measurement_method' => $method])->save();
            }

            return $latest;
        }

        $draft = MeasurementSubmission::query()->forceCreate([
            'order_item_id' => $item->id,
            'revision' => ($latest?->revision ?? 0) + 1,
            'measurement_method' => $method,
            'input_source' => 'manual',
            'status' => 'draft',
            'measurement_link_id' => $link->id,
        ]);

        // A correction starts blank on purpose: the rejected revision stays on record
        // for audit, but the contact re-enters their data from scratch.

        return $draft;
    }

    private function validatedPerson(OrderItem $item, array $person): array
    {
        try {
            return app(ValidateMeasurementPayload::class)->person($item->product_type, $person);
        } catch (ValidationException $e) {
            $mapped = [];
            foreach ($e->errors() as $key => $messages) {
                $field = preg_match('/^people\.0\.(.+)$/', $key, $m) ? "entry.{$m[1]}" : 'entry';
                $mapped[$field] = $messages;
            }
            throw ValidationException::withMessages($mapped);
        }
    }

    private function isStrict(OrderItem $item): bool
    {
        return strtolower((string) $item->unit) === 'piece';
    }

    private function quantity(OrderItem $item): int
    {
        return (int) round((float) $item->quantity);
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
