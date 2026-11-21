@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-7 px-4 py-5 sm:py-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('stock.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-slate-950">
                ← Back to stock
            </a>
            <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                Processing {{ $processing->number }}
            </h1>
            <p class="mt-2 text-sm font-medium text-slate-600">{{ $processing->store->name }}</p>
        </div>
        <a href="{{ route('stock.processings.create', auth()->user()->isAdministrator() ? ['store_id' => $processing->store_id] : []) }}"
           class="oy-btn oy-btn-primary">+ Record Processing</a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2 sm:p-6 lg:grid-cols-3">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Processing date</p>
            <p class="mt-1 font-medium text-slate-900">{{ $processing->processed_on?->format('d M Y') ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Processed by</p>
            <p class="mt-1 font-medium text-slate-900">{{ $processing->processor?->name ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Raw material</p>
            <p class="mt-1 font-medium text-slate-900">{{ $processing->rawItem?->description ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Quantity consumed</p>
            <p class="mt-1 font-medium text-slate-900">{{ number_format((float) $processing->quantity_consumed, 3) }} kg</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Consumed cost</p>
            <p class="mt-1 font-medium text-slate-900">₦{{ number_format((float) $processing->consumed_cost, 2) }}</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Allocated output cost</p>
            <p class="mt-1 font-medium text-slate-900">₦{{ number_format((float) $processing->allocated_cost, 2) }}</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Cost difference</p>
            <p class="mt-1 font-medium text-slate-900">₦{{ number_format((float) $processing->cost_difference, 2) }}</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Recoverable waste</p>
            <p class="mt-1 font-medium text-slate-900">{{ number_format((float) $processing->recoverable_waste_kg, 3) }} kg</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Discarded waste</p>
            <p class="mt-1 font-medium text-slate-900">{{ number_format((float) $processing->discarded_waste_kg, 3) }} kg</p>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50/70 px-5 py-4 sm:px-6">
            <h2 class="font-semibold text-slate-900">Produced stock</h2>
        </div>
        @if ($processing->outputs->isEmpty())
            <p class="px-5 py-8 text-sm text-slate-600">No output lines were recorded.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Stock item</th>
                            <th class="px-5 py-3">Quantity produced</th>
                            <th class="px-5 py-3">Allocated cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($processing->outputs as $output)
                            <tr>
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $output->item?->description ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    {{ number_format((float) $output->quantity_produced, 3) }}
                                    {{ $output->item?->unit }}
                                </td>
                                <td class="px-5 py-3">₦{{ number_format((float) $output->line_cost, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($processing->notes)
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="font-semibold text-slate-900">Notes</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $processing->notes }}</p>
        </section>
    @endif
</div>
@endsection
