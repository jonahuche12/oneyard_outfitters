@extends('layouts.app')

@section('content')
<div class="oy-page">

    <a href="{{ route('earnings.pools.index') }}" class="oy-link">← Back to Earnings</a>

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <div class="oy-meta">Earnings pool</div>
            <h1 class="oy-page-title">{{ $pool->order?->order_number }}</h1>
            <p class="oy-page-description">{{ $pool->order?->organization?->name }}</p>
        </div>
        <div class="oy-page-actions">
            <span class="oy-badge {{ $pool->status === 'finalized' ? 'oy-badge-success' : 'oy-badge-warning' }}">
                {{ ucfirst($pool->status) }}
            </span>
        </div>
    </div>

    @foreach (['status_message', 'success'] as $flashKey)
        @if (session($flashKey))
            <div class="oy-alert oy-alert-success mb-5">{{ session($flashKey) }}</div>
        @endif
    @endforeach

    <div class="space-y-6">

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="oy-card">
                <div class="oy-card-header"><h2 class="oy-card-title">Pool</h2></div>
                <div class="oy-card-body">
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div><dt class="oy-meta">Quotation</dt><dd>{{ $pool->quotation_number }}</dd></div>
                        <div><dt class="oy-meta">Source amount</dt><dd>₦{{ number_format((float) $pool->source_amount, 2) }}</dd></div>
                        <div><dt class="oy-meta">Rate</dt><dd>{{ rtrim(rtrim((string) $pool->rate_percent, '0'), '.') }}%</dd></div>
                        <div><dt class="oy-meta">Pool amount</dt><dd data-pool-amount>₦{{ number_format((float) $pool->pool_amount, 2) }}</dd></div>
                        <div><dt class="oy-meta">Delivered</dt><dd>{{ $pool->delivered_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                        @if ($pool->finalized_at)
                            <div><dt class="oy-meta">Finalized</dt><dd>{{ $pool->finalized_at->format('d M Y H:i') }}</dd></div>
                        @endif
                    </dl>
                </div>
            </section>

            <section class="oy-card">
                <div class="oy-card-header"><h2 class="oy-card-title">Payment</h2></div>
                <div class="oy-card-body">
                    <dl class="grid gap-4 sm:grid-cols-3">
                        <div><dt class="oy-meta">Order total</dt><dd>₦{{ number_format($payment['total'], 2) }}</dd></div>
                        <div><dt class="oy-meta">Paid</dt><dd>₦{{ number_format($payment['paid'], 2) }}</dd></div>
                        <div><dt class="oy-meta">Balance</dt><dd>₦{{ number_format($payment['balance'], 2) }}</dd></div>
                    </dl>
                    @if (! $payment['covered'] && $pool->status !== 'finalized')
                        <div class="oy-alert oy-alert-warning mt-4" data-payment-warning>
                            Cannot finalize: the order balance is outstanding.
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <section class="oy-card">
            <div class="oy-card-header"><h2 class="oy-card-title">Contributions</h2></div>
            <div class="oy-card-body">
                @if ($pool->contributions->isEmpty())
                    <p class="text-sm opacity-70">No contributions recorded yet.</p>
                @else
                    <div class="oy-table-scroll">
                        <table class="oy-table">
                            <thead>
                                <tr>
                                    <th scope="col">Staff</th>
                                    <th scope="col">Activity</th>
                                    <th scope="col">Points</th>
                                    <th scope="col">Source</th>
                                    <th scope="col">Reason</th>
                                    <th scope="col">When</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pool->contributions as $contribution)
                                    <tr>
                                        <td>{{ $names[$contribution->user_id] ?? 'Unknown' }}</td>
                                        <td>{{ str_replace('_', ' ', $contribution->activity_key) }}</td>
                                        <td>{{ rtrim(rtrim((string) $contribution->points, '0'), '.') }}</td>
                                        <td>{{ ucfirst($contribution->source_type) }}</td>
                                        <td>{{ $contribution->reason ?? '—' }}</td>
                                        <td>{{ $contribution->activity_at?->format('d M Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        @if ($pool->status === 'finalized')
            <section class="oy-card">
                <div class="oy-card-header"><h2 class="oy-card-title">Finalized allocations</h2></div>
                <div class="oy-card-body">
                    <div class="oy-table-scroll">
                        <table class="oy-table">
                            <thead>
                                <tr>
                                    <th scope="col">Staff</th>
                                    <th scope="col">Points</th>
                                    <th scope="col">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pool->allocations as $allocation)
                                    <tr>
                                        <td>{{ $names[$allocation->user_id] ?? 'Unknown' }}</td>
                                        <td>{{ rtrim(rtrim((string) $allocation->effective_points, '0'), '.') }}</td>
                                        <td>₦{{ number_format((float) $allocation->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @else
            <section class="oy-card">
                <div class="oy-card-header"><h2 class="oy-card-title">Allocation preview</h2></div>
                <div class="oy-card-body">
                    @if ($preview['verdict'] === 'held')
                        <div class="oy-alert oy-alert-warning" data-preview-held>
                            This pool would be held:
                            {{ collect($preview['hold_reasons'])->map(fn ($r) => str_replace('_', ' ', $r))->implode(', ') }}.
                        </div>
                    @else
                        <div class="oy-table-scroll">
                            <table class="oy-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Staff</th>
                                        <th scope="col">Points</th>
                                        <th scope="col">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($preview['allocations'] as $row)
                                        <tr>
                                            <td>{{ $names[$row['user_id']] ?? 'Unknown' }}</td>
                                            <td>{{ rtrim(rtrim((string) $row['effective_points'], '0'), '.') }}</td>
                                            <td>₦{{ number_format((float) $row['amount'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="text-sm opacity-70 mt-3">Preview only. Nothing is recorded until the pool is finalized.</p>
                    @endif
                </div>
            </section>
        @endif

    </div>
</div>
@endsection
