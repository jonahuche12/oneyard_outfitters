<?php

namespace App\Http\Controllers;

use App\Actions\Stores\RecordStoreExpense;
use App\Models\Store;
use App\Models\StoreDailyCashRecord;
use App\Models\StoreExpense;
use App\Models\StoreStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreExpensesController extends Controller
{
    public function index(Request $request, Store $store): View
    {
        $this->authorizeStoreOperation($request, $store);

        $dailyCashToday = $store->dailyCashRecords()
            ->whereDate('business_date', today()->toDateString())
            ->first();

        $expenses = $store->expenses()
            ->with(['dailyCashRecord', 'recordedBy'])
            ->latest('id')
            ->paginate(20);

        return view('stores.expenses.index', [
            'store' => $store,
            'dailyCashToday' => $dailyCashToday,
            'expenses' => $expenses,
            'canRecordExpenses' => $dailyCashToday !== null
                && $dailyCashToday->closed_by === null,
            'categories' => StoreExpense::CATEGORIES,
            'paymentMethods' => StoreExpense::PAYMENT_METHODS,
        ]);
    }

    public function store(
        Request $request,
        Store $store,
        RecordStoreExpense $recordStoreExpense
    ): RedirectResponse {
        $this->authorizeStoreOperation($request, $store);

        $result = $recordStoreExpense->execute(
            $request->user(),
            $store,
            $request->only([
                'amount',
                'category',
                'payment_method',
                'description',
            ])
        );

        $response = redirect()
            ->route('stores.expenses.index', $store)
            ->with('success', 'Expense recorded successfully.');

        if ($result['threshold_exceeded']) {
            $response->with(
                'warning',
                'Daily qualifying expenses have exceeded ₦5,000. '
                .'Current qualifying total: ₦'
                .number_format((float) $result['qualifying_total'], 2)
                .'. The expense was still recorded.'
            );
        }

        return $response;
    }

    private function authorizeStoreOperation(Request $request, Store $store): void
    {
        $actor = $request->user();

        abort_unless(
            $actor !== null && $actor->is_active && $store->is_active,
            403
        );

        $assigned = StoreStaff::query()
            ->active()
            ->where('user_id', $actor->id)
            ->where('store_id', $store->id)
            ->whereIn('role', [
                StoreStaff::ROLE_COORDINATOR,
                StoreStaff::ROLE_ASSISTANT,
            ])
            ->exists();

        abort_unless($assigned, 403);
    }
}
