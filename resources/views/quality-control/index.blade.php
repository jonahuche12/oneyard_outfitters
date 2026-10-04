@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Quality Control</h1>
        <p class="mt-1 text-sm text-gray-600">
            Orders waiting for Quality Control inspection.
        </p>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-gray-200">
        <div class="divide-y divide-gray-200">
            @forelse($orders as $order)
                <div class="p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="font-semibold text-gray-900">
                                {{ $order->order_number }}
                            </h2>
                            <p class="text-sm text-gray-600">
                                {{ $order->organization->name }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ $order->items->count() }} order item(s)
                            </p>
                        </div>

                        <form method="POST" action="{{ route('quality-control.start', $order) }}">
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                            >
                                Start Inspection
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-gray-500">
                    No orders are currently waiting for Quality Control.
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-6">
        {{ $orders->links() }}
    </div>
</div>
@endsection
