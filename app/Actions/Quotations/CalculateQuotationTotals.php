<?php

namespace App\Actions\Quotations;

use App\Models\Quotation;

class CalculateQuotationTotals
{
    public function execute(Quotation $quotation): Quotation
    {
        $subtotal = $quotation->items()
            ->get()
            ->sum(fn ($item) => (float) $item->line_total);

        $discount = (float) $quotation->discount;
        $additionalCharges = (float) $quotation->additional_charges;

        $total = max(
            0,
            $subtotal - $discount + $additionalCharges
        );

        $quotation->update([
            'subtotal' => $subtotal,
            'total' => $total,
        ]);

        return $quotation->fresh();
    }
}
