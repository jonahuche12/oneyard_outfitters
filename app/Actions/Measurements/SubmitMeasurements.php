<?php

namespace App\Actions\Measurements;

use App\Models\IndividualMeasurement;
use App\Models\MeasurementSizeQuantity;
use App\Models\MeasurementSubmission;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitMeasurements
{
    /**
     * @param  array<int|string, array>  $payloads  keyed by order item id
     * @return Collection<int, MeasurementSubmission>
     *
     * @throws ValidationException
     */
    public function execute(string $rawToken, array $payloads): Collection
    {
        $link = app(ResolveMeasurementLink::class)->execute($rawToken);

        if (! $link) {
            $this->fail('link', 'This link is no longer valid.');
        }

        return DB::transaction(function () use ($link, $payloads): Collection {
            $order = Order::query()->lockForUpdate()->findOrFail($link->order_id);

            $open = $order->status === Order::STATUS_APPROVED
                || ($order->status === Order::STATUS_IN_PRODUCTION && ! $order->productionPlan()->exists());
            if (! $open) {
                $this->fail('link', 'This link is no longer valid.');
            }

            $items = OrderItem::query()
                ->where('order_id', $order->id)
                ->where('measurement_eligibility', 'required')
                ->orderBy('sort_order')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $latest = [];
            $pending = [];
            foreach ($items as $id => $item) {
                $latest[$id] = MeasurementSubmission::query()
                    ->where('order_item_id', $id)->orderByDesc('revision')->first();
                if (! $latest[$id] || in_array($latest[$id]->status, ['draft', 'correction_requested'], true)) {
                    $pending[$id] = $item;
                }
            }

            if ($pending === []) {
                $this->fail('items', 'There is nothing to submit. Your measurements are already with Oneyard.');
            }

            foreach (array_keys($payloads) as $id) {
                if (! isset($pending[(int) $id])) {
                    $this->fail("items.$id", 'This item cannot be submitted.');
                }
            }

            $clean = [];
            $switched = [];
            foreach ($pending as $id => $item) {
                $existing = $latest[$id];

                // Saved drafts are the source of truth; never depend on the browser
                // to resend measurements that are already stored on the server.
                if ($existing && $existing->status === 'draft') {
                    // The contact's current choice is authoritative. A change of
                    // method replaces whatever was collected under the old one.
                    $selectedMethod = is_array($payloads[$id] ?? null)
                        ? ($payloads[$id]['method'] ?? null)
                        : null;

                    if (! in_array($selectedMethod, ['individual', 'size_quantity'], true)) {
                        $this->fail("items.$id.method", 'Please choose how you want to provide measurements.');
                    }

                    if ($selectedMethod !== $existing->measurement_method) {
                        $existing->individualMeasurements()->delete();
                        $existing->sizeQuantities()->delete();
                        $existing->forceFill(['measurement_method' => $selectedMethod])->save();
                        $switched[$id] = true;
                        $source = $payloads[$id];
                    } else {
                        $existing->load(['individualMeasurements', 'sizeQuantities']);

                        $source = [
                            'method' => $existing->measurement_method,
                            'people' => $existing->individualMeasurements
                                ->sortBy('position')
                                ->map(fn ($person) => [
                                    'label' => $person->person_label,
                                    'measurements' => $person->measurements,
                                    'notes' => $person->notes,
                                ])->values()->all(),
                            'sizes' => $existing->sizeQuantities
                                ->pluck('quantity', 'size_label')->all(),
                        ];
                    }
                } else {
                    if (! isset($payloads[$id]) || ! is_array($payloads[$id])) {
                        $this->fail("items.$id", 'Please provide measurements for "' . $item->item_name . '".');
                    }

                    $source = $payloads[$id];

                }

                try {
                    $clean[$id] = app(ValidateMeasurementPayload::class)
                        ->handle($item->product_type, (string) $item->unit, $item->quantity, $source);
                } catch (ValidationException $e) {
                    $prefixed = [];
                    foreach ($e->errors() as $key => $messages) {
                        $prefixed["items.$id.$key"] = $messages;
                    }
                    throw ValidationException::withMessages($prefixed);
                }

            }

            foreach ($pending as $id => $item) {
                if (
                    (! ($latest[$id] && $latest[$id]->status === 'draft') || isset($switched[$id]))
                    && app(ValidateMeasurementPayload::class)->profileFor($item->product_type)
                    && ($clean[$id]['method'] ?? null) === 'individual'
                ) {
                    $this->fail(
                        "items.$id.people",
                        'Save each person’s measurements as a draft before submitting.'
                    );
                }
            }

            $created = collect();
            foreach ($pending as $id => $item) {
                $data = $clean[$id];

                if ($latest[$id] && $latest[$id]->status === 'draft') {
                    // Validation above checks the stored draft against the item's
                    // complete requirements before it becomes reviewable.
                    $submission = $latest[$id];
                    $submission->forceFill([
                        'measurement_method' => $data['method'],
                        'status' => 'submitted',
                        'submitted_at' => now(),
                        'measurement_link_id' => $link->id,
                    ])->save();

                    if (isset($switched[$id])) {
                        $this->storeRows($submission, $data);
                    }
                } else {
                    $submission = MeasurementSubmission::query()->forceCreate([
                        'order_item_id' => $id,
                        'revision' => ($latest[$id]?->revision ?? 0) + 1,
                        'measurement_method' => $data['method'],
                        'input_source' => 'manual',
                        'status' => 'submitted',
                        'submitted_at' => now(),
                        'measurement_link_id' => $link->id,
                    ]);

                    foreach ($data['people'] as $person) {
                        IndividualMeasurement::query()->forceCreate([
                            'measurement_submission_id' => $submission->id,
                            'person_label' => $person['label'],
                            'measurements' => $person['measurements'],
                            'notes' => $person['notes'],
                            'position' => $person['position'],
                        ]);
                    }

                    foreach ($data['sizes'] as $label => $qty) {
                        MeasurementSizeQuantity::query()->forceCreate([
                            'measurement_submission_id' => $submission->id,
                            'size_label' => $label,
                            'quantity' => $qty,
                        ]);
                    }
                }

                $created->push($submission);
            }

            return $created;
        });
    }

    /**
     * @param  array{people: array, sizes: array}  $data
     */
    private function storeRows(MeasurementSubmission $submission, array $data): void
    {
        foreach ($data['people'] as $person) {
            IndividualMeasurement::query()->forceCreate([
                'measurement_submission_id' => $submission->id,
                'person_label' => $person['label'],
                'measurements' => $person['measurements'],
                'notes' => $person['notes'],
                'position' => $person['position'],
            ]);
        }

        foreach ($data['sizes'] as $label => $qty) {
            MeasurementSizeQuantity::query()->forceCreate([
                'measurement_submission_id' => $submission->id,
                'size_label' => $label,
                'quantity' => $qty,
            ]);
        }
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
