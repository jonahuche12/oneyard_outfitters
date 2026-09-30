@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Quotations</h1>
            <p class="oy-page-description">
                Manage commercial offers issued to organizations.
            </p>
        </div>

        <div class="oy-page-actions">
            @can('create', App\Models\Quotation::class)
                <a href="{{ route('quotations.create') }}" class="oy-btn oy-btn-primary">
                    New Quotation
                </a>
            @endcan
        </div>
    </div>

    @if($quotations->isEmpty())

        <div class="oy-card">
            <div class="oy-card-body">
                <div class="oy-empty-state">
                    <div class="oy-empty-state-title">No quotations yet</div>
                    <div class="oy-empty-state-description">
                        Create a quotation when an organization's requirements are ready for a commercial offer.
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
                            <th>Quotation</th>
                            <th>Organization</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($quotations as $quotation)
                            <tr>
                                <td>
                                    <div class="font-medium text-slate-900">
                                        {{ $quotation->quotation_number }}
                                    </div>
                                </td>

                                <td>
                                    <div class="font-medium text-slate-900">
                                        {{ $quotation->organization->name }}
                                    </div>

                                    <div class="oy-code mt-1">
                                        {{ $quotation->organization->organization_code }}
                                    </div>
                                </td>

                                <td>
                                    {{ $quotation->quotation_date->format('d M Y') }}
                                </td>

                                <td>
                                    @if($quotation->status === 'accepted')
                                        <span class="oy-badge oy-badge-success">Accepted</span>
                                    @elseif($quotation->status === 'rejected' || $quotation->status === 'cancelled')
                                        <span class="oy-badge oy-badge-danger">
                                            {{ ucfirst($quotation->status) }}
                                        </span>
                                    @elseif($quotation->status === 'sent')
                                        <span class="oy-badge oy-badge-neutral">Sent</span>
                                    @elseif($quotation->status === 'expired')
                                        <span class="oy-badge oy-badge-warning">Expired</span>
                                    @else
                                        <span class="oy-badge oy-badge-warning">Draft</span>
                                    @endif
                                </td>

                                <td class="font-medium text-slate-900">
                                    ₦{{ number_format((float) $quotation->total, 2) }}
                                </td>

                                <td class="text-right">
                                    <a
                                        href="{{ route('quotations.show', $quotation) }}"
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

            @if($quotations->hasPages())
                <div class="oy-card-footer">
                    {{ $quotations->links() }}
                </div>
            @endif
        </div>

    @endif

</div>
@endsection
