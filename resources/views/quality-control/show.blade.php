@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a
            href="{{ route('quality-control.index') }}"
            class="text-sm font-medium text-gray-600 hover:text-gray-900"
        >
            ← Quality Control Queue
        </a>

        <div class="mt-3">
            <h1 class="text-2xl font-semibold text-gray-900">
                Quality Control — {{ $order->order_number }}
            </h1>
            <p class="mt-1 text-sm text-gray-600">
                {{ $order->organization->name }}
            </p>
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('quality-control.complete', $inspection) }}"
        class="space-y-6"
    >
        @csrf

        @if($errors->any())
            <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-gray-200">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Inspection Items</h2>
            </div>

            <div class="divide-y divide-gray-200">
                @foreach($inspection->items as $inspectionItem)
                    <div class="p-5">
                        <div class="grid gap-4 md:grid-cols-12 md:items-start">
                            <div class="md:col-span-4">
                                <h3 class="font-medium text-gray-900">
                                    {{ $inspectionItem->item_name }}
                                </h3>

                                <p class="mt-1 text-sm text-gray-500">
                                    Quantity:
                                    {{ rtrim(rtrim(number_format((float) $inspectionItem->quantity, 2), '0'), '.') }}
                                    {{ $inspectionItem->unit }}
                                </p>
                            </div>

                            @if($inspectionItem->isPiece())
                                <div class="md:col-span-3">
                                    <label
                                        for="failed_quantity_{{ $inspectionItem->id }}"
                                        class="block text-sm font-medium text-gray-700"
                                    >
                                        Failed quantity
                                    </label>

                                    <input
                                        id="failed_quantity_{{ $inspectionItem->id }}"
                                        name="items[{{ $inspectionItem->id }}][failed_quantity]"
                                        type="number"
                                        min="0"
                                        max="{{ $inspectionItem->quantity }}"
                                        step="1"
                                        value="{{ old('items.' . $inspectionItem->id . '.failed_quantity', 0) }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                                    >
                                </div>
                            @else
                                <input
                                    type="hidden"
                                    name="items[{{ $inspectionItem->id }}][failed_quantity]"
                                    value=""
                                >

                                <div class="md:col-span-3 text-sm text-gray-500">
                                    Quantity-based failure count is not applicable to
                                    <strong>{{ $inspectionItem->unit }}</strong> items.
                                </div>
                            @endif

                            <div class="md:col-span-5">
                                <label
                                    for="findings_{{ $inspectionItem->id }}"
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    Findings
                                </label>

                                <textarea
                                    id="findings_{{ $inspectionItem->id }}"
                                    name="items[{{ $inspectionItem->id }}][findings]"
                                    rows="2"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                                >{{ old('items.' . $inspectionItem->id . '.findings') }}</textarea>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-lg bg-white p-5 shadow ring-1 ring-gray-200">
            <div>
                <label for="findings" class="block text-sm font-medium text-gray-700">
                    Overall findings
                </label>

                <textarea
                    id="findings"
                    name="findings"
                    rows="4"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                >{{ old('findings') }}</textarea>
            </div>

            <div class="mt-4">
                <label for="correction_notes" class="block text-sm font-medium text-gray-700">
                    Correction notes
                </label>

                <textarea
                    id="correction_notes"
                    name="correction_notes"
                    rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                >{{ old('correction_notes') }}</textarea>
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center rounded-md bg-gray-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-800"
                >
                    Complete Quality Control
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
