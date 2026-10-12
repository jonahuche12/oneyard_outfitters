@php
    $estimateDate = $order->expected_delivery_date;

    $estimateClosed = in_array($order->status, [
        \App\Models\Order::STATUS_DELIVERED,
        \App\Models\Order::STATUS_CANCELLED,
    ], true);
@endphp

@if ($estimateDate === null)
    <span class="oy-table-secondary">—</span>
@elseif ($estimateClosed)
    <div>{{ $estimateDate->format('d M Y') }}</div>
@else
    @php
        $daysRemaining = (int) round(
            now()->startOfDay()->diffInDays($estimateDate->copy()->startOfDay(), false)
        );
    @endphp

    <div>{{ $estimateDate->format('d M Y') }}</div>

    <div class="mt-1">
        @if ($daysRemaining < 0)
            <span class="oy-badge oy-badge-danger">
                {{ abs($daysRemaining) }} {{ \Illuminate\Support\Str::plural('day', abs($daysRemaining)) }} overdue
            </span>
        @elseif ($daysRemaining === 0)
            <span class="oy-badge oy-badge-warning">Due today</span>
        @elseif ($daysRemaining < 5)
            <span class="oy-badge oy-badge-warning">
                {{ $daysRemaining }} {{ \Illuminate\Support\Str::plural('day', $daysRemaining) }} to delivery
            </span>
        @else
            <span class="oy-badge oy-badge-neutral">
                {{ $daysRemaining }} days to delivery
            </span>
        @endif
    </div>
@endif
