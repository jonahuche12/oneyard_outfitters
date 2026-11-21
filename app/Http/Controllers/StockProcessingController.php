<?php

namespace App\Http\Controllers;

use App\Actions\Stock\RecordStockProcessing;
use App\Models\StockItem;
use App\Models\StockProcessing;
use App\Models\Store;
use App\Models\StoreStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockProcessingController extends Controller
{
    public function create(Request $request): View
    {
        $user = $request->user();
        $store = $this->coordinatorStore($user);
        $stores = collect();
        $isAdministrator = false;

        $rawItems = collect();
        $fabricItems = collect();
        $wasteItems = collect();

        if ($store) {
            $storeId = $store->id;

            $rawItems = StockItem::query()
                ->where('store_id', $storeId)
                ->where('is_raw', true)
                ->where('unit', StockItem::UNIT_KG)
                ->whereIn('processing_status', [
                    StockItem::PROCESSING_PENDING,
                    StockItem::PROCESSING_PROCESSING,
                ])
                ->whereHas('levels', function ($query) use ($storeId) {
                    $query->where('store_id', $storeId)
                        ->where('quantity', '>', 0);
                })
                ->with(['levels' => function ($query) use ($storeId) {
                    $query->where('store_id', $storeId);
                }])
                ->orderBy('description')
                ->get();

            $fabricItems = StockItem::query()
                ->where('store_id', $storeId)
                ->where('is_raw', false)
                ->where('unit', StockItem::UNIT_YARD)
                ->orderBy('description')
                ->get();

            $wasteItems = StockItem::query()
                ->where('store_id', $storeId)
                ->where('is_raw', false)
                ->where('unit', StockItem::UNIT_KG)
                ->where('category', 'reusable-waste')
                ->orderBy('description')
                ->get();
        }

        return view('stock.processings.create', [
            'stores' => $stores,
            'store' => $store,
            'isAdministrator' => $isAdministrator,
            'rawItems' => $rawItems,
            'fabricItems' => $fabricItems,
            'wasteItems' => $wasteItems,
        ]);
    }

    public function store(
        Request $request,
        RecordStockProcessing $action
    ): RedirectResponse {
        $user = $request->user();
        $store = $this->coordinatorStore($user);

        $processing = $action->execute(
            $user,
            $store,
            $request->except('store_id')
        );

        return redirect()
            ->route('stock.processings.show', $processing)
            ->with('success', 'Stock processing recorded and stock levels updated.');
    }

    public function show(
        Request $request,
        StockProcessing $processing
    ): View {
        $user = $request->user();
        $assignment = $user->activeStoreAssignment;

        abort_unless(
            $assignment
                && in_array($assignment->role, [
                    StoreStaff::ROLE_COORDINATOR,
                    StoreStaff::ROLE_ASSISTANT,
                ], true)
                && (int) $assignment->store_id === (int) $processing->store_id,
            403
        );

        $processing->load([
            'store',
            'rawItem',
            'processor',
            'outputs.item',
        ]);

        return view('stock.processings.show', [
            'processing' => $processing,
        ]);
    }

    private function coordinatorStore($user): Store
    {
        $assignment = $user->activeStoreAssignment;

        abort_unless(
            $assignment
                && in_array($assignment->role, [
                    StoreStaff::ROLE_COORDINATOR,
                    StoreStaff::ROLE_ASSISTANT,
                ], true)
                && $assignment->store?->is_active,
            403
        );

        return $assignment->store;
    }
}
