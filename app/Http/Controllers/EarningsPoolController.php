<?php

namespace App\Http\Controllers;

use App\Actions\Earnings\CalculateOrderEarningsAllocation;
use App\Actions\Earnings\DeriveOrderEarningsContributions;
use Illuminate\Http\RedirectResponse;
use App\Actions\Earnings\FinalizeOrderEarningsPool;
use App\Actions\Earnings\VoidOrderEarningsContribution;
use App\Actions\Earnings\RecordOrderEarningsContribution;
use App\Models\EarningsActivityWeight;
use App\Models\OrderEarningsContribution;
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
            ->withCount(['contributions' => fn ($q) => $q->whereNull('voided_at')])
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

        $staff = collect();
        $weights = collect();

        if (Gate::allows('assignContribution', $pool)) {
            $staff = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $weights = EarningsActivityWeight::query()
                ->where('is_enabled', true)
                ->where('activity_key', '!=', 'management_oversight')
                ->orderBy('label')
                ->get(['activity_key', 'label']);
        }

        return view('earnings.pools.show', compact('pool', 'payment', 'preview', 'names', 'staff', 'weights'));
    }

    public function storeContribution(Request $request, OrderEarningsPool $pool): RedirectResponse
    {
        Gate::authorize('assignContribution', $pool);

        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'activity_key' => ['required', 'string', 'not_in:management_oversight'],
            'activity_at' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        app(RecordOrderEarningsContribution::class)->execute($pool, [
            'user_id' => $data['user_id'],
            'activity_key' => $data['activity_key'],
            'source_type' => OrderEarningsContribution::SOURCE_MANUAL,
            'activity_at' => $data['activity_at'],
            'reason' => $data['reason'],
        ], $request->user());

        return redirect()
            ->route('earnings.pools.show', $pool)
            ->with('success', 'The contribution has been recorded.');
    }

    public function voidContribution(Request $request, OrderEarningsPool $pool, OrderEarningsContribution $contribution): RedirectResponse
    {
        Gate::authorize('voidContribution', $pool);
        abort_unless($contribution->order_earnings_pool_id === $pool->id, 404);

        $data = $request->validate([
            'void_reason' => ['required', 'string', 'min:10', 'max:2000'],
            'void_target' => ['nullable', 'integer'],
        ]);

        app(VoidOrderEarningsContribution::class)->execute($contribution, $data['void_reason'], $request->user());

        return redirect()
            ->route('earnings.pools.show', $pool)
            ->with('success', 'The contribution has been voided and removed from the allocation.');
    }

    public function finalize(Request $request, OrderEarningsPool $pool): RedirectResponse
    {
        Gate::authorize('finalize', $pool);

        $result = app(FinalizeOrderEarningsPool::class)->execute($pool, $request->user());

        if ($result->status === OrderEarningsPool::STATUS_HELD) {
            $reasons = collect($result->hold_reasons['reasons'] ?? [])
                ->map(fn ($r) => str_replace('_', ' ', $r))
                ->implode(', ');

            return redirect()
                ->route('earnings.pools.show', $pool)
                ->with('pool_held', 'The pool was held, not finalized: ' . $reasons . '. Resolve this and finalize again.');
        }

        return redirect()
            ->route('earnings.pools.show', $pool)
            ->with('success', 'The pool has been finalized and staff wallets credited.');
    }

    public function refreshContributions(OrderEarningsPool $pool): RedirectResponse
    {
        Gate::authorize('deriveContributions', $pool);

        $report = app(DeriveOrderEarningsContributions::class)->execute($pool);

        $names = User::query()
            ->whereIn('id', collect($report['skipped'])->pluck('user_id'))
            ->pluck('name', 'id');

        $skipped = collect($report['skipped'])
            ->map(fn ($row) => ($names[$row['user_id']] ?? 'Unknown')
                . ' (' . str_replace('_', ' ', $row['activity_key']) . '): '
                . $row['reason'])
            ->all();

        $message = count($report['credited']) . ' contribution(s) confirmed from workflow records.';

        if ($skipped !== []) {
            $message .= ' ' . count($skipped) . ' skipped, see below.';
        }

        return redirect()
            ->route('earnings.pools.show', $pool)
            ->with('success', $message)
            ->with('derivation_skipped', $skipped);
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
