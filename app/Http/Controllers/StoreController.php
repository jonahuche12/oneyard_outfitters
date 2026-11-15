<?php

namespace App\Http\Controllers;

use App\Actions\Stores\CreateStore;
use App\Actions\Stores\SetStoreActive;
use App\Actions\Stores\UpdateStore;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Store::class);

        $search = trim((string) $request->input('search'));

        $stores = Store::query()
            ->with('coordinatorAssignment.user')
            ->withCount('activeStaff')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('general_warehouse_slot') // the warehouse first
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('stores.index', [
            'stores' => $stores,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Store::class);

        return view('stores.create', [
            'types' => Store::TYPES,
            'warehouseExists' => Store::query()->where('type', Store::TYPE_WAREHOUSE)->exists(),
        ]);
    }

    public function store(Request $request, CreateStore $action): RedirectResponse
    {
        Gate::authorize('create', Store::class);

        $store = $action->execute(
            $request->user(),
            $request->only(['name', 'code', 'type', 'address', 'phone'])
        );

        return redirect()
            ->route('stores.show', $store)
            ->with('success', 'Store created.');
    }

    public function show(Store $store): View
    {
        Gate::authorize('view', $store);

        $store->load(['coordinatorAssignment.user', 'assistantAssignments.user', 'creator']);

        $history = Gate::allows('manageStaff', $store)
            ? $store->staffAssignments()
                ->with(['user', 'assignedBy', 'endedBy'])
                ->orderByDesc('starts_at')
                ->orderByDesc('id')
                ->get()
            : collect();

        $canManageStaff = Gate::allows('manageStaff', $store) && ! $store->isWarehouse();
        $canAssign = $canManageStaff && $store->is_active;

        // Only active users who currently belong to no store can be assigned.
        $assignableUsers = $canAssign
            ? User::query()
                ->where('is_active', true)
                ->whereDoesntHave('activeStoreAssignment')
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
            : collect();

        $destinationStores = $canManageStaff
            ? Store::query()
                ->where('type', Store::TYPE_STORE)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        return view('stores.show', [
            'store' => $store,
            'history' => $history,
            'canManageStaff' => $canManageStaff,
            'canAssign' => $canAssign,
            'assignableUsers' => $assignableUsers,
            'destinationStores' => $destinationStores,
        ]);
    }

    /**
     * Send a staff member to the store they currently belong to.
     */
    public function mine(Request $request): RedirectResponse
    {
        $assignment = $request->user()->activeStoreAssignment;

        if (! $assignment) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('stores.show', $assignment->store_id);
    }

    public function edit(Store $store): View
    {
        Gate::authorize('update', $store);

        return view('stores.edit', ['store' => $store]);
    }

    public function update(Request $request, Store $store, UpdateStore $action): RedirectResponse
    {
        Gate::authorize('update', $store);

        $action->execute(
            $request->user(),
            $store,
            $request->only(['name', 'code', 'address', 'phone'])
        );

        return redirect()
            ->route('stores.show', $store)
            ->with('success', 'Store updated.');
    }

    public function activate(Request $request, Store $store, SetStoreActive $action): RedirectResponse
    {
        Gate::authorize('toggleActive', $store);

        $action->execute($request->user(), $store, true);

        return redirect()
            ->route('stores.show', $store)
            ->with('success', 'Store activated.');
    }

    public function deactivate(Request $request, Store $store, SetStoreActive $action): RedirectResponse
    {
        Gate::authorize('toggleActive', $store);

        $action->execute($request->user(), $store, false);

        return redirect()
            ->route('stores.show', $store)
            ->with('success', 'Store deactivated.');
    }
}
