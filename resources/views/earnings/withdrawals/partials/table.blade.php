<div data-list-total="{{ $withdrawals->total() }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-1 border-b border-slate-200 px-4 py-4 sm:px-5">
            <h2 class="font-semibold text-slate-900">Withdrawal register</h2>
            <p class="text-xs text-slate-500">Approval and rejection decisions require a recorded reason.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3">Staff member</th>
                        <th class="whitespace-nowrap px-4 py-3">Amount</th>
                        <th class="min-w-48 px-4 py-3">Payout account</th>
                        <th class="whitespace-nowrap px-4 py-3">Requested</th>
                        <th class="px-4 py-3">Status / decision</th>
                        <th class="min-w-40 px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($withdrawals as $withdrawal)
                        <tr class="align-top hover:bg-slate-50/70">
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $withdrawal->user->name }}</div>
                                <div class="mt-1 break-all text-xs text-slate-500">{{ $withdrawal->user->email }}</div>
                                <div class="mt-2 font-mono text-xs text-slate-400">
                                    WD-{{ str_pad((string) $withdrawal->id, 6, '0', STR_PAD_LEFT) }}
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 font-bold text-slate-900">
                                ₦{{ number_format((float) $withdrawal->amount, 2) }}
                            </td>

                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-800">{{ $withdrawal->bank_name }}</div>
                                <div class="mt-1 text-slate-600">{{ $withdrawal->account_name }}</div>
                                <div class="mt-1 font-mono text-xs text-slate-500">
                                    •••• {{ $withdrawal->account_last_four }}
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">
                                {{ $withdrawal->requested_at?->format('d M Y') ?? '—' }}
                                <div class="mt-1 text-xs text-slate-400">
                                    {{ $withdrawal->requested_at?->format('H:i') ?? '' }}
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                @php
                                    $statusClasses = [
                                        'requested' => 'bg-amber-100 text-amber-800',
                                        'approved' => 'bg-blue-100 text-blue-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'paid' => 'bg-emerald-100 text-emerald-800',
                                        'cancelled' => 'bg-slate-100 text-slate-700',
                                    ];
                                @endphp

                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$withdrawal->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ ucfirst($withdrawal->status) }}
                                </span>

                                @if($withdrawal->status === 'approved' && $withdrawal->notes)
                                    <div class="mt-3 max-w-xs">
                                        <p class="text-xs font-semibold text-slate-600">Approval reason</p>
                                        <p class="mt-1 whitespace-pre-line text-xs leading-5 text-slate-600">{{ $withdrawal->notes }}</p>
                                    </div>
                                @endif

                                @if($withdrawal->reject_reason)
                                    <div class="mt-3 max-w-xs">
                                        <p class="text-xs font-semibold text-red-700">Rejection reason</p>
                                        <p class="mt-1 whitespace-pre-line text-xs leading-5 text-red-700">{{ $withdrawal->reject_reason }}</p>
                                    </div>
                                @endif

                                @if($withdrawal->payment_reference)
                                    <div class="mt-3">
                                        <p class="text-xs font-semibold text-slate-600">Payment reference</p>
                                        <p class="mt-1 break-all font-mono text-xs text-slate-600">{{ $withdrawal->payment_reference }}</p>
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-4">
                                @if((int) $withdrawal->user_id === (int) auth()->id())
                                    <p class="text-xs leading-5 text-amber-700">You cannot manage your own request.</p>
                                @elseif($withdrawal->status === 'requested')
                                    <div class="flex flex-col items-start gap-2">
                                        <button type="button"
                                                class="oy-btn oy-btn-primary"
                                                data-withdrawal-modal-open
                                                data-modal-type="approve"
                                                data-action="{{ route('earnings.withdrawals.approve', $withdrawal) }}"
                                                data-person="{{ $withdrawal->user->name }}"
                                                data-amount="₦{{ number_format((float) $withdrawal->amount, 2) }}">
                                            Approve
                                        </button>
                                        <button type="button"
                                                class="oy-btn oy-btn-secondary"
                                                data-withdrawal-modal-open
                                                data-modal-type="reject"
                                                data-action="{{ route('earnings.withdrawals.reject', $withdrawal) }}"
                                                data-person="{{ $withdrawal->user->name }}"
                                                data-amount="₦{{ number_format((float) $withdrawal->amount, 2) }}">
                                            Reject
                                        </button>
                                    </div>
                                @elseif($withdrawal->status === 'approved')
                                    <button type="button"
                                            class="oy-btn oy-btn-primary"
                                            data-withdrawal-modal-open
                                            data-modal-type="paid"
                                            data-action="{{ route('earnings.withdrawals.paid', $withdrawal) }}"
                                            data-person="{{ $withdrawal->user->name }}"
                                            data-amount="₦{{ number_format((float) $withdrawal->amount, 2) }}">
                                        Record payment
                                    </button>
                                @else
                                    <span class="text-xs text-slate-400">No further actions</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <p class="font-semibold text-slate-700">No withdrawal requests found</p>
                                <p class="mt-1 text-sm text-slate-500">Try changing your search or filters.</p>
                                @if(request()->query())
                                    <a href="{{ route('earnings.withdrawals.index') }}"
                                       class="mt-3 inline-block text-sm font-semibold underline">Clear all filters</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($withdrawals->hasPages())
            <div class="border-t border-slate-200 px-4 py-4 sm:px-5">
                {{ $withdrawals->links() }}
            </div>
        @endif
    </div>
