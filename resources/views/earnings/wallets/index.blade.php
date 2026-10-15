@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <h1 class="oy-page-title">Staff Wallets</h1>
            <p class="oy-page-description">Balances from earnings and commission.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('earnings.wallets.index') }}" class="flex flex-wrap gap-3 mb-5">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search name or email" class="oy-input">
        <button type="submit" class="oy-btn oy-btn-secondary">Search</button>
        @if ($search !== '')
            <a href="{{ route('earnings.wallets.index') }}" class="oy-btn oy-btn-secondary">Clear</a>
        @endif
    </form>

    @if ($users->isEmpty())
        <div class="oy-card"><div class="oy-card-body"><div class="oy-empty-state">
            <div class="oy-empty-state-title">No staff found.</div>
        </div></div></div>
    @else
        <div class="oy-table-wrapper"><div class="oy-table-scroll">
            <table class="oy-table">
                <thead>
                    <tr>
                        <th scope="col">Staff</th>
                        <th scope="col">Balance</th>
                        <th scope="col">Last entry</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $person)
                        @php $balance = (float) ($totals[$person->id]->balance ?? 0); @endphp
                        <tr>
                            <td>
                                {{ $person->name }}
                                @unless ($person->is_active)
                                    <span class="oy-badge oy-badge-warning">Inactive</span>
                                @endunless
                            </td>
                            <td>{{ $balance < 0 ? '-' : '' }}₦{{ number_format(abs($balance), 2) }}</td>
                            <td>{{ isset($totals[$person->id]) ? \Illuminate\Support\Carbon::parse($totals[$person->id]->last_at)->format('d M Y') : '—' }}</td>
                            <td><a href="{{ route('earnings.wallets.show', $person) }}" class="oy-btn oy-btn-secondary">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div></div>
        <div class="mt-5">{{ $users->links() }}</div>
    @endif

</div>
@endsection
