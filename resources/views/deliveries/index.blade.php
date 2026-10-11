@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Deliveries</h1>
            <p class="oy-page-description">
                Orders that have entered the Delivery workflow.
            </p>
        </div>
    </div>

    @foreach (['status', 'success'] as $flashKey)
        @if (session($flashKey))
            <div class="oy-alert oy-alert-success mb-5">
                {{ session($flashKey) }}
            </div>
        @endif
    @endforeach

    @if ($deliveries->isEmpty())

        <div class="oy-card">
            <div class="oy-card-body">
                <div class="oy-empty-state">
                    <div class="oy-empty-state-title">No deliveries have been created yet.</div>
                    <div class="oy-empty-state-description">
                        Orders appear here after Quality Control passes and Delivery is started.
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
                            <th scope="col">Status</th>
                            <th scope="col">Delivery Date</th>
                            <th scope="col" class="text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($deliveries as $delivery)
                            <tr>
                                <td>
                                    <a
                                        href="{{ route('deliveries.show', $delivery) }}"
                                        class="oy-table-primary hover:underline"
                                    >
                                        {{ $delivery->order->order_number }}
                                    </a>
                                </td>

                                <td>
                                    {{ $delivery->order->organization->name }}
                                </td>

                                <td>
                                    @if ($delivery->status === \App\Models\Delivery::STATUS_CONFIRMED)
                                        <span class="oy-badge oy-badge-success">
                                            <span class="oy-status-dot"></span>
                                            Delivered
                                        </span>
                                    @else
                                        <span class="oy-badge oy-badge-warning">
                                            <span class="oy-status-dot"></span>
                                            Pending Delivery
                                        </span>

                                        <div class="oy-table-secondary">
                                            {{ $delivery->activated_at
                                                ? 'Customer activated'
                                                : 'Awaiting customer activation' }}
                                        </div>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap">
                                    {{ $delivery->delivery_date?->format('d M Y') ?? '—' }}
                                </td>

                                <td>
                                    <div class="oy-action-group">
                                        <a
                                            href="{{ route('deliveries.show', $delivery) }}"
                                            class="oy-btn oy-btn-secondary oy-btn-sm"
                                        >
                                            View
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($deliveries->hasPages())
                <div class="oy-card-footer">
                    {{ $deliveries->links() }}
                </div>
            @endif
        </div>

    @endif

</div>
@endsection
