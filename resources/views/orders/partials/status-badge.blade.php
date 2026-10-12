@php
    $orderBadgeClass = match ($orderStatus) {
        \App\Models\Order::STATUS_APPROVED,
        \App\Models\Order::STATUS_READY,
        \App\Models\Order::STATUS_DELIVERED => 'oy-badge-success',
        \App\Models\Order::STATUS_IN_PRODUCTION => 'oy-badge-neutral',
        \App\Models\Order::STATUS_READY_FOR_QUALITY_CONTROL => 'oy-badge-info',
        \App\Models\Order::STATUS_CORRECTION_REQUIRED,
        \App\Models\Order::STATUS_CANCELLED => 'oy-badge-danger',
        default => 'oy-badge-warning',
    };

    $orderStatusLabel = \App\Models\Order::statusLabels()[$orderStatus]
        ?? ucwords(str_replace('_', ' ', (string) $orderStatus));
@endphp

<span class="oy-badge {{ $orderBadgeClass }}">{{ $orderStatusLabel }}</span>
