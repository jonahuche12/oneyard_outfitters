<?php

namespace App\Http\Controllers;

use App\Actions\Earnings\CalculateOrderEarningsAllocation;
use App\Models\OrderEarningsPool;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EarningsPoolController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', OrderEarningsPool::class);

        $status = $request->query('status');
        $search = trim((string) $request->query('q', ''));

        $pools = OrderEarningsPool::query()
            ->with(['order.organization', 'order.quotation.payments'])
            ->withCount('contributions')
            ->when(
                in_array($status, [
                    OrderEarningsPool::STATUS_PENDING,
                    OrderEarningsPool::STATUS_HELD,
                    OrderEarningsPool::STATUS_FINALIZED,
                ], true),
                fn ($q) => $q->where('status', $status)
            )
            ->when($search !== '', fn ($q) => $q->whereHas(
                'order',
                fn ($o) => $o->where('order_number', 'like', '%' . $search . '%')
            ))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $paymentState = [];
        foreach ($pools as $pool) {
            $paymentState[$pool->id] = $this->payment($pool);
        }

        return view('earnings.pools.index', compact('pools', 'paymentState', 'status', 'search'));
    }

    public function show(OrderEarningsPool $pool): View
    {
        Gate::authorize('view', $pool);

        $pool->load([
            'order.organization',
            'order.quotation.payments',
            'contributions',
            'allocations',
        ]);

        $payment = $this->payment($pool);

        $preview = $pool->status === OrderEarningsPool::STATUS_FINALIZED
            ? null
            : app(CalculateOrderEarningsAllocation::class)->execute($pool);

        $userIds = $pool->contributions->pluck('user_id')
            ->merge($pool->allocations->pluck('user_id'))
            ->merge(collect($preview['allocations'] ?? [])->pluck('user_id'))
            ->unique()
            ->all();

        $names = User::query()->whereIn('id', $userIds)->pluck('name', 'id');

        return view('earnings.pools.show', compact('pool', 'payment', 'preview', 'names'));
    }

    /**
     * @return array{total: float, paid: float, balance: float, covered: bool}
     */
    private function payment(OrderEarningsPool $pool): array
    {
        $total = (float) ($pool->order?->total ?? 0);
        $paid = (float) ($pool->order?->quotation?->payments
            ->where('status', 'completed')
            ->sum('amount') ?? 0);
        $balance = max(0, round($total - $paid, 2));

        return [
            'total' => $total,
            'paid' => $paid,
            'balance' => $balance,
            'covered' => $balance <= 0.01,
        ];
    }
}
