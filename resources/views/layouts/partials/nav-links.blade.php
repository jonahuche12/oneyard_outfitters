@php
    $mobile = $mobile ?? false;
    $navUser = auth()->user();
    $navGroups = [
        'Overview' => [
            ['Dashboard', 'dashboard', 'dashboard', 'home', true],
        ],
        'Customers' => [
            ['Leads', 'leads.index', 'leads.*', 'inbox', \Illuminate\Support\Facades\Gate::allows('viewAny', \App\Models\Lead::class)],
            ['Organizations', 'organizations.index', 'organizations.*', 'building', \Illuminate\Support\Facades\Gate::allows('viewAny', \App\Models\Organization::class)],
        ],
        'Operations' => [
            ['Orders', 'orders.index', 'orders.*', 'clipboard', \Illuminate\Support\Facades\Gate::allows('viewAny', \App\Models\Order::class)],
            ['Procurement', 'procurements.index', 'procurements.*', 'cube', \Illuminate\Support\Facades\Gate::allows('viewAny', \App\Models\Procurement::class)],
            ['Quality Control', 'quality-control.index', 'quality-control.*', 'shield', \Illuminate\Support\Facades\Gate::allows('viewQualityControl', \App\Models\Order::class)],
        ],
        'Finance & Earnings' => [
            ['Earnings', 'earnings.pools.index', 'earnings.pools.*', 'chart', \Illuminate\Support\Facades\Gate::allows('viewAny', \App\Models\OrderEarningsPool::class)],
            ['Staff Wallets', 'earnings.wallets.index', 'earnings.wallets.*', 'wallet', \Illuminate\Support\Facades\Gate::allows('viewAny', \App\Models\StaffWalletEntry::class)],
            ['Withdrawal Requests', 'earnings.withdrawals.index', 'earnings.withdrawals.*', 'download', (bool) $navUser?->hasPermission('earnings.withdrawals.manage')],
        ],
        'Administration' => [
            ['Staff', 'staff.index', 'staff.*', 'users', \Illuminate\Support\Facades\Gate::allows('viewAny', \App\Models\User::class)],
        ],
        'My Account' => [
            ['My Wallet', 'wallet.index', 'wallet.*', 'wallet', true],
        ],
    ];
    // Groups start collapsed. Set to false to keep the current page's group closed too.
    $openActiveGroup = true;
@endphp

<div class="space-y-1">
@foreach($navGroups as $groupLabel => $links)
    @php
        $visible = array_values(array_filter($links, fn ($l) => $l[4]));
        $groupActive = $openActiveGroup && collect($visible)->contains(fn ($l) => request()->routeIs($l[2]));
    @endphp
    @if($visible !== [])
        <details class="oy-nav-group group {{ $groupActive ? 'is-active-group' : '' }}" @if($groupActive) open @endif>
            <summary class="oy-nav-group-heading flex cursor-pointer list-none items-center justify-between rounded-lg px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider transition [&::-webkit-details-marker]:hidden">
                <span>{{ $groupLabel }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4 shrink-0 transition-transform group-open:rotate-180" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </summary>
            <div class="oy-nav-group-links mt-1 space-y-1 pb-2">
                @foreach($visible as [$label, $routeName, $pattern, $icon])
                    <a
                        href="{{ route($routeName) }}"
                        class="oy-nav-link !justify-start gap-3 text-left {{ request()->routeIs($pattern) ? 'is-active' : '' }}"
                        @if(request()->routeIs($pattern)) aria-current="page" @endif
                        @if($mobile) data-app-mobile-menu-link @endif
                    >
                        <x-ui.icon :name="$icon" class="h-5 w-5" />
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
            </div>
        </details>
    @endif
@endforeach
</div>
