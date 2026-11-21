@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl space-y-7 px-4 py-5 sm:py-8">
    <div class="mb-6">
        <a href="{{ route('stock.consignments.show', $consignment) }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 hover:underline">
            &larr; Back to consignment
        </a>
        <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Acknowledge {{ $consignment->reference }}</h1>
        <p class="mt-1 text-sm text-slate-600">
            Destination: {{ $consignment->destinationStore->name }}
        </p>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm leading-6 text-red-800">
            <p class="font-medium">Please correct the following:</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($lines->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-700 shadow-sm sm:p-7">
            All dispatched quantities have already been accounted for. No further acknowledgement is available.
        </div>
    @else
        <form method="POST" action="{{ route('stock.consignments.acknowledgements.store', $consignment) }}" class="space-y-7 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            @csrf

            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Stock item</th>
                            <th class="px-4 py-3">Dispatched</th>
                            <th class="px-4 py-3">Previously accounted</th>
                            <th class="px-4 py-3">Remaining</th>
                            <th class="px-4 py-3">Good</th>
                            <th class="px-4 py-3">Damaged</th>
                            <th class="px-4 py-3">Shortage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach ($lines as $index => $line)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $line->item->description }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $line->item->category }}</div>
                                    <input type="hidden"
                                        name="items[{{ $index }}][stock_consignment_item_id]"
                                        value="{{ $line->id }}">
                                </td>
                                <td class="px-4 py-3">{{ number_format((float) $line->quantity_dispatched, 3) }}</td>
                                <td class="px-4 py-3">{{ number_format($line->quantity_accounted, 3) }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ number_format($line->quantity_remaining, 3) }}</td>
                                @foreach (['quantity_good', 'quantity_damaged', 'quantity_shortage'] as $field)
                                    <td class="px-2 py-3">
                                        <input
                                            type="number"
                                            name="items[{{ $index }}][{{ $field }}]"
                                            value="{{ old("items.$index.$field", '0') }}"
                                            min="0"
                                            max="{{ $line->quantity_remaining }}"
                                            step="0.001"
                                            required
                                            class="block w-28 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="space-y-2">
                <label for="notes" class="block text-sm font-medium text-slate-700">Acknowledgement notes</label>
                <textarea id="notes" name="notes" rows="3"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">{{ old('notes') }}</textarea>
            </div>

            <div class="flex justify-end border-t border-slate-100 pt-5">
                <button type="submit"
                    class="oy-btn oy-btn-primary">
                    Record Acknowledgement
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
