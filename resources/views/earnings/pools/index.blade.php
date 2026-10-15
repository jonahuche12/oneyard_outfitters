@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Earnings</h1>
            <p class="oy-page-description">
                Staff earnings pools for delivered orders.
            </p>
        </div>
    </div>

    @foreach (['status_message', 'success'] as $flashKey)
        @if (session($flashKey))
            <div class="oy-alert oy-alert-success mb-5">{{ session($flashKey) }}</div>
        @endif
    @endforeach

    <form method="GET" action="{{ route('earnings.pools.index') }}" class="flex flex-wrap gap-3 mb-5">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search order number" class="oy-input">
        <select name="status" class="oy-input">
            <option value="">All statuses</option>
            @foreach (['pending' => 'Pending', 'held' => 'Held', 'finalized' => 'Finalized'] as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="oy-btn oy-btn-secondary">Filter</button>
        @if ($search !== '' || $status)
            <a href="{{ route('earnings.pools.index') }}" class="oy-btn oy-btn-secondary">Clear</a>
        @endif
    </form>

    @if ($pools->isEmpty())
        <div class="oy-card">
            <div class="oy-card-body">
                <div class="oy-empty-state">
                    <div class="oy-empty-state-title">No earnings pools found.</div>
                    <div class="oy-empty-state-description">
                        A pool is created when an order is delivered.
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="oy-table-wrapper">
            <div class="oy-table-scroll">
                <table class="oy-table">
                    <thead>
                        <tr>
                            <th scope="col">Order</th>
                            <th scope="col">Organization</th>
                            <th scope="col">Delivered</th>
                            <th scope="col">Pool</th>
                            <th scope="col">Contributors</th>
                            <th scope="col">Payment</th>
                            <th scope="col">Status</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pools as $pool)
                            <tr>
                                <td>{{ $pool->order?->order_number ?? '—' }}</td>
                                <td>{{ $pool->order?->organization?->name ?? '—' }}</td>
                                <td>{{ $pool->delivered_at?->format('d M Y') ?? '—' }}</td>
                                <td>₦{{ number_format((float) $pool->pool_amount, 2) }}</td>
                                <td>{{ $pool->contributions_count }}</td>
                                <td>{{ $paymentState[$pool->id]['covered'] ? 'Paid' : 'Outstanding' }}</td>
                                <td>
                                    <span class="oy-badge {{ $pool->status === 'finalized' ? 'oy-badge-success' : 'oy-badge-warning' }}">
                                        {{ ucfirst($pool->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('earnings.pools.show', $pool) }}" class="oy-btn oy-btn-secondary">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-5">{{ $pools->links() }}</div>
    @endif

</div>
@endsection
