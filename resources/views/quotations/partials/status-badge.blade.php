@php
    $quotationBadgeClass = match ($quotationStatus) {
        \App\Models\Quotation::STATUS_ACCEPTED => 'oy-badge-success',
        \App\Models\Quotation::STATUS_REJECTED,
        \App\Models\Quotation::STATUS_CANCELLED => 'oy-badge-danger',
        \App\Models\Quotation::STATUS_SENT => 'oy-badge-info',
        \App\Models\Quotation::STATUS_DRAFT,
        \App\Models\Quotation::STATUS_EXPIRED => 'oy-badge-warning',
        default => 'oy-badge-neutral',
    };
@endphp

<span class="oy-badge {{ $quotationBadgeClass }}">{{ ucfirst($quotationStatus) }}</span>
