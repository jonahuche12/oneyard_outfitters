@extends('layouts.app')

@section('content')
<div class="oy-page">

    <a href="{{ route('earnings.wallets.index') }}" class="oy-link">← Back to Staff Wallets</a>

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <div class="oy-meta">Staff wallet</div>
            <h1 class="oy-page-title">{{ $user->name }}</h1>
            <p class="oy-page-description">
                {{ $user->email }}
                @unless ($user->is_active) <span class="oy-badge oy-badge-warning">Inactive</span> @endunless
            </p>
        </div>
        <div class="oy-page-actions">
            @can('adjust', App\Models\StaffWalletEntry::class)
                @if ($user->id !== auth()->id())
                    <button type="button" class="oy-btn oy-btn-primary" data-open-pool-modal="adjust-modal">Adjust</button>
                @endif
            @endcan
        </div>
    </div>

    <div class="oy-card mb-5"><div class="oy-card-body">
        <div class="text-sm opacity-70">Balance</div>
        <div class="text-3xl font-semibold" data-wallet-balance>
            {{ (float) $balance < 0 ? '-' : '' }}₦{{ number_format(abs((float) $balance), 2) }}
        </div>
        @if ((float) $balance < 0)
            <p class="text-sm mt-2 opacity-70">Recoverable from future earnings.</p>
        @endif
    </div></div>

    @if ($entries->isEmpty())
        <div class="oy-card"><div class="oy-card-body"><div class="oy-empty-state">
            <div class="oy-empty-state-title">No wallet entries yet.</div>
        </div></div></div>
    @else
        <div class="oy-table-wrapper"><div class="oy-table-scroll">
            <table class="oy-table">
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Type</th>
                        <th scope="col">Order</th>
                        <th scope="col">Note</th>
                        <th scope="col">Amount</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entries as $entry)
                        @php $reversed = in_array($entry->id, $reversedIds, true); @endphp
                        <tr class="{{ $reversed ? 'opacity-60' : '' }}">
                            <td>{{ $entry->created_at?->format('d M Y') }}</td>
                            <td>
                                {{ ucfirst($entry->type) }}
                                @if ($reversed) <span class="oy-badge oy-badge-warning" data-reversed>Reversed</span> @endif
                            </td>
                            <td>{{ $entry->pool?->order?->order_number ?? '—' }}</td>
                            <td>{{ $entry->reason ?? '—' }}</td>
                            <td>{{ (float) $entry->amount < 0 ? '-' : '' }}₦{{ number_format(abs((float) $entry->amount), 2) }}</td>
                            <td>
                                @can('adjust', App\Models\StaffWalletEntry::class)
                                    @if (in_array($entry->type, ['earning', 'commission'], true) && ! $reversed && $user->id !== auth()->id())
                                        <button type="button" class="oy-btn oy-btn-secondary" data-reverse-entry
                                            data-reverse-url="{{ route('earnings.wallets.reverse', [$user, $entry]) }}">Reverse</button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div></div>
        <div class="mt-5">{{ $entries->links() }}</div>
    @endif

    @can('adjust', App\Models\StaffWalletEntry::class)
        <div id="adjust-modal" data-pool-modal
             data-reopen="{{ $errors->hasAny(['amount', 'adjust_reason']) ? 'true' : 'false' }}"
             class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4"
             role="dialog" aria-modal="true" aria-labelledby="adjust-modal-title">
            <div class="oy-card mx-auto mt-24 max-w-lg">
                <div class="oy-card-header"><h2 id="adjust-modal-title" class="oy-card-title">Adjust wallet</h2></div>
                <form method="POST" action="{{ route('earnings.wallets.adjust', $user) }}" class="oy-card-body space-y-4">
                    @csrf
                    <div>
                        <label for="adjust-amount" class="oy-meta">Amount (negative to deduct)</label>
                        <input id="adjust-amount" type="text" name="amount" value="{{ old('amount') }}" class="oy-input w-full" required data-modal-focus>
                        @error('amount') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="adjust-reason" class="oy-meta">Reason (at least 10 characters)</label>
                        <textarea id="adjust-reason" name="adjust_reason" rows="3" class="oy-input w-full" required>{{ old('adjust_reason') }}</textarea>
                        @error('adjust_reason') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" class="oy-btn oy-btn-secondary" data-close-pool-modal>Cancel</button>
                        <button type="submit" class="oy-btn oy-btn-primary">Record adjustment</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="reverse-modal" data-pool-modal
             data-reopen="false"
             class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4"
             role="dialog" aria-modal="true" aria-labelledby="reverse-modal-title">
            <div class="oy-card mx-auto mt-24 max-w-lg">
                <div class="oy-card-header"><h2 id="reverse-modal-title" class="oy-card-title">Reverse entry</h2></div>
                <form id="reverse-form" method="POST" action="" class="oy-card-body space-y-4">
                    @csrf
                    <p class="text-sm">An equal and opposite entry is added. The original stays in history.</p>
                    @error('reverse_reason') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('entry') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    <div>
                        <label for="reverse-reason" class="oy-meta">Reason (at least 10 characters)</label>
                        <textarea id="reverse-reason" name="reverse_reason" rows="3" class="oy-input w-full" required data-modal-focus>{{ old('reverse_reason') }}</textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" class="oy-btn oy-btn-secondary" data-close-pool-modal>Cancel</button>
                        <button type="submit" class="oy-btn oy-btn-primary">Reverse entry</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

</div>
@endsection
