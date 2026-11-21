<?php

namespace App\Http\Controllers;

use App\Actions\Stock\RegisterStoreCustomer;
use App\Models\Customer;
use App\Models\Store;
use App\Models\StoreStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreCustomersController extends Controller
{
    public function index(Request $request, Store $store): View
    {
        $this->authorizeStoreAccess($request, $store);

        $search = trim((string) $request->query('search', ''));

        $customers = Customer::query()
            ->where('store_id', $store->id)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($customerQuery) use ($search): void {
                    $customerQuery
                        ->where('customer_code', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('stores.customers.index', compact('store', 'customers', 'search'));
    }

    public function create(Request $request, Store $store): View
    {
        $this->authorizeStoreAccess($request, $store);

        $returnToSale = $request->query('return_to') === 'sale';

        return view('stores.customers.create', compact('store', 'returnToSale'));
    }

    public function store(
        Request $request,
        Store $store,
        RegisterStoreCustomer $registerCustomer
    ): RedirectResponse {
        $this->authorizeStoreAccess($request, $store);

        $customer = $registerCustomer->execute(
            $request->user(),
            $store,
            $request->only([
                'name', 'phone', 'alternate_phone', 'email', 'address', 'notes',
            ])
        );

        if ($request->input('return_to') === 'sale') {
            return redirect()
                ->route('stores.sales.create', [
                    'store' => $store,
                    'customer_id' => $customer->id,
                ])
                ->with('success', 'Customer registered. Complete the sale below.');
        }

        return redirect()
            ->route('stores.customers.index', $store)
            ->with('success', 'Customer registered successfully.');
    }

    private function authorizeStoreAccess(Request $request, Store $store): void
    {
        $user = $request->user();

        abort_unless($user && $user->is_active && $store->is_active, 403);

        abort_unless(
            StoreStaff::query()
                ->active()
                ->where('user_id', $user->id)
                ->where('store_id', $store->id)
                ->whereIn('role', [
                    StoreStaff::ROLE_COORDINATOR,
                    StoreStaff::ROLE_ASSISTANT,
                ])
                ->exists(),
            403
        );
    }
}
