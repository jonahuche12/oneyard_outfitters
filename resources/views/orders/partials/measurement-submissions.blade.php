@php
    $viewer = auth()->user();
    $canViewMeasurements = $viewer && app(\App\Actions\Measurements\CanViewOrderMeasurements::class)->handle($viewer, $order);
    $reviewOpen = $order->status === \App\Models\Order::STATUS_APPROVED
        || ($order->status === \App\Models\Order::STATUS_IN_PRODUCTION && ! $order->productionPlan()->exists());
    $canReview = $viewer && $viewer->hasPermission('measurements.review') && $reviewOpen;
    $correctionContacts = collect();
    $linkedContactIds = [];
    if ($canReview) {
        $correctionContacts = \App\Models\Contact::query()
            ->where('organization_id', $order->organization_id)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();
        $linkedContactIds = \App\Models\MeasurementLink::query()
            ->where('order_id', $order->id)
            ->where('is_active_slot', 1)
            ->pluck('contact_id')
            ->all();
    }
    $latestByItem = collect();
    $requiredItems = collect();
    $pageItems = collect();
    $paginator = null;

    if ($canViewMeasurements) {
        $requiredItems = $order->items->where('measurement_eligibility', 'required');
        if ($requiredItems->isNotEmpty()) {
            $mpage = max(1, (int) request()->query('measurements_page', 1));
            $paginator = (new \Illuminate\Pagination\LengthAwarePaginator(
                $requiredItems->values()->forPage($mpage, 5)->values(),
                $requiredItems->count(),
                5,
                $mpage,
                ['path' => route('orders.show', $order), 'pageName' => 'measurements_page']
            ))->fragment('measurement-submissions');
            $pageItems = $paginator->getCollection();
            $latestByItem = \App\Models\MeasurementSubmission::query()
                ->whereIn('order_item_id', $pageItems->pluck('id'))
                ->with(['individualMeasurements', 'sizeQuantities'])
                ->orderByDesc('revision')
                ->get()
                ->groupBy('order_item_id')
                ->map->first();
        }
    }
    $statusLabels = [
        'draft' => 'Draft, not yet submitted',
        'submitted' => 'Submitted, awaiting review',
        'correction_requested' => 'Correction requested',
        'accepted' => 'Accepted',
    ];
@endphp

