@extends('layouts.app')

@section('content')
<div class="oy-page">

    <a href="{{ route('earnings.pools.index') }}" class="oy-link">← Back to Earnings</a>

    <div class="oy-page-header">
        <div class="oy-page-header-content">
            <div class="oy-meta">Earnings pool</div>
            <h1 class="oy-page-title">{{ $pool->order?->order_number }}</h1>
            <p class="oy-page-description">{{ $pool->order?->organization?->name }}</p>
        </div>
        <div class="oy-page-actions">
            <span class="oy-badge {{ $pool->status === 'finalized' ? 'oy-badge-success' : 'oy-badge-warning' }}">
                {{ ucfirst($pool->status) }}
            </span>
            @can('deriveContributions', $pool)
                <form method="POST" action="{{ route('earnings.pools.refresh-contributions', $pool) }}" class="inline">
                    @csrf
                    <button type="submit" class="oy-btn oy-btn-secondary" data-refresh-contributions>
                        Refresh from workflow
                    </button>
                </form>
            @endcan
            @can('assignContribution', $pool)
                <button type="button" class="oy-btn oy-btn-secondary" data-open-pool-modal="contribution-modal">
                    Add contribution
                </button>
            @endcan
            @can('finalize', $pool)
                <button type="button" class="oy-btn oy-btn-primary" data-open-pool-modal="finalize-modal">
                    Finalize pool
                </button>
            @endcan
        </div>
    </div>

    @foreach (['status_message', 'success'] as $flashKey)
        @if (session($flashKey))
            <div class="oy-alert oy-alert-success mb-5">{{ session($flashKey) }}</div>
        @endif
    @endforeach

    @if (session('pool_held'))
        <div class="oy-alert oy-alert-warning mb-5" data-pool-held>{{ session('pool_held') }}</div>
    @endif

    @if (session('derivation_skipped'))
        <div class="oy-alert oy-alert-warning mb-5" data-derivation-skipped>
            <strong>Not credited from workflow:</strong>
            <ul class="list-disc ml-5 mt-2">
                @foreach (session('derivation_skipped') as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="space-y-6">

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="oy-card">
                <div class="oy-card-header"><h2 class="oy-card-title">Pool</h2></div>
                <div class="oy-card-body">
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div><dt class="oy-meta">Quotation</dt><dd>{{ $pool->quotation_number }}</dd></div>
                        <div><dt class="oy-meta">Source amount</dt><dd>₦{{ number_format((float) $pool->source_amount, 2) }}</dd></div>
                        <div><dt class="oy-meta">Rate</dt><dd>{{ rtrim(rtrim((string) $pool->rate_percent, '0'), '.') }}%</dd></div>
                        <div><dt class="oy-meta">Pool amount</dt><dd data-pool-amount>₦{{ number_format((float) $pool->pool_amount, 2) }}</dd></div>
                        <div><dt class="oy-meta">Delivered</dt><dd>{{ $pool->delivered_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                        @if ($pool->finalized_at)
                            <div><dt class="oy-meta">Finalized</dt><dd>{{ $pool->finalized_at->format('d M Y H:i') }}</dd></div>
                        @endif
                    </dl>
                </div>
            </section>

            <section class="oy-card">
                <div class="oy-card-header"><h2 class="oy-card-title">Payment</h2></div>
                <div class="oy-card-body">
                    <dl class="grid gap-4 sm:grid-cols-3">
                        <div><dt class="oy-meta">Order total</dt><dd>₦{{ number_format($payment['total'], 2) }}</dd></div>
                        <div><dt class="oy-meta">Paid</dt><dd>₦{{ number_format($payment['paid'], 2) }}</dd></div>
                        <div><dt class="oy-meta">Balance</dt><dd>₦{{ number_format($payment['balance'], 2) }}</dd></div>
                    </dl>
                    @if (! $payment['covered'] && $pool->status !== 'finalized')
                        <div class="oy-alert oy-alert-warning mt-4" data-payment-warning>
                            Cannot finalize: the order balance is outstanding.
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <section class="oy-card">
            <div class="oy-card-header"><h2 class="oy-card-title">Contributions</h2></div>
            <div class="oy-card-body">
                @if ($pool->contributions->isEmpty())
                    <p class="text-sm opacity-70">No contributions recorded yet.</p>
                @else
                    <div class="oy-table-scroll">
                        <table class="oy-table">
                            <thead>
                                <tr>
                                    <th scope="col">Staff</th>
                                    <th scope="col">Activity</th>
                                    <th scope="col">Points</th>
                                    <th scope="col">Source</th>
                                    <th scope="col">Reason</th>
                                    <th scope="col">When</th>
                                    <th scope="col"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pool->contributions as $contribution)
                                    <tr class="{{ $contribution->voided_at ? 'line-through opacity-60' : '' }}" @if ($contribution->voided_at) data-voided-contribution @endif>
                                        <td>{{ $names[$contribution->user_id] ?? 'Unknown' }}</td>
                                        <td>{{ str_replace('_', ' ', $contribution->activity_key) }}</td>
                                        <td>{{ rtrim(rtrim((string) $contribution->points, '0'), '.') }}</td>
                                        <td>{{ ucfirst($contribution->source_type) }}</td>
                                        <td>{{ $contribution->reason ?? '—' }}</td>
                                        <td>{{ $contribution->activity_at?->format('d M Y H:i') }}</td>
                                        <td class="no-underline">
                                            @if ($contribution->voided_at)
                                                <span class="text-xs">Voided: {{ $contribution->void_reason }}</span>
                                            @else
                                                @can('voidContribution', $pool)
                                                    <button type="button" class="oy-btn oy-btn-secondary"
                                                        data-void-contribution
                                                        data-void-id="{{ $contribution->id }}"
                                                        data-void-url="{{ route('earnings.pools.contributions.void', [$pool, $contribution]) }}">
                                                        Void
                                                    </button>
                                                @endcan
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        @if ($pool->status === 'finalized')
            <section class="oy-card">
                <div class="oy-card-header"><h2 class="oy-card-title">Finalized allocations</h2></div>
                <div class="oy-card-body">
                    <div class="oy-table-scroll">
                        <table class="oy-table">
                            <thead>
                                <tr>
                                    <th scope="col">Staff</th>
                                    <th scope="col">Points</th>
                                    <th scope="col">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pool->allocations as $allocation)
                                    <tr>
                                        <td>{{ $names[$allocation->user_id] ?? 'Unknown' }}</td>
                                        <td>{{ rtrim(rtrim((string) $allocation->effective_points, '0'), '.') }}</td>
                                        <td>₦{{ number_format((float) $allocation->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @else
            <section class="oy-card">
                <div class="oy-card-header"><h2 class="oy-card-title">Allocation preview</h2></div>
                <div class="oy-card-body">
                    @if ($preview['verdict'] === 'held')
                        <div class="oy-alert oy-alert-warning" data-preview-held>
                            This pool would be held:
                            {{ collect($preview['hold_reasons'])->map(fn ($r) => str_replace('_', ' ', $r))->implode(', ') }}.
                        </div>
                    @else
                        <div class="oy-table-scroll">
                            <table class="oy-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Staff</th>
                                        <th scope="col">Points</th>
                                        <th scope="col">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($preview['allocations'] as $row)
                                        <tr>
                                            <td>{{ $names[$row['user_id']] ?? 'Unknown' }}</td>
                                            <td>{{ rtrim(rtrim((string) $row['effective_points'], '0'), '.') }}</td>
                                            <td>₦{{ number_format((float) $row['amount'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="text-sm opacity-70 mt-3">Preview only. Nothing is recorded until the pool is finalized.</p>
                    @endif
                </div>
            </section>
        @endif

    </div>

    @can('assignContribution', $pool)
        <div id="contribution-modal" data-pool-modal
             data-reopen="{{ $errors->hasAny(['user_id', 'activity_key', 'activity_at', 'reason']) ? 'true' : 'false' }}"
             class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4"
             role="dialog" aria-modal="true" aria-labelledby="contribution-modal-title">
            <div class="oy-card mx-auto mt-16 max-w-lg">
                <div class="oy-card-header"><h2 id="contribution-modal-title" class="oy-card-title">Add contribution</h2></div>
                <form method="POST" action="{{ route('earnings.pools.contributions.store', $pool) }}" class="oy-card-body space-y-4">
                    @csrf
                    <div>
                        <label for="contribution-user" class="oy-meta">Staff member</label>
                        <select id="contribution-user" name="user_id" class="oy-input w-full" required data-modal-focus>
                            <option value="">Select staff</option>
                            @foreach ($staff as $person)
                                <option value="{{ $person->id }}" @selected((string) old('user_id') === (string) $person->id)>{{ $person->name }}</option>
                            @endforeach
                        </select>
                        @error('user_id') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="contribution-activity" class="oy-meta">Activity</label>
                        <select id="contribution-activity" name="activity_key" class="oy-input w-full" required>
                            <option value="">Select activity</option>
                            @foreach ($weights as $weight)
                                <option value="{{ $weight->activity_key }}" @selected(old('activity_key') === $weight->activity_key)>{{ $weight->label }}</option>
                            @endforeach
                        </select>
                        @error('activity_key') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="contribution-at" class="oy-meta">When the work was done</label>
                        <input id="contribution-at" type="datetime-local" name="activity_at" value="{{ old('activity_at') }}" class="oy-input w-full" required>
                        @error('activity_at') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="contribution-reason" class="oy-meta">Reason (at least 10 characters)</label>
                        <textarea id="contribution-reason" name="reason" rows="3" class="oy-input w-full" required>{{ old('reason') }}</textarea>
                        @error('reason') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" class="oy-btn oy-btn-secondary" data-close-pool-modal>Cancel</button>
                        <button type="submit" class="oy-btn oy-btn-primary">Record contribution</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @can('voidContribution', $pool)
        <div id="void-modal" data-pool-modal
             data-reopen="{{ $errors->hasAny(['void_reason', 'contribution']) ? 'true' : 'false' }}"
             class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4"
             role="dialog" aria-modal="true" aria-labelledby="void-modal-title">
            <div class="oy-card mx-auto mt-24 max-w-lg">
                <div class="oy-card-header"><h2 id="void-modal-title" class="oy-card-title">Void contribution</h2></div>
                <form id="void-form" method="POST"
                      action="{{ old('void_target') ? route('earnings.pools.contributions.void', [$pool, old('void_target')]) : '' }}"
                      class="oy-card-body space-y-4">
                    @csrf
                    <input type="hidden" name="void_target" id="void-target" value="{{ old('void_target') }}">
                    <p class="text-sm">The contribution stays in history but no longer counts toward the allocation.</p>
                    @error('contribution') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    <div>
                        <label for="void-reason" class="oy-meta">Reason (at least 10 characters)</label>
                        <textarea id="void-reason" name="void_reason" rows="3" class="oy-input w-full" required data-modal-focus>{{ old('void_reason') }}</textarea>
                        @error('void_reason') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" class="oy-btn oy-btn-secondary" data-close-pool-modal>Cancel</button>
                        <button type="submit" class="oy-btn oy-btn-primary">Void contribution</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @can('finalize', $pool)
        <div id="finalize-modal" data-pool-modal data-reopen="false"
             class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4"
             role="dialog" aria-modal="true" aria-labelledby="finalize-modal-title">
            <div class="oy-card mx-auto mt-24 max-w-lg">
                <div class="oy-card-header"><h2 id="finalize-modal-title" class="oy-card-title">Finalize earnings pool</h2></div>
                <form method="POST" action="{{ route('earnings.pools.finalize', $pool) }}" class="oy-card-body space-y-4">
                    @csrf
                    <p class="text-sm">
                        This records each person's share and credits their wallet. Finalized shares cannot be edited;
                        corrections are made through audited adjustments.
                    </p>
                    @if (! $payment['covered'])
                        <div class="oy-alert oy-alert-warning">The order balance is still outstanding, so finalization will be refused.</div>
                    @endif
                    <div class="flex justify-end gap-3">
                        <button type="button" class="oy-btn oy-btn-secondary" data-close-pool-modal data-modal-focus>Cancel</button>
                        <button type="submit" class="oy-btn oy-btn-primary">Finalize pool</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

</div>
@endsection
