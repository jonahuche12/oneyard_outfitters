<?php

namespace App\Http\Controllers;

use App\Actions\Stock\DispatchStockConsignment;
use App\Models\StockConsignment;
use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockConsignmentController extends Controller
{
    public function create(Request $request): View
    {
        $actor = $request->user();
        $isAdmin = false;
        $sourceStore = $this->coordinatorStore($actor);

        $items = collect();
        $destinations = collect();

        if ($sourceStore) {
            $items = StockItem::query()
                ->whereHas('levels', function ($query) use ($sourceStore) {
                    $query->where('store_id', $sourceStore->id)
                        ->where('quantity', '>', 0);
                })
                ->with(['levels' => function ($query) use ($sourceStore) {
                    $query->where('store_id', $sourceStore->id);
                }])
                ->orderBy('description')
                ->get();

            $destinations = Store::query()
                ->where('is_active', true)
                ->where('id', '!=', $sourceStore->id)
                ->orderBy('name')
                ->get();
        }

        return view('stock.consignments.create', compact(
            'isAdmin',
            'sourceStore',
            'items',
            'destinations'
        ));
    }

    public function store(
        Request $request,
        DispatchStockConsignment $dispatch
    ): RedirectResponse {
        $actor = $request->user();
        $source = $this->coordinatorStore($actor);

        $request->validate([
            'destination_store_id' => [
                'required',
                'integer',
                'exists:stores,id',
            ],
        ]);

        $destination = Store::query()
            ->where('is_active', true)
            ->findOrFail($request->integer('destination_store_id'));

        abort_unless((int) $destination->id !== (int) $source->id, 422);

        $consignment = $dispatch->execute(
            $actor,
            $source,
            $destination,
            $request->only('items', 'notes')
        );

        return redirect()
            ->route('stock.consignments.show', $consignment)
            ->with('success', 'Stock consignment dispatched successfully.');
    }

    public function show(Request $request, StockConsignment $consignment): View
    {
        $actor = $request->user();

        $canView = StoreStaff::query()
            ->where('user_id', $actor->id)
            ->whereNull('ends_at')
            ->whereIn('store_id', [
                $consignment->source_store_id,
                $consignment->destination_store_id,
            ])
            ->exists();

        if (! $canView) {
            throw new AuthorizationException(
                'You are not assigned to either store involved in this consignment.'
            );
        }

        $consignment->load([
            'sourceStore',
            'destinationStore',
            'creator',
            'dispatcher',
            'items.item',
            'items.discrepancies.reporter',
            'items.discrepancies.resolver',
            'returns.dispatcher',
            'returns.receiver',
            'returns.items.consignmentItem.item',
        ]);

        $assignment = $actor->activeStoreAssignment;
        $canDispatchReturn = $assignment
            && (int) $assignment->store_id === (int) $consignment->destination_store_id
            && in_array($assignment->role, [
                StoreStaff::ROLE_COORDINATOR,
                StoreStaff::ROLE_ASSISTANT,
            ], true)
            && $consignment->status !== StockConsignment::STATUS_CANCELLED;

        $canReceiveReturn = $assignment
            && (int) $assignment->store_id === (int) $consignment->source_store_id
            && $assignment->role === StoreStaff::ROLE_COORDINATOR;

        return view('stock.consignments.show', compact(
            'consignment',
            'canDispatchReturn',
            'canReceiveReturn'
        ));
    }

    private function coordinatorStore(User $actor): Store
    {
        $assignment = $actor->activeStoreAssignment;

        if (
            ! $assignment ||
            ! in_array($assignment->role, [
                StoreStaff::ROLE_COORDINATOR,
                StoreStaff::ROLE_ASSISTANT,
            ], true) ||
            ! $assignment->store ||
            ! $assignment->store->is_active
        ) {
            throw new AuthorizationException(
                'Only an active store coordinator can dispatch stock.'
            );
        }

        return $assignment->store;
    }
}
