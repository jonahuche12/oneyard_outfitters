<?php

namespace App\Http\Controllers;

use App\Actions\Stock\AcknowledgeStockConsignment;
use App\Models\StockConsignment;
use App\Models\StockConsignmentAcknowledgementItem;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockConsignmentAcknowledgementController extends Controller
{
    public function create(Request $request, StockConsignment $consignment)
    {
        $this->authorizeDestinationCoordinator($request->user(), $consignment);

        $consignment->load([
            'sourceStore',
            'destinationStore',
            'items.item',
        ]);

        $lineIds = $consignment->items->pluck('id');

        $acknowledged = StockConsignmentAcknowledgementItem::query()
            ->whereIn('stock_consignment_item_id', $lineIds)
            ->select('stock_consignment_item_id')
            ->selectRaw('SUM(quantity_good + quantity_damaged + quantity_shortage) as quantity_accounted')
            ->groupBy('stock_consignment_item_id')
            ->pluck('quantity_accounted', 'stock_consignment_item_id');

        $lines = $consignment->items->map(function ($line) use ($acknowledged) {
            $accounted = (float) ($acknowledged[$line->id] ?? 0);
            $line->quantity_accounted = $accounted;
            $line->quantity_remaining = max(
                0,
                (float) $line->quantity_dispatched - $accounted
            );

            return $line;
        })->filter(fn ($line) => $line->quantity_remaining > 0);

        return view('stock.consignments.acknowledge', compact(
            'consignment',
            'lines'
        ));
    }

    public function store(
        Request $request,
        StockConsignment $consignment,
        AcknowledgeStockConsignment $action
    ) {
        $this->authorizeDestinationCoordinator($request->user(), $consignment);

        $acknowledgement = $action->execute(
            $request->user(),
            $consignment,
            $request->only(['items', 'notes'])
        );

        return redirect()
            ->route('stock.consignments.show', $consignment)
            ->with('success', 'Consignment acknowledgement recorded.');
    }

    private function authorizeDestinationCoordinator(
        User $actor,
        StockConsignment $consignment
    ): void {
        abort_unless($actor->is_active, 403);

        $destination = Store::query()->findOrFail($consignment->destination_store_id);

        abort_unless($destination->is_active, 403);

        $assignment = $actor->activeStoreAssignment;

        abort_unless(
            $assignment
            && in_array($assignment->role, [
                StoreStaff::ROLE_COORDINATOR,
                StoreStaff::ROLE_ASSISTANT,
            ], true)
            && (int) $assignment->store_id === (int) $destination->id,
            403
        );
    }
}
