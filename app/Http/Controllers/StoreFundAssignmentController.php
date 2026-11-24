<?php

namespace App\Http\Controllers;

use App\Actions\Stores\ApproveStoreFundAssignment;
use App\Actions\Stores\ConfirmStoreFundAssignmentReceipt;
use App\Actions\Stores\CorrectStoreFundAssignmentDispatch;
use App\Actions\Stores\CreateDirectStoreFundAssignment;
use App\Actions\Stores\DispatchStoreFundAssignment;
use App\Actions\Stores\RejectStoreFundAssignment;
use App\Actions\Stores\RequestStoreFunds;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\StoreFundAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StoreFundAssignmentController extends Controller
{
    /**
     * Role-aware Store Funds workspace.
     *
     * Administrators can view requests, create direct assignments,
     * and inspect the complete assignment history.
     *
     * Finance reviewers are restricted to store-originated requests.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StoreFundAssignment::class);

        $isFinanceReviewer = Gate::allows(
            'reviewRequests',
            StoreFundAssignment::class
        );

        $activeStoreAssignment = $request->user()->activeStoreAssignment;

        $coordinatorStore = $activeStoreAssignment?->role === StoreStaff::ROLE_COORDINATOR
            ? $activeStoreAssignment->store
            : null;

        if ($coordinatorStore && ! $coordinatorStore->is_active) {
            $coordinatorStore = null;
        }

        $tab = (string) $request->query('tab', 'requests');

        if ($isFinanceReviewer) {
            // Finance must never reach direct assignments or all-source history.
            $tab = 'requests';
        } else {
            Gate::authorize('viewManagementHistory', StoreFundAssignment::class);

            if (! in_array($tab, ['requests', 'assign', 'history'], true)) {
                $tab = 'requests';
            }
        }

        $assignmentsQuery = StoreFundAssignment::query()
            ->with([
                'store',
                'requestedBy',
                'handledBy',
                'approvedBy',
                'dispatchedBy',
                'confirmedBy',
            ]);

        if ($tab === 'requests') {
            $assignmentsQuery->where(
                'source',
                StoreFundAssignment::SOURCE_STORE_REQUEST
            );
        } elseif ($tab === 'assign') {
            $assignmentsQuery->whereRaw('1 = 0');
        }

        $assignments = $assignmentsQuery
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $stores = collect();

        if ($tab === 'assign') {
            $stores = Store::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->filter(fn (Store $store): bool => Gate::allows(
                    'createDirect',
                    [StoreFundAssignment::class, $store]
                ))
                ->values();
        }

        return view('store-fund-assignments.index', [
            'mode' => $isFinanceReviewer ? 'requests' : 'admin',
            'tab' => $tab,
            'assignments' => $assignments,
            'store' => null,
            'stores' => $stores,
            'coordinatorStore' => $coordinatorStore,
            'isFinanceReviewer' => $isFinanceReviewer,
        ]);
    }

    /**
     * Legacy URL retained for existing bookmarks and links.
     */
    public function assign(): RedirectResponse
    {
        Gate::authorize('viewManagementHistory', StoreFundAssignment::class);

        return redirect()->route('store-fund-assignments.index', [
            'tab' => 'assign',
        ]);
    }

    /**
     * Legacy request-queue URL retained as a redirect.
     */
    public function requests(): RedirectResponse
    {
        Gate::authorize('viewAny', StoreFundAssignment::class);

        return redirect()->route('store-fund-assignments.index', [
            'tab' => 'requests',
        ]);
    }

    /**
     * Store-scoped page for coordinators, assistants and authorized administrators.
     */
    public function storeIndex(Store $store): View
    {
        Gate::authorize('viewStore', [StoreFundAssignment::class, $store]);

        $assignments = StoreFundAssignment::query()
            ->where('store_id', $store->id)
            ->with([
                'store',
                'requestedBy',
                'handledBy',
                'approvedBy',
                'dispatchedBy',
                'confirmedBy',
            ])
            ->latest('id')
            ->paginate(30);

        return view('store-fund-assignments.index', [
            'mode' => 'store',
            'tab' => 'store',
            'assignments' => $assignments,
            'store' => $store,
            'stores' => collect(),
            'coordinatorStore' => null,
            'isFinanceReviewer' => false,
        ]);
    }

    public function requestFunds(
        Request $request,
        Store $store,
        RequestStoreFunds $action
    ): RedirectResponse {
        $action->execute($store, $request->only([
            'purpose',
            'requested_cash',
            'requested_bank',
            'note',
            'reference',
        ]));

        return redirect()
            ->route('stores.fund-assignments.index', $store)
            ->with('success', 'Funds request submitted.');
    }

    public function createDirect(
        Request $request,
        Store $store,
        CreateDirectStoreFundAssignment $action
    ): RedirectResponse {
        $action->execute($store, $request->only([
            'purpose',
            'requested_cash',
            'requested_bank',
            'note',
            'reference',
        ]));

        return redirect()
            ->route('store-fund-assignments.index', [
                'tab' => 'history',
            ])
            ->with('success', 'Direct fund assignment created. Dispatch it when ready.');
    }

    public function approve(
        StoreFundAssignment $assignment,
        ApproveStoreFundAssignment $action
    ): RedirectResponse {
        $action->execute($assignment);

        return back()->with('success', 'Funds request approved.');
    }

    public function reject(
        Request $request,
        StoreFundAssignment $assignment,
        RejectStoreFundAssignment $action
    ): RedirectResponse {
        $action->execute(
            $assignment,
            (string) $request->input('reason', '')
        );

        return back()->with('success', 'Funds request rejected.');
    }

    public function dispatch(
        Request $request,
        StoreFundAssignment $assignment,
        DispatchStoreFundAssignment $action
    ): RedirectResponse {
        $action->execute($assignment, $request->only([
            'sent_cash',
            'sent_bank',
        ]));

        return back()->with('success', 'Funds dispatch recorded.');
    }

    public function correctDispatch(
        Request $request,
        StoreFundAssignment $assignment,
        CorrectStoreFundAssignmentDispatch $action
    ): RedirectResponse {
        $action->execute($assignment, $request->only([
            'sent_cash',
            'sent_bank',
        ]));

        return back()->with('success', 'Dispatch correction recorded.');
    }

    public function confirm(
        Request $request,
        StoreFundAssignment $assignment,
        ConfirmStoreFundAssignmentReceipt $action
    ): RedirectResponse {
        $action->execute($assignment, $request->only([
            'received_cash',
            'received_bank',
        ]));

        return back()->with('success', 'Funds receipt confirmed.');
    }
}
