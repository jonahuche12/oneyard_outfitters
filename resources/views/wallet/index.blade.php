@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">My Wallet</h1>
            <p class="oy-page-description">
                Your earnings from delivered orders, with any adjustments.
            </p>
        </div>
    </div>

    <div class="oy-card mb-5">
        <div class="oy-card-body">
            <div class="text-sm opacity-70">Current balance</div>
            <div class="text-3xl font-semibold" data-wallet-balance>
                {{ (float) $balance < 0 ? '-' : '' }}₦{{ number_format(abs((float) $balance), 2) }}
            </div>
            @if ((float) $balance < 0)
                <p class="text-sm mt-2 opacity-70">
                    A negative balance will be recovered from your future earnings.
                </p>
            @endif
        </div>
    </div>

    @if ($entries->isEmpty())

        <div class="oy-card">
            <div class="oy-card-body">
                <div class="oy-empty-state">
                    <div class="oy-empty-state-title">No wallet entries yet.</div>
                    <div class="oy-empty-state-description">
                        Earnings appear here after finance finalizes the earnings pool for a delivered order you worked on.
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
                            <th scope="col">Date</th>
                            <th scope="col">Type</th>
                            <th scope="col">Order</th>
                            <th scope="col">Note</th>
                            <th scope="col">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr>
                                <td>{{ $entry->created_at?->format('d M Y') }}</td>
                                <td>{{ ucfirst($entry->type) }}</td>
                                <td>{{ $entry->pool?->order?->order_number ?? '—' }}</td>
                                <td>{{ $entry->reason ?? '—' }}</td>
                                <td>
                                    {{ (float) $entry->amount < 0 ? '-' : '' }}₦{{ number_format(abs((float) $entry->amount), 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-5">
            {{ $entries->links() }}
        </div>

    @endif

</div>
@endsection
