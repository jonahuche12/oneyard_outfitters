<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreRemittance;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class StoreRemittancePageController extends Controller
{
    public function manage(Store $store): View
    {
        Gate::authorize('viewStore', [StoreRemittance::class, $store]);

        $dailyCashRecords = StoreDailyCashRecord::query()
            ->where('store_id', $store->id)
            ->whereNotNull('closed_by')
            ->with('remittances')
            ->orderByDesc('business_date')
            ->get();

        return view('stores.remittances.index', compact(
            'store',
            'dailyCashRecords'
        ));
    }

    public function review(): View
    {
        Gate::authorize('viewAny', StoreRemittance::class);

        return view('store-remittances.review');
    }
}
