<?php

namespace App\Actions\Quotations;

use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Support\Facades\DB;

class CreateQuotation
{
    /**
     * Create a quotation and its item snapshots atomically.
     */
    public function execute(array $data, array $items = []): Quotation
    {
        return DB::transaction(function () use ($data, $items): Quotation {
            $temporaryNumber = 'TMP-' . uniqid('', true);

            $subtotal = collect($items)
                ->sum(fn (array $item) => (float) $item['line_total']);

            $discount = (float) ($data['discount'] ?? 0);
            $additionalCharges = (float) ($data['additional_charges'] ?? 0);

            $total = max(
                0,
                $subtotal - $discount + $additionalCharges
            );

            $quotation = Quotation::create([
                ...$data,
                'quotation_number' => $temporaryNumber,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'additional_charges' => $additionalCharges,
                'total' => $total,
            ]);

            $quotation->update([
                'quotation_number' => sprintf(
                    'QUO-%06d',
                    $quotation->id
                ),
            ]);

            foreach ($items as $item) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    ...$item,
                ]);
            }

            return $quotation->fresh([
                'items',
                'organization',
                'contact',
                'createdBy',
            ]);
        });
    }
}
