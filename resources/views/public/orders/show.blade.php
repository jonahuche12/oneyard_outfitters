@extends('layouts.public.app')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10">

    <div class="mb-8">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold text-slate-900">
                {{ $order->order_number }}
            </h1>

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
        </div>

        <p class="mt-2 text-sm text-slate-500">
            {{ $order->organization->name }}
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        <div class="oy-card lg:col-span-2">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Order Progress</h2>
                <p class="oy-card-description">
                    Your order status will be updated here as fulfillment progresses.
                </p>
            </div>

            <div class="oy-card-body">
                <div class="grid gap-5 sm:grid-cols-2">

                    <div>
                        <div class="oy-meta">Order Date</div>
                        <div class="mt-1 text-sm text-slate-700">
                            {{ $order->order_date->format('d M Y') }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta">Expected Delivery</div>
                        <div class="mt-1">
                            <span class="oy-badge {{ $order->expected_delivery_days < 5 ? 'oy-badge-warning' : 'oy-badge-neutral' }}">
                                {{ $order->expected_delivery_days }}
                                {{ $order->expected_delivery_days === 1 ? 'day' : 'days' }}
                                to delivery
                            </span>
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta">Expected Delivery Date</div>
                        <div class="mt-1 text-sm text-slate-700">
                            {{ $order->expected_delivery_date?->format('d M Y') ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="oy-meta">Current Status</div>
                        <div class="mt-1 text-sm font-medium text-slate-900">
                            {{ ucwords(str_replace('_', ' ', $order->status)) }}
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Organization</h2>
            </div>

            <div class="oy-card-body">
                <div class="font-medium text-slate-900">
                    {{ $order->organization->name }}
                </div>

                <div class="oy-code mt-1">
                    {{ $order->organization->organization_code }}
                </div>
            </div>
        </div>

        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Primary Contact</h2>
            </div>

            <div class="oy-card-body">
                <div class="font-medium text-slate-900">
                    {{ $recipient->contact->first_name }}
                    {{ $recipient->contact->last_name }}
                </div>

                @if($recipient->contact->position)
                    <div class="mt-1 text-sm text-slate-500">
                        {{ $recipient->contact->position }}
                    </div>
                @endif

                <div class="mt-2 text-sm text-slate-600">
                    {{ $recipient->email }}
                </div>
            </div>
        </div>

    </div>

    <div class="oy-card mt-6">
        <div class="oy-card-header">
            <h2 class="oy-card-title">Order Items</h2>
        </div>

        <div class="oy-card-body">

            @if($order->items->isEmpty())
                <div class="text-sm text-slate-500">
                    No order items are available.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="oy-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="font-medium text-slate-900">
                                            {{ $item->item_name }}
                                        </div>

                                        @if($item->description)
                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ $item->description }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>{{ number_format((float) $item->quantity, 2) }}</td>
                                    <td>{{ $item->unit }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection
