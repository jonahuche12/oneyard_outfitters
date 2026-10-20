@php
    $eligibilityUser = auth()->user();
    $canDecideEligibility = $eligibilityUser
        && $eligibilityUser->is_active
        && $eligibilityUser->hasPermission('measurements.eligibility.manage');
    $eligibilityEditable = $canDecideEligibility
        && ($order->status === \App\Models\Order::STATUS_APPROVED
            || ($order->status === \App\Models\Order::STATUS_IN_PRODUCTION && ! $order->productionPlan));
    $pendingEligibilityCount = $order->items
        ->filter(fn ($i) => ($i->measurement_eligibility ?? 'pending') === 'pending')
        ->count();
    $reopenItemId = old('_item_id');
    $reopenEligibility = $reopenItemId
        && ($errors->has('eligibility') || $errors->has('reason'))
        && $order->items->contains('id', (int) $reopenItemId);
    $eligibilityBadges = [
        'pending' => ['Pending', 'bg-amber-100 text-amber-800'],
        'required' => ['Measurements required', 'bg-blue-100 text-blue-800'],
        'not_required' => ['Not required', 'bg-slate-100 text-slate-700'],
    ];
@endphp

@if($order->items->isNotEmpty())
    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" id="measurement-eligibility">
        <h2 class="text-lg font-semibold text-slate-900">Measurement Eligibility</h2>
        <p class="mt-1 text-sm text-slate-600">
            Each item must be marked as requiring measurements or not before the order can enter production.
        </p>

        @if($pendingEligibilityCount > 0 && $order->status === \App\Models\Order::STATUS_APPROVED)
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                {{ $pendingEligibilityCount }} {{ \Illuminate\Support\Str::plural('item', $pendingEligibilityCount) }}
                still pending. Production cannot start until every item is decided.
            </div>
        @endif

        <ul class="mt-4 divide-y divide-slate-200">
            @foreach($order->items as $eligibilityItem)
                @php
                    $state = $eligibilityItem->measurement_eligibility ?? 'pending';
                    [$stateLabel, $stateClass] = $eligibilityBadges[$state] ?? $eligibilityBadges['pending'];
                @endphp
                <li class="py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="font-medium text-slate-900">{{ $eligibilityItem->item_name }}</div>
                            <span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $stateClass }}">
                                {{ $stateLabel }}
                            </span>
                            @if($eligibilityItem->measurement_eligibility_reason)
                                <p class="mt-2 text-sm text-slate-600">{{ $eligibilityItem->measurement_eligibility_reason }}</p>
                            @endif
                        </div>

                        @if($eligibilityEditable)
                            <button
                                type="button"
                                class="open-measurement-eligibility rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                                data-action="{{ route('orders.items.measurement-eligibility', [$order, $eligibilityItem]) }}"
                                data-item-id="{{ $eligibilityItem->id }}"
                                data-item-name="{{ $eligibilityItem->item_name }}"
                            >
                                {{ $state === 'pending' ? 'Decide' : 'Change decision' }}
                            </button>
                        @endif
                    </div>

                    @if($eligibilityItem->measurementEligibilityAudits->isNotEmpty())
                        <details class="mt-3 text-sm text-slate-600">
                            <summary class="cursor-pointer text-slate-500">Decision history</summary>
                            <ul class="mt-2 space-y-2">
                                @foreach($eligibilityItem->measurementEligibilityAudits as $audit)
                                    <li class="rounded-lg bg-slate-50 p-3">
                                        <div>
                                            {{ str_replace('_', ' ', $audit->old_eligibility ?? 'none') }}
                                            &rarr;
                                            {{ str_replace('_', ' ', $audit->new_eligibility) }}
                                            by {{ $audit->changedBy?->name ?? 'Unknown' }}
                                            on {{ $audit->created_at?->format('M j, Y g:i A') }}
                                        </div>
                                        <div class="mt-1 text-slate-500">{{ $audit->reason }}</div>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>

    @if($eligibilityEditable)
        <div
            id="measurementEligibilityModal"
            class="fixed inset-0 z-50 hidden overflow-y-auto"
            aria-labelledby="measurementEligibilityModalTitle"
            aria-modal="true"
            role="dialog"
            data-reopen="{{ $reopenEligibility ? 1 : 0 }}"
        >
            <div id="measurementEligibilityBackdrop" class="fixed inset-0 bg-slate-900/60"></div>

            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">
                    <div class="flex items-start justify-between border-b border-slate-200 p-6">
                        <div>
                            <h2 id="measurementEligibilityModalTitle" class="text-lg font-semibold text-slate-900">
                                Measurement Eligibility
                            </h2>
                            <p class="mt-1 text-sm text-slate-600" id="measurementEligibilityItemName">
                                @if($reopenEligibility)
                                    {{ $order->items->firstWhere('id', (int) $reopenItemId)?->item_name }}
                                @endif
                            </p>
                        </div>
                        <button type="button" id="closeMeasurementEligibilityModal"
                                class="text-2xl leading-none text-slate-400 hover:text-slate-700" aria-label="Close">&times;</button>
                    </div>

                    <form
                        id="measurementEligibilityForm"
                        method="POST"
                        action="{{ $reopenEligibility ? route('orders.items.measurement-eligibility', [$order, (int) $reopenItemId]) : '#' }}"
                        data-submit-spinner
                        data-loading-text="Saving..."
                    >
                        @csrf
                        <input type="hidden" name="_item_id" id="measurementEligibilityItemId" value="{{ $reopenEligibility ? $reopenItemId : '' }}">

                        <div class="space-y-4 p-6">
                            <fieldset>
                                <legend class="text-sm font-medium text-slate-700">Decision</legend>
                                <label class="mt-2 flex items-center gap-2 text-sm">
                                    <input type="radio" name="eligibility" value="required"
                                           @checked($reopenEligibility && old('eligibility') === 'required')>
                                    Individual measurements are required
                                </label>
                                <label class="mt-2 flex items-center gap-2 text-sm">
                                    <input type="radio" name="eligibility" value="not_required"
                                           @checked($reopenEligibility && old('eligibility') === 'not_required')>
                                    Measurements are not required
                                </label>
                                @if($reopenEligibility)
                                    @error('eligibility')
                                        <p class="mt-2 text-sm text-red-600" data-eligibility-error>{{ $message }}</p>
                                    @enderror
                                @endif
                            </fieldset>

                            <div>
                                <label for="measurementEligibilityReason" class="text-sm font-medium text-slate-700">Reason</label>
                                <textarea id="measurementEligibilityReason" name="reason" rows="3" maxlength="2000"
                                          class="mt-1 w-full rounded-lg border border-slate-300 p-2 text-sm"
                                          placeholder="At least 10 characters">{{ $reopenEligibility ? old('reason') : '' }}</textarea>
                                @if($reopenEligibility)
                                    @error('reason')
                                        <p class="mt-1 text-sm text-red-600" data-eligibility-error>{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-slate-200 p-6">
                            <button type="button" id="cancelMeasurementEligibilityModal"
                                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700">Cancel</button>
                            <button type="submit"
                                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Save decision</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endif
