@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl space-y-7 px-4 py-5 sm:py-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm text-gray-500">Stock consignment</p>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $consignment->reference }}</h1>
            <p class="mt-1 text-sm text-gray-600">Dispatched {{ $consignment->dispatched_at?->format('d M Y, h:i A') }}</p>
        </div>
        <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-800">
            {{ ucfirst(str_replace('_', ' ', $consignment->status)) }}
        </span>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <section class="grid gap-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 md:grid-cols-2">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Source store</p>
            <p class="mt-1 font-semibold">{{ $consignment->sourceStore->name }}</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Destination store</p>
            <p class="mt-1 font-semibold">{{ $consignment->destinationStore->name }}</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Dispatched by</p>
            <p class="mt-1">{{ $consignment->dispatcher?->name ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Created by</p>
            <p class="mt-1">{{ $consignment->creator?->name ?? '—' }}</p>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50/70 px-5 py-4 sm:px-6">
            <h2 class="font-semibold text-gray-900">Dispatched items</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Item</th>
                        <th class="px-5 py-3 text-right">Quantity</th>
                        <th class="px-5 py-3 text-right">Unit cost</th>
                        <th class="px-5 py-3 text-right">Line cost</th>
                        <th class="px-5 py-3 text-right">Market price</th>
                        <th class="px-5 py-3 text-right">Expected sales</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($consignment->items as $line)
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $line->item?->description ?? 'Item unavailable' }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format((float) $line->quantity_dispatched, 3) }}</td>
                            <td class="px-5 py-3 text-right">₦{{ number_format((float) $line->unit_cost, 2) }}</td>
                            <td class="px-5 py-3 text-right">₦{{ number_format((float) $line->line_cost, 2) }}</td>
                            <td class="px-5 py-3 text-right">{{ $line->market_price === null ? '—' : '₦'.number_format((float) $line->market_price, 2) }}</td>
                            <td class="px-5 py-3 text-right">₦{{ number_format((float) $line->expected_sales_value, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 font-semibold text-gray-900">
                    <tr>
                        <td colspan="3" class="px-5 py-3 text-right">Totals</td>
                        <td class="px-5 py-3 text-right">₦{{ number_format((float) $consignment->total_cost, 2) }}</td>
                        <td></td>
                        <td class="px-5 py-3 text-right">₦{{ number_format((float) $consignment->expected_sales_value, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="text-xs uppercase tracking-wide text-gray-500">Total dispatch cost</p>
            <p class="mt-2 text-xl font-semibold">₦{{ number_format((float) $consignment->total_cost, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="text-xs uppercase tracking-wide text-gray-500">Expected sales value</p>
            <p class="mt-2 text-xl font-semibold">₦{{ number_format((float) $consignment->expected_sales_value, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="text-xs uppercase tracking-wide text-gray-500">Expected gross return</p>
            <p class="mt-2 text-xl font-semibold">₦{{ number_format((float) $consignment->expected_gross_return, 2) }}</p>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="font-semibold text-gray-900">Notes</h2>
        <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $consignment->notes ?: 'No dispatch notes.' }}</p>
        <p class="mt-4 text-xs text-gray-500">All dispatched quantities are held in destination quarantine until the receiving store acknowledges them.</p>
    </section>

    <div>
        <a href="{{ route('stock.index') }}" class="text-sm font-medium text-gray-700 underline">Back to stock</a>
    </div>
</div>

    @php
        $viewer = auth()->user();
        $viewerAssignment = $viewer->activeStoreAssignment;
        $canAcknowledge = $viewer->is_active
            && $viewerAssignment
            && in_array($viewerAssignment->role, [
                \App\Models\StoreStaff::ROLE_COORDINATOR,
                \App\Models\StoreStaff::ROLE_ASSISTANT,
            ], true)
            && (int) $viewerAssignment->store_id === (int) $consignment->destination_store_id;

        $canConfirmDiscrepancies = $viewer->is_active
            && $viewerAssignment
            && $viewerAssignment->role === \App\Models\StoreStaff::ROLE_COORDINATOR
            && (int) $viewerAssignment->store_id === (int) $consignment->source_store_id;

        $discrepancies = $consignment->items
            ->flatMap(fn ($line) => $line->discrepancies);
        $acknowledgements = $consignment->acknowledgements()
            ->with(['acknowledger', 'items.discrepancies'])
            ->latest('acknowledged_at')
            ->get();
    @endphp

    <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Reported discrepancies</h2>
            <p class="mt-1 text-sm text-gray-600">
                Damaged quantities require source-store write-off confirmation. Shortages require source-store confirmation.
            </p>
        </div>

        @forelse ($discrepancies as $discrepancy)
            @php
                $line = $consignment->items->firstWhere('id', $discrepancy->stock_consignment_item_id);
                $isOpen = $discrepancy->status === 'open';
            @endphp

            <div class="mt-5 border-t border-gray-100 pt-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-medium text-gray-900">
                            {{ $line?->item?->description ?? 'Stock item' }}
                            — {{ ucfirst($discrepancy->type) }}
                        </p>
                        <p class="mt-1 text-sm text-gray-600">
                            Quantity: {{ number_format((float) $discrepancy->quantity, 3) }}
                            · Reported by {{ $discrepancy->reporter?->name ?? 'Staff member' }}
                            · {{ $discrepancy->reported_at?->format('d M Y, h:i A') ?? '—' }}
                        </p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $isOpen ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800' }}">
                        {{ ucfirst($discrepancy->status) }}
                    </span>
                </div>

                @if (! $isOpen)
                    <p class="mt-2 text-sm text-gray-600">
                        Resolution: {{ ucfirst(str_replace('_', ' ', $discrepancy->resolution ?? 'confirmed')) }}
                        · Confirmed by {{ $discrepancy->resolver?->name ?? 'Staff member' }}
                        · {{ $discrepancy->resolved_at?->format('d M Y, h:i A') ?? '—' }}
                    </p>
                    @if ($discrepancy->resolution_reason)
                        <p class="mt-1 whitespace-pre-line text-sm text-gray-600">{{ $discrepancy->resolution_reason }}</p>
                    @endif
                @elseif ($canConfirmDiscrepancies)
                    <form method="POST"
                        action="{{ route('stock.consignments.discrepancies.confirm', [$consignment, $discrepancy]) }}"
                        class="mt-4 space-y-3 rounded-lg bg-gray-50 p-4">
                        @csrf
                        <label for="resolution_reason_{{ $discrepancy->id }}" class="block text-sm font-medium text-gray-700">
                            Confirmation reason
                        </label>
                        <textarea
                            id="resolution_reason_{{ $discrepancy->id }}"
                            name="resolution_reason"
                            rows="3"
                            maxlength="2000"
                            required
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Explain the source-store confirmation..."
                        >{{ old('resolution_reason') }}</textarea>
                        @error('resolution_reason')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                            class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            {{ $discrepancy->type === 'damaged' ? 'Confirm write-off' : 'Confirm shortage' }}
                        </button>
                    </form>
                @else
                    <p class="mt-3 text-sm text-amber-700">
                        Awaiting confirmation by the source-store coordinator.
                    </p>
                @endif
            </div>
        @empty
            <p class="mt-4 text-sm text-gray-500">No discrepancies have been reported.</p>
        @endforelse
    </section>

    <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold">Acknowledgement history</h2>
                <p class="text-sm text-gray-600">Recorded receipt quantities and outstanding discrepancies.</p>
            </div>
            @if ($canAcknowledge && ! in_array($consignment->status, [
                \App\Models\StockConsignment::STATUS_RECONCILED,
                \App\Models\StockConsignment::STATUS_CANCELLED,
            ], true))
                <a href="{{ route('stock.consignments.acknowledgements.create', $consignment) }}"
                    class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Record Acknowledgement
                </a>
            @endif
        </div>

        @forelse ($acknowledgements as $acknowledgement)
            <div class="mt-5 border-t border-gray-200 pt-4">
                <div class="flex flex-wrap justify-between gap-2 text-sm">
                    <span class="font-medium">
                        {{ $acknowledgement->acknowledger->name ?? 'Staff member' }}
                    </span>
                    <span class="text-gray-500">
                        {{ $acknowledgement->acknowledged_at }}
                    </span>
                </div>

                @if ($acknowledgement->notes)
                    <p class="mt-1 text-sm text-gray-600">{{ $acknowledgement->notes }}</p>
                @endif

                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-gray-500">
                            <tr>
                                <th class="py-2 pr-4">Item</th>
                                <th class="py-2 pr-4">Good</th>
                                <th class="py-2 pr-4">Damaged</th>
                                <th class="py-2 pr-4">Shortage</th>
                                <th class="py-2">Discrepancies</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($acknowledgement->items as $acknowledgedLine)
                                <tr class="border-t border-gray-100">
                                    <td class="py-2 pr-4">
                                        {{ $acknowledgedLine->consignmentItem->item->description ?? 'Stock item' }}
                                    </td>
                                    <td class="py-2 pr-4">{{ number_format((float) $acknowledgedLine->quantity_good, 3) }}</td>
                                    <td class="py-2 pr-4">{{ number_format((float) $acknowledgedLine->quantity_damaged, 3) }}</td>
                                    <td class="py-2 pr-4">{{ number_format((float) $acknowledgedLine->quantity_shortage, 3) }}</td>
                                    <td class="py-2">
                                        {{ $acknowledgedLine->discrepancies->count() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="mt-5 text-sm text-gray-500">No acknowledgement has been recorded yet.</p>
        @endforelse
    </section>


    {{-- Consignment returns --}}
    <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Returns to source store</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Only acknowledged-good stock can be returned. Receipt must be confirmed by the source coordinator.
                </p>
            </div>
            @if ($canDispatchReturn)
                <a href="{{ route('stock.consignments.returns.create', $consignment) }}"
                   class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50">
                    Dispatch return
                </a>
            @endif
        </div>

        @if ($consignment->returns->isEmpty())
            <p class="mt-4 text-sm text-slate-500">No returns have been dispatched for this consignment.</p>
        @else
            <div class="mt-4 space-y-4">
                @foreach ($consignment->returns->sortByDesc('dispatched_at') as $stockReturn)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $stockReturn->reference }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Dispatched {{ $stockReturn->dispatched_at?->format('d M Y, h:i A') }}
                                    by {{ $stockReturn->dispatcher?->name ?? '—' }}
                                </p>
                                @if ($stockReturn->received_at)
                                    <p class="mt-1 text-xs text-slate-500">
                                        Receipt confirmed {{ $stockReturn->received_at->format('d M Y, h:i A') }}
                                        by {{ $stockReturn->receiver?->name ?? '—' }}
                                    </p>
                                @else
                                    <p class="mt-1 text-xs font-medium text-amber-700">Awaiting source-store receipt</p>
                                @endif
                            </div>
                            @if ($canReceiveReturn && ! $stockReturn->received_at)
                                <a href="{{ route('stock.consignments.returns.receive', $stockReturn) }}"
                                   class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50">
                                    Confirm receipt
                                </a>
                            @endif
                        </div>

                        <div class="mt-3 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-left text-xs text-slate-500">
                                    <tr>
                                        <th class="py-2 pr-4">Item</th>
                                        <th class="py-2 px-2 text-right">Sent back</th>
                                        <th class="py-2 px-2 text-right">Received</th>
                                        <th class="py-2 pl-2 text-right">Not received</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($stockReturn->items as $returnLine)
                                        @php
                                            $received = $returnLine->quantity_received;
                                            $missing = $received === null
                                                ? null
                                                : bcsub((string) $returnLine->quantity_dispatched, (string) $received, 3);
                                        @endphp
                                        <tr>
                                            <td class="py-2 pr-4">{{ $returnLine->consignmentItem->item->description }}</td>
                                            <td class="px-2 py-2 text-right">{{ number_format((float) $returnLine->quantity_dispatched, 3) }}</td>
                                            <td class="px-2 py-2 text-right">{{ $received === null ? '—' : number_format((float) $received, 3) }}</td>
                                            <td class="py-2 pl-2 text-right">{{ $missing === null ? 'Pending' : number_format((float) $missing, 3) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($stockReturn->dispatch_notes)
                            <p class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ $stockReturn->dispatch_notes }}</p>
                        @endif
                        @if ($stockReturn->receipt_notes)
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-600">
                                <span class="font-medium">Receipt notes:</span> {{ $stockReturn->receipt_notes }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>

@endsection
