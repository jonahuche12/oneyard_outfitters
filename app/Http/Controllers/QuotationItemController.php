<?php

namespace App\Http\Controllers;

use App\Actions\Quotations\CalculateQuotationTotals;
use App\Models\ProductSpecification;
use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuotationItemController extends Controller
{
    public function store(
        Request $request,
        Quotation $quotation,
        CalculateQuotationTotals $calculateQuotationTotals
    ): RedirectResponse {
        Gate::authorize('update', $quotation);

        $validated = $request->validate([
            'product_specification_id' => ['nullable', 'exists:product_specifications,id'],
            'item_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit' => ['required', 'string', 'max:100'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $productSpecification = null;

        if (!empty($validated['product_specification_id'])) {
            $productSpecification = ProductSpecification::query()
                ->whereKey($validated['product_specification_id'])
                ->where('organization_id', $quotation->organization_id)
                ->whereIn('status', ['draft', 'confirmed'])
                ->first();

            if (!$productSpecification) {
                return back()
                    ->withErrors([
                        'product_specification_id' =>
                            'The selected product specification is not available for this organization.',
                    ])
                    ->withInput();
            }
        }

        /*
         * When a Product Specification is linked, its descriptive fields
         * become the quotation snapshot. The agreed unit price remains
         * editable because the quotation represents a negotiated offer.
         */
        $itemName = $productSpecification?->item_name ?? $validated['item_name'];
        $description = $productSpecification?->description
            ?? ($validated['description'] ?? null);
        $unit = $productSpecification?->unit ?? $validated['unit'];

        $quantity = (float) $validated['quantity'];
        $unitPrice = (float) $validated['unit_price'];

        $quotation->items()->create([
            'product_specification_id' => $productSpecification?->id,
            'item_name' => $itemName,
            'description' => $description,
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_price' => $unitPrice,
            'line_total' => $quantity * $unitPrice,
            'sort_order' => ($quotation->items()->max('sort_order') ?? -1) + 1,
        ]);

        $calculateQuotationTotals->execute($quotation);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('status', 'Quotation item added successfully.');
    }

    public function update(
        Request $request,
        Quotation $quotation,
        QuotationItem $item,
        CalculateQuotationTotals $calculateQuotationTotals
    ): RedirectResponse {
        Gate::authorize('update', $quotation);

        abort_unless($item->quotation_id === $quotation->id, 404);

        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit' => ['required', 'string', 'max:100'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $item->update([
            ...$validated,
            'line_total' => (float) $validated['quantity'] * (float) $validated['unit_price'],
        ]);

        $calculateQuotationTotals->execute($quotation);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('status', 'Quotation item updated successfully.');
    }

    public function destroy(
        Quotation $quotation,
        QuotationItem $item,
        CalculateQuotationTotals $calculateQuotationTotals
    ): RedirectResponse {
        Gate::authorize('update', $quotation);

        abort_unless($item->quotation_id === $quotation->id, 404);

        $item->delete();

        $calculateQuotationTotals->execute($quotation);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('status', 'Quotation item removed successfully.');
    }
}
