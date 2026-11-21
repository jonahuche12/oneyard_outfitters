<?php

namespace App\Http\Controllers;

use App\Actions\Stock\DispatchStockConsignmentReturn;
use App\Actions\Stock\ReceiveStockConsignmentReturn;
use App\Models\StockConsignment;
use App\Models\StockConsignmentReturn;
use App\Models\StockLevel;
use App\Models\StoreStaff;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockConsignmentReturnController extends Controller
{
    public function create(Request $request, StockConsignment $consignment): View
    {
        $actor = $request->user();
        $this->authorizeStoreStaff($actor->id, $consignment->destination_store_id, [
            StoreStaff::ROLE_COORDINATOR,
            StoreStaff::ROLE_ASSISTANT,
        ]);

        abort_if(
            $consignment->status === StockConsignment::STATUS_CANCELLED,
            422,
            'Cancelled consignments cannot be returned.'
        );

        $good = DB::table('stock_consignment_acknowledgement_items as ai')
            ->join('stock_consignment_acknowledgements as a', 'a.id', '=', 'ai.acknowledgement_id')
            ->where('a.stock_consignment_id', $consignment->id)
            ->groupBy('ai.stock_consignment_item_id')
            ->selectRaw('ai.stock_consignment_item_id, SUM(ai.quantity_good) as quantity_good')
            ->pluck('quantity_good', 'stock_consignment_item_id');

        $returned = DB::table('stock_consignment_return_items as ri')
            ->join('stock_consignment_returns as r', 'r.id', '=', 'ri.stock_consignment_return_id')
            ->where('r.stock_consignment_id', $consignment->id)
            ->groupBy('ri.stock_consignment_item_id')
            ->selectRaw('ri.stock_consignment_item_id, SUM(ri.quantity_dispatched) as quantity_returned')
            ->pluck('quantity_returned', 'stock_consignment_item_id');

        $consignment->load('items.item');

        $levels = StockLevel::query()
            ->where('store_id', $consignment->destination_store_id)
            ->whereIn('stock_item_id', $consignment->items->pluck('stock_item_id'))
            ->get()
            ->keyBy('stock_item_id');

        $lines = $consignment->items->map(function ($line) use ($good, $returned, $levels) {
            $goodQuantity = (string) ($good[$line->id] ?? '0.000');
            $returnedQuantity = (string) ($returned[$line->id] ?? '0.000');
            $remainingGood = bcsub($goodQuantity, $returnedQuantity, 3);
            $level = $levels->get($line->stock_item_id);
            $available = (string) ($level?->quantity ?? '0.000');
            $maximum = bccomp($remainingGood, $available, 3) < 0
                ? $remainingGood
                : $available;

            return [
                'line' => $line,
                'good' => $goodQuantity,
                'returned' => $returnedQuantity,
                'available' => $available,
                'maximum' => $maximum,
            ];
        })->filter(fn ($row) => bccomp($row['maximum'], '0', 3) > 0);

        return view('stock.consignments.returns.create', compact('consignment', 'lines'));
    }

    public function store(
        Request $request,
        StockConsignment $consignment,
        DispatchStockConsignmentReturn $dispatch
    ): RedirectResponse {
        $stockReturn = $dispatch->execute(
            $request->user(),
            $consignment,
            $request->only('items', 'notes')
        );

        return redirect()
            ->route('stock.consignments.show', $consignment)
            ->with('success', 'Return dispatched: '.$stockReturn->reference);
    }

    public function receive(
        Request $request,
        StockConsignmentReturn $stockReturn
    ): View {
        $consignment = $stockReturn->consignment;
        $this->authorizeStoreStaff(
            $request->user()->id,
            $consignment->source_store_id,
            [StoreStaff::ROLE_COORDINATOR]
        );

        abort_if($stockReturn->received_at !== null, 422, 'This return has already been received.');

        $stockReturn->load('consignment.sourceStore', 'items.consignmentItem.item');

        return view('stock.consignments.returns.receive', compact('stockReturn'));
    }

    public function confirmReceipt(
        Request $request,
        StockConsignmentReturn $stockReturn,
        ReceiveStockConsignmentReturn $receive
    ): RedirectResponse {
        $receive->execute(
            $request->user(),
            $stockReturn,
            $request->only('items', 'notes')
        );

        return redirect()
            ->route('stock.consignments.show', $stockReturn->stock_consignment_id)
            ->with('success', 'Return receipt recorded.');
    }

    private function authorizeStoreStaff(int $userId, int $storeId, array $roles): void
    {
        $allowed = StoreStaff::query()
            ->where('user_id', $userId)
            ->where('store_id', $storeId)
            ->whereIn('role', $roles)
            ->whereNull('ends_at')
            ->exists();

        if (! $allowed) {
            throw new AuthorizationException('You are not authorised to perform this store operation.');
        }
    }
}
