@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Orders</h1>
            <p class="oy-page-description">
                Manage accepted customer orders through fulfillment.
            </p>
        </div>
    </div>

    <div class="oy-card mb-6">
        <div class="oy-card-body">
            <form method="GET" action="{{ route('orders.index') }}" class="oy-form">
                <div class="grid gap-4 md:grid-cols-3">

                    <div class="oy-form-group md:col-span-2">
                        <label class="oy-form-label">Search</label>
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            class="oy-input"
                            placeholder="Order number, organization or quotation..."
                        >
                    </div>

                    <div class="oy-form-group">
                        <label class="oy-form-label">Status</label>
                        <select name="status" class="oy-select">
                            <option value="">All statuses</option>
                            @foreach([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'in_production' => 'In Production',
                                'ready' => 'Ready',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ] as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected($status === $value)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <a href="{{ route('orders.index') }}" class="oy-btn oy-btn-secondary">
                        Clear
                    </a>

                    <button type="submit" class="oy-btn oy-btn-primary">
                        Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($orders->isEmpty())

        <div class="oy-card">
            <div class="oy-card-body">
                <div class="oy-empty-state">
                    <div class="oy-empty-state-title">No orders found</div>
                    <div class="oy-empty-state-description">
                        Orders are created automatically after a verified quotation payment.
                    </div>
                </div>
            </div>
        </div>

    @else

        <div class="oy-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="oy-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Organization</th>
                            <th>Quotation</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td>
                                    <div class="font-medium text-slate-900">
                                        {{ $order->order_number }}
                                    </div>
                                </td>

                                <td>
                                    <div class="font-medium text-slate-900">
                                        {{ $order->organization->name }}
                                    </div>

                                    <div class="oy-code mt-1">
                                        {{ $order->organization->organization_code }}
                                    </div>
                                </td>

                                <td>
                                    {{ $order->quotation->quotation_number }}
                                </td>

                                <td>
                                    {{ $order->order_date->format('d M Y') }}
                                </td>

                                <td>
                                    @if($order->status === 'approved')
                                        <span class="oy-badge oy-badge-success">Approved</span>
                                    @elseif($order->status === 'in_production')
                                        <span class="oy-badge oy-badge-neutral">In Production</span>
                                    @elseif($order->status === 'ready')
                                        <span class="oy-badge oy-badge-success">Ready</span>
                                    @elseif($order->status === 'delivered')
                                        <span class="oy-badge oy-badge-success">Delivered</span>
                                    @elseif($order->status === 'cancelled')
                                        <span class="oy-badge oy-badge-danger">Cancelled</span>
                                    @else
                                        <span class="oy-badge oy-badge-warning">Pending</span>
                                    @endif
                                </td>

                                <td class="font-medium text-slate-900">
                                    ₦{{ number_format((float) $order->total, 2) }}
                                </td>

                                <td class="text-right">
                                    <a
                                        href="{{ route('orders.show', $order) }}"
                                        class="oy-btn oy-btn-secondary oy-btn-sm"
                                    >
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="oy-card-footer">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>

    @endif

</div>
@endsection
