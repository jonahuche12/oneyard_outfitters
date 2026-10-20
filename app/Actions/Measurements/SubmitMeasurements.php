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
                if (! $latest[$id] || $latest[$id]->status === 'correction_requested') {
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
            foreach ($pending as $id => $item) {
                if (! isset($payloads[$id]) || ! is_array($payloads[$id])) {
                    $this->fail("items.$id", 'Please provide measurements for "' . $item->item_name . '".');
                }
                try {
                    $clean[$id] = app(ValidateMeasurementPayload::class)
                        ->handle($item->product_type, (string) $item->unit, $item->quantity, $payloads[$id]);
                } catch (ValidationException $e) {
                    $prefixed = [];
                    foreach ($e->errors() as $key => $messages) {
                        $prefixed["items.$id.$key"] = $messages;
                    }
                    throw ValidationException::withMessages($prefixed);
                }
            }

            $created = collect();
            foreach ($pending as $id => $item) {
                $data = $clean[$id];

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

                $created->push($submission);
            }

            return $created;
        });
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
