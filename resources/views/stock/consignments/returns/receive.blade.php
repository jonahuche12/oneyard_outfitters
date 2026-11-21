@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-7 px-4 py-5 sm:py-8">
    <div>
        <a href="{{ route('stock.consignments.show', $stockReturn->stock_consignment_id) }}"
           class="text-sm font-semibold text-slate-600 transition hover:text-slate-950 hover:underline">&larr; Back to consignment</a>
        <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Confirm return receipt</h1>
        <p class="mt-1 text-sm text-gray-600">
            {{ $stockReturn->reference }} — confirm what physically arrived at {{ $stockReturn->consignment->sourceStore->name }}.
        </p>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm leading-6 text-red-800">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('stock.consignments.returns.confirm', $stockReturn) }}"
          class="space-y-7 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        @csrf
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-3 py-3">Stock item</th>
                        <th class="px-3 py-3 text-right">Dispatched back</th>
                        <th class="px-3 py-3">Quantity received</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($stockReturn->items as $line)
                        <tr>
                            <td class="px-3 py-3 font-medium">{{ $line->consignmentItem->item->description }}</td>
                            <td class="px-3 py-3 text-right">{{ number_format((float) $line->quantity_dispatched, 3) }}</td>
                            <td class="px-3 py-3">
                                <input type="hidden" name="items[{{ $loop->index }}][stock_consignment_return_item_id]"
                                       value="{{ $line->id }}">
                                <input type="number" name="items[{{ $loop->index }}][quantity_received]"
                                       min="0" max="{{ $line->quantity_dispatched }}" step="0.001"
                                       value="{{ old('items.'.$loop->index.'.quantity_received', $line->quantity_dispatched) }}"
                                       required class="w-32 rounded-md border-gray-300 text-sm">
                                <p class="mt-1 text-xs text-gray-500">
                                    Not received if short: quantity dispatched minus quantity received
                                </p>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">Receipt notes</label>
            <textarea id="notes" name="notes" rows="3" maxlength="2000"
                      class="mt-1 block w-full rounded-md border-gray-300">{{ old('notes') }}</textarea>
        </div>

        <button type="submit" class="rounded-md bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white">
            Confirm actual receipt
        </button>
    </form>
</div>
@endsection
