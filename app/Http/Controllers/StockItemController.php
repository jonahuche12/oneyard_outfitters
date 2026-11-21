<?php

namespace App\Http\Controllers;

use App\Actions\Stock\CreateStockItem;
use App\Actions\Stock\UpdateStockItem;
use App\Models\StockItem;
use App\Models\StockItemPhoto;
use App\Models\StockLevel;
use Illuminate\Support\Facades\Storage;
use App\Models\StoreStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockItemController extends Controller
{
    public function index(Request $request): View
    {
        $assignment = $request->user()->activeStoreAssignment;

        abort_unless($assignment, 403);

        $items = StockItem::query()
            ->with([
                'store',
                'creator',
                'photos',
                'levels' => fn ($query) => $query->where('store_id', $assignment->store_id),
            ])
            ->whereHas('levels', fn ($query) => $query->where('store_id', $assignment->store_id))
            ->latest()
            ->paginate(20);

        return view('stock.index', [
            'items' => $items,
            'stores' => collect(),
            'selectedStoreId' => $assignment->store_id,
            'isAdministrator' => false,
            'canManageItems' => $assignment->role === StoreStaff::ROLE_COORDINATOR && $assignment->store?->is_active,
        ]);
    }

    public function create(Request $request): View
    {
        $assignment = $request->user()->activeStoreAssignment;

        abort_unless(
            $assignment
                && $assignment->role === StoreStaff::ROLE_COORDINATOR
                && $assignment->store?->is_active,
            403
        );

        return view('stock.create', [
            'stores' => collect(),
            'selectedStoreId' => $assignment->store_id,
            'isAdministrator' => false,
            'coordinatorStore' => $assignment->store,
        ]);
    }

    public function store(Request $request, CreateStockItem $action): RedirectResponse
    {
        $assignment = $request->user()->activeStoreAssignment;

        abort_unless(
            $assignment
                && $assignment->role === StoreStaff::ROLE_COORDINATOR
                && $assignment->store?->is_active,
            403
        );

        $store = $assignment->store;

        $action->execute($request->user(), $store, $request->only([
            'description',
            'category',
            'grade',
            'unit',
            'is_raw',
            'quantity',
            'unit_cost',
            'suggested_price',
            'current_market_price',
            'photos',
        ]));

        return redirect()
            ->route('stores.show', $store)
            ->with('success', 'Stock item, opening balance and photos registered successfully.');
    }

    public function edit(Request $request, StockItem $stockItem): View
    {
        $assignment = $request->user()->activeStoreAssignment;

        abort_unless(
            $assignment
                && $assignment->role === StoreStaff::ROLE_COORDINATOR
                && $assignment->store?->is_active
                && (int) $stockItem->store_id === (int) $assignment->store_id,
            403
        );

        $stockItem->load(['store', 'photos']);

        return view('stock.edit', [
            'item' => $stockItem,
            'coordinatorStore' => $assignment->store,
        ]);
    }

    public function update(Request $request, StockItem $stockItem, UpdateStockItem $action): RedirectResponse
    {
        $assignment = $request->user()->activeStoreAssignment;

        abort_unless(
            $assignment
                && $assignment->role === StoreStaff::ROLE_COORDINATOR
                && $assignment->store?->is_active
                && (int) $stockItem->store_id === (int) $assignment->store_id,
            403
        );

        $action->execute($request->user(), $stockItem, $request->only([
            'description',
            'category',
            'grade',
            'suggested_price',
            'current_market_price',
            'photos',
        ]));

        return redirect()
            ->route('stores.show', $assignment->store)
            ->with('success', 'Stock item details updated successfully.');
    }



    /**
     * Display an item and its inventory position at the actor's assigned store.
     */
    public function show(Request $request, StockItem $stockItem): View
    {
        $assignment = $request->user()->activeStoreAssignment;
        abort_unless($assignment, 403);

        $level = StockLevel::query()
            ->where('stock_item_id', $stockItem->id)
            ->where('store_id', $assignment->store_id)
            ->first();

        abort_unless($level, 404);

        $stockItem->load([
            'store',
            'creator',
            'photos.uploader',
        ]);

        $movements = $stockItem->movements()
            ->where('store_id', $assignment->store_id)
            ->with('actor')
            ->latest()
            ->paginate(30);

        return view('stock.show', [
            'item' => $stockItem,
            'level' => $level->load('store'),
            'movements' => $movements,
            'canManageItems' => $assignment->role === StoreStaff::ROLE_COORDINATOR
                && $assignment->store?->is_active
                && (int) $stockItem->store_id === (int) $assignment->store_id,
        ]);
    }

    /**
     * Stream a private stock photo only to a user assigned to a store holding the item.
     */
    public function showPhoto(
        Request $request,
        StockItem $stockItem,
        StockItemPhoto $photo
    ) {
        $assignment = $request->user()->activeStoreAssignment;
        abort_unless($assignment, 403);

        abort_unless(
            (int) $photo->stock_item_id === (int) $stockItem->id,
            404
        );

        $holdsStock = StockLevel::query()
            ->where('stock_item_id', $stockItem->id)
            ->where('store_id', $assignment->store_id)
            ->exists();

        abort_unless($holdsStock, 404);

        abort_unless(
            Storage::disk('local')->exists($photo->file_path),
            404
        );

        return Storage::disk('local')->response(
            $photo->file_path,
            $photo->original_name,
            [
                'Content-Type' => $photo->mime_type,
                'Content-Disposition' => 'inline; filename="'.addslashes($photo->original_name).'"',
            ]
        );
    }

}
