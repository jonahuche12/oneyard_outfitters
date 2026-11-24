<?php

namespace App\Http\Controllers;

use App\Actions\Stores\ReviewStoreRemittance;
use App\Actions\Stores\SubmitStoreRemittance;
use App\Models\Store;
use App\Models\StoreRemittance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StoreRemittanceController extends Controller
{
    public function index(Store $store): JsonResponse
    {
        Gate::authorize('viewStore', [StoreRemittance::class, $store]);

        $remittances = StoreRemittance::query()
            ->where('store_id', $store->id)
            ->orderByDesc('business_date')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($remittances);
    }

    public function store(
        Request $request,
        Store $store,
        SubmitStoreRemittance $action
    ): JsonResponse {
        Gate::authorize('submit', [StoreRemittance::class, $store]);

        $remittance = $action->execute(
            $request->user(),
            $store,
            $request->only([
                'store_daily_cash_record_id',
                'amount_cash',
                'amount_bank',
                'method',
                'reference',
                'note',
                'resubmits_id',
            ])
        );

        return response()->json(['data' => $remittance], 201);
    }

    public function queue(): JsonResponse
    {
        Gate::authorize('viewAny', StoreRemittance::class);

        $remittances = StoreRemittance::query()
            ->where('status', StoreRemittance::STATUS_PENDING)
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->paginate(20);

        return response()->json($remittances);
    }

    public function confirm(
        Request $request,
        StoreRemittance $remittance,
        ReviewStoreRemittance $action
    ): JsonResponse {
        Gate::authorize('review', $remittance);

        $validated = $request->validate([
            'review_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $reviewed = $action->execute(
            $request->user(),
            $remittance,
            'confirm',
            $validated['review_reason'] ?? null
        );

        return response()->json(['data' => $reviewed]);
    }

    public function reject(
        Request $request,
        StoreRemittance $remittance,
        ReviewStoreRemittance $action
    ): JsonResponse {
        Gate::authorize('review', $remittance);

        $validated = $request->validate([
            'review_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $reviewed = $action->execute(
            $request->user(),
            $remittance,
            'reject',
            $validated['review_reason']
        );

        return response()->json(['data' => $reviewed]);
    }
}
