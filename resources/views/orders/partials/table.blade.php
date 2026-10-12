<div class="oy-table-wrapper">

    {{-- Read by resources/js/ajax-list.js to keep the header count in sync. --}}
    <div data-list-total="{{ $orders->total() }}" hidden></div>

    <div class="oy-card-header">
        <h2 class="oy-card-title">Order Records</h2>
        <p class="oy-card-description">
            Follow each accepted order from approval through production, quality control and delivery.
        </p>
    </div>

    <div class="oy-table-scroll">
        <table class="oy-table">
            <thead>
                <tr>
                    <th scope="col">Order</th>
                    <th scope="col">Organization</th>
                    <th scope="col">Quotation</th>
                    <th scope="col">Coordinator</th>
                    <th scope="col">Order Date</th>
                    <th scope="col">Expected Delivery</th>
                    <th scope="col">Status</th>
                    <th scope="col">Total</th>
                    <th scope="col" class="text-right">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($orders as $order)
                    <tr data-order-row="{{ $order->id }}">
                        <td>
                            <div class="oy-table-primary">{{ $order->order_number }}</div>
                        </td>

                        <td>
                            @if ($order->organization)
                                <div class="oy-table-primary">{{ $order->organization->name }}</div>
                                <div class="oy-code">{{ $order->organization->organization_code }}</div>
                            @else
                                <span class="oy-table-secondary">Organization removed</span>
                            @endif
                        </td>

                        <td class="whitespace-nowrap">
                            {{ $order->quotation?->quotation_number ?? '—' }}
                        </td>

                        <td>
                            @if ($order->currentAssignment?->user)
                                {{ $order->currentAssignment->user->name }}
                            @else
                                <span class="oy-table-secondary">Unassigned</span>
                            @endif
                        </td>

                        <td class="whitespace-nowrap">
                            {{ $order->order_date?->format('d M Y') ?? '—' }}
                        </td>

                        <td class="whitespace-nowrap">
                            @include('orders.partials.delivery-estimate', ['order' => $order])
                        </td>

                        <td class="whitespace-nowrap">
                            @include('orders.partials.status-badge', ['orderStatus' => $order->status])
                        </td>

                        <td class="whitespace-nowrap font-medium text-slate-900">
                            ₦{{ number_format((float) $order->total, 2) }}
                        </td>

                        <td>
                            <div class="oy-action-group">
                                <a
                                    href="{{ route('orders.show', $order) }}"
                                    class="oy-btn oy-btn-secondary oy-btn-sm"
                                >
                                    View
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="oy-empty-state">
                                <div class="oy-empty-state-title">No orders found.</div>

                                @if ($search !== '' || $status)
                                    <p class="oy-empty-state-description">
                                        Try another search or clear the current filters.
                                    </p>

                                    <button
                                        type="button"
                                        data-list-clear
                                        class="oy-btn oy-btn-secondary oy-btn-sm mt-4"
                                    >
                                        Clear search
                                    </button>
                                @else
                                    <p class="oy-empty-state-description">
                                        Orders are created automatically after a verified quotation payment.
                                    </p>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($orders->hasPages())
        <div class="oy-card-footer">
            {{ $orders->links() }}
        </div>
    @endif

</div>