@if($canViewMeasurements && $requiredItems->isNotEmpty())
<section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" id="measurement-submissions">
    <h2 class="text-lg font-semibold text-slate-900">Measurements Received</h2>
    <p class="mt-1 text-sm text-slate-600">Latest revision for each item that requires measurements. Values are in inches. Drafts are still being entered by the customer and are not final.</p>

    <div class="mt-4 space-y-6">
        @foreach($pageItems as $item)
            @php $sub = $latestByItem->get($item->id); @endphp
            <div class="rounded-lg border border-slate-200 p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="font-semibold text-slate-900">{{ $item->item_name }} <span class="text-sm font-normal text-slate-500">({{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }} {{ $item->unit }})</span></h3>
                    @if($sub)
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $sub->status === 'draft' ? 'bg-amber-100 text-amber-800' : ($sub->status === 'accepted' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800') }}">
                            {{ $statusLabels[$sub->status] ?? ucfirst(str_replace('_', ' ', $sub->status)) }}
                        </span>
                    @else
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Not started</span>
                    @endif
                </div>

                @if($sub)
                    <p class="mt-1 text-xs text-slate-500">
                        Revision {{ $sub->revision }} ·
                        {{ $sub->measurement_method === 'size_quantity' ? 'Commercial sizes' : 'Individual measurements' }} ·
                        @if($sub->status === 'draft')
                            last saved {{ $sub->updated_at?->format('j M Y H:i') }}
                        @else
                            submitted {{ $sub->submitted_at?->format('j M Y H:i') }}
                        @endif
                    </p>

                    @if($sub->measurement_method === 'size_quantity')
                        @php $total = $sub->sizeQuantities->sum('quantity'); @endphp
                        @if($sub->sizeQuantities->isEmpty())
                            <p class="mt-3 text-sm text-slate-600">No sizes entered yet.</p>
                        @else
                            <div class="mt-3 overflow-x-auto">
                                <table class="text-sm">
                                    <thead class="text-xs uppercase text-slate-500"><tr><th class="pr-6 py-1 text-left">Size</th><th class="py-1 text-left">Quantity</th></tr></thead>
                                    <tbody>
                                        @foreach($sub->sizeQuantities as $q)
                                            <tr><td class="pr-6 py-1">{{ $q->size_label }}</td><td class="py-1">{{ $q->quantity }}</td></tr>
                                        @endforeach
                                        <tr class="border-t border-slate-200 font-semibold"><td class="pr-6 py-1">Total</td><td class="py-1">{{ $total }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @else
                        @if($sub->individualMeasurements->isEmpty())
                            <p class="mt-3 text-sm text-slate-600">No people saved yet.</p>
                        @else
                            <div class="mt-3 overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="text-xs uppercase text-slate-500">
                                        <tr><th class="py-1 pr-4">#</th><th class="pr-4">Name / label</th><th class="pr-4">Measurements</th><th>Notes</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        @foreach($sub->individualMeasurements as $m)
                                            <tr class="align-top">
                                                <td class="py-2 pr-4">{{ $m->position }}</td>
                                                <td class="pr-4">{{ $m->person_label ?: '—' }}</td>
                                                <td class="pr-4">
                                                    @foreach(($m->measurements ?? []) as $field => $value)
                                                        <span class="mr-3 inline-block whitespace-nowrap"><span class="text-slate-500">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span> {{ $value }}</span>
                                                    @endforeach
                                                </td>
                                                <td>{{ $m->notes ?: '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif

                    @if($canReview && $sub->status === 'submitted')
                        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-4">
                            <form method="POST" action="{{ route('orders.measurements.accept', [$order, $sub]) }}" data-submit-spinner data-loading-text="Accepting...">
                                @csrf
                                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Accept measurements</button>
                            </form>
                            <button type="button" class="rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50"
                                data-correction-open
                                data-action="{{ route('orders.measurements.correction', [$order, $sub]) }}"
                                data-item="{{ $item->item_name }}">Request correction</button>
                        </div>
                    @elseif($sub->status === 'correction_requested' && $sub->correction_reason)
                        <p class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-900"><strong>Correction requested:</strong> {{ $sub->correction_reason }}</p>
                    @endif
                @endif
            </div>
        @endforeach
    </div>

    @if($paginator && $paginator->hasPages())
        <div class="mt-6" data-measurement-pager>{{ $paginator->links() }}</div>
    @endif

    @if($canReview)
        @php $reopenId = old('review_submission_id'); @endphp
        <div id="correction-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="correction-title"
             @if(($errors->has('reason') || $errors->has('contact_ids')) && $reopenId) data-reopen-action="{{ route('orders.measurements.correction', [$order, (int) $reopenId]) }}" @endif>
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h3 id="correction-title" class="text-lg font-semibold text-slate-900">Request correction</h3>
                <p class="mt-1 text-sm text-slate-600">Each selected contact receives your explanation and a fresh link by email. Previously entered measurements are cleared so they can start again, by entering each person, uploading a spreadsheet, or choosing commercial sizes.</p>
                <form method="POST" action="" id="correction-form" class="mt-4" data-submit-spinner data-loading-text="Sending...">
                    @csrf
                    <input type="hidden" name="review_submission_id" id="correction-submission-id" value="{{ $reopenId }}">
                    <label for="correction-reason" class="block text-sm font-medium text-slate-700">What needs correcting?</label>
                    <textarea id="correction-reason" name="reason" rows="4" maxlength="1000" required class="mt-1 w-full rounded-lg border border-slate-300 p-2 text-sm">{{ old('reason') }}</textarea>
                    @error('reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror

                    <fieldset class="mt-4" data-correction-contacts>
                        <legend class="block text-sm font-medium text-slate-700">Email the new link to</legend>
                        @php $ticked = old('contact_ids', $linkedContactIds); @endphp
                        @forelse($correctionContacts as $c)
                            <label class="mt-2 flex items-start gap-2 text-sm {{ blank($c->email) ? 'text-slate-400' : 'text-slate-800' }}">
                                <input type="checkbox" name="contact_ids[]" value="{{ $c->id }}" data-correction-contact @checked(in_array($c->id, (array) $ticked)) @disabled(blank($c->email)) class="mt-0.5">
                                <span>{{ trim($c->first_name . ' ' . $c->last_name) }}
                                    <span class="text-xs">{{ filled($c->email) ? '(' . $c->email . ')' : '(no email address, cannot be selected)' }}</span>
                                </span>
                            </label>
                        @empty
                            <p class="mt-2 text-sm text-slate-600">This organization has no active contacts.</p>
                        @endforelse
                        <p class="mt-2 text-xs text-slate-500">Contacts who already hold a link are ticked. Their old link stops working when the new one is sent.</p>
                        @error('contact_ids')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        @error('contact_ids.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </fieldset>
                    <div class="mt-4 flex justify-end gap-3">
                        <button type="button" data-correction-close class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                        <button type="submit" data-correction-submit class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">Send correction request</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</section>
@endif
