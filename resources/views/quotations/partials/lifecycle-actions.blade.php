<div class="oy-card">
    <div class="oy-card-header">
        <div>
            <h2 class="oy-card-title">Quotation Status</h2>
            <p class="oy-card-description">
                Manage the commercial lifecycle of this quotation.
            </p>
        </div>

        <span class="oy-badge
            @if($quotation->status === \App\Models\Quotation::STATUS_DRAFT) oy-badge-warning
            @elseif($quotation->status === \App\Models\Quotation::STATUS_SENT) oy-badge-info
            @elseif($quotation->status === \App\Models\Quotation::STATUS_CANCELLED) oy-badge-danger
            @else oy-badge-neutral
            @endif">
            {{ ucfirst($quotation->status) }}
        </span>
    </div>

    @if($quotation->status === \App\Models\Quotation::STATUS_DRAFT)
        <div class="oy-card-footer flex flex-wrap gap-2">
            @can('cancel', $quotation)
                <form method="POST" action="{{ route('quotations.cancel', $quotation) }}">
                    @csrf
                    <button type="submit" class="oy-btn oy-btn-danger">
                        Cancel Quotation
                    </button>
                </form>
            @endcan
        </div>
    @elseif($quotation->status === \App\Models\Quotation::STATUS_SENT)
        @can('cancel', $quotation)
            <div class="oy-card-footer">
                <form method="POST" action="{{ route('quotations.cancel', $quotation) }}">
                    @csrf
                    <button type="submit" class="oy-btn oy-btn-danger">
                        Cancel Quotation
                    </button>
                </form>
            </div>
        @endcan
    @endif
</div>
