@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Deliveries
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    Orders that have entered the Delivery workflow.
                </p>
            </div>
        </div>

        <div class="oy-card overflow-hidden">
            @if($deliveries->isEmpty())
                <div class="p-8 text-center text-sm text-slate-600">
                    No deliveries have been created yet.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Order
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Organization
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Delivery Date
                                </th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200 bg-white">
                            @foreach($deliveries as $delivery)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-slate-900">
                                        {{ $delivery->order->order_number }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-slate-700">
                                        {{ $delivery->order->organization->name }}
                                    </td>

                                    <td class="px-6 py-4">
                                        @if($delivery->status === \App\Models\Delivery::STATUS_CONFIRMED)
                                            <span class="oy-badge oy-badge-success">
                                                Delivered
                                            </span>
                                        @else
                                            <span class="oy-badge oy-badge-warning">
                                                Pending Delivery
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-sm text-slate-700">
                                        {{ $delivery->delivery_date?->format('d M Y') ?? '—' }}
                                    </td>

                                    <td class="px-6 py-4 text-right">
                                        <a
                                            href="{{ route('deliveries.show', $delivery) }}"
                                            class="oy-btn oy-btn-secondary"
                                        >
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 p-4">
                    {{ $deliveries->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
