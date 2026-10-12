@extends('layouts.public')

@php
    $title = 'Order Details';
@endphp

@section('content')
<div class="mx-auto max-w-6xl space-y-7 pb-10">

    {{-- Page heading --}}
    <div class="relative overflow-hidden rounded-2xl bg-slate-950 px-6 py-8 text-white shadow-lg sm:px-9 sm:py-10">
        <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-blue-500/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 right-1/3 h-48 w-48 rounded-full bg-indigo-400/10 blur-3xl"></div>

        <div class="relative">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                <span class="h-1.5 w-1.5 rounded-full bg-blue-400"></span>
                Order tracking
            </div>

            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <h1 class="break-words text-2xl font-bold tracking-tight sm:text-3xl lg:text-4xl">
                        {{ $order->order_number }}
                    </h1>
                    <p class="mt-2 text-sm text-slate-300 sm:text-base">
                        {{ $order->organization->name }}
                    </p>
                </div>

                <div class="shrink-0">
                    @if($order->status === 'approved')
                        <span class="oy-badge oy-badge-success">Approved</span>
                    @elseif($order->status === 'in_production')
                        <span class="oy-badge oy-badge-neutral">In Production</span>
                    @elseif($order->status === 'ready')
                        <span class="oy-badge oy-badge-success">Ready</span>
                    @elseif($order->status === 'delivered')
                        <span class="oy-badge oy-badge-success">Delivered</span>
                    @elseif($order->status === 'cancelled')
                        <span class="oy-badge oy-badge-danger">Cancelled</span>
                    @else
                        <span class="oy-badge oy-badge-warning">Pending</span>
                    @endif
                </div>
            </div>

            <p class="mt-5 max-w-2xl text-sm leading-6 text-slate-400">
                Track your order, review the expected delivery date, and confirm the items included in your order.
            </p>

            <div class="mt-7 grid gap-3 border-t border-white/10 pt-5 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Order placed</p>
                    <p class="mt-1.5 text-sm font-semibold text-white">
                        {{ $order->order_date->format('d M Y') }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Expected delivery</p>
                    <p class="mt-1.5 text-sm font-semibold text-white">
                        {{ $order->expected_delivery_date?->format('d M Y') ?? 'Date to be confirmed' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main information --}}
    <div class="grid items-start gap-5 lg:grid-cols-3">

        {{-- Order progress and delivery --}}
        <section class="oy-card overflow-hidden lg:col-span-2">
            <div class="oy-card-header border-b border-slate-100 bg-slate-50/70 px-5 py-5 sm:px-6">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/>
                            <circle cx="7.5" cy="18" r="1.5"/>
                            <circle cx="17.5" cy="18" r="1.5"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="oy-card-title">Order progress</h2>
                        <p class="oy-card-description mt-1">
                            Your latest order status and delivery expectations.
                        </p>
                    </div>
                </div>
            </div>

            <div class="oy-card-body p-5 sm:p-6">
                @if($order->status === 'cancelled')
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                        <div class="flex gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-red-900">This order has been cancelled</p>
                                <p class="mt-1 text-sm leading-5 text-red-800">
                                    Please contact your Oneyard Outfitters representative if you need further information.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    @php
                        $orderSteps = [
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'in_production' => 'In Production',
                            'ready' => 'Ready',
                            'delivered' => 'Delivered',
                        ];
                        $currentStep = array_search($order->status, array_keys($orderSteps), true);
                    @endphp

                    <div class="mb-7">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <span class="text-sm font-medium text-slate-500">Current stage</span>
                            <span class="text-sm font-bold text-slate-900">
                                {{ $orderSteps[$order->status] ?? 'Pending' }}
                            </span>
                        </div>

                        <div class="flex items-start">
                            @foreach($orderSteps as $stepKey => $stepLabel)
                                @php
                                    $stepIndex = array_search($stepKey, array_keys($orderSteps), true);
                                    $isComplete = $currentStep !== false && $stepIndex < $currentStep;
                                    $isCurrent = $stepKey === $order->status;
                                    $isReached = $currentStep !== false && $stepIndex <= $currentStep;
                                @endphp

                                <div class="flex min-w-0 flex-1 flex-col items-center text-center">
                                    <div class="flex w-full items-center">
                                        @if(!$loop->first)
                                            <div class="h-1 flex-1 rounded-full {{ $isReached ? 'bg-blue-600' : 'bg-slate-200' }}"></div>
                                        @endif

                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2
                                            {{ $isCurrent ? 'border-blue-700 bg-blue-600 text-white ring-4 ring-blue-100' : ($isComplete ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-400') }}">
                                            @if($isComplete)
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>
                                                </svg>
                                            @else
                                                <span class="text-xs font-bold">{{ $loop->iteration }}</span>
                                            @endif
                                        </div>

                                        @if(!$loop->last)
                                            <div class="h-1 flex-1 rounded-full {{ $isComplete ? 'bg-blue-600' : 'bg-slate-200' }}"></div>
                                        @endif
                                    </div>

                                    <span class="mt-3 max-w-full px-0.5 text-[10px] leading-4 sm:text-xs {{ $isCurrent ? 'font-bold text-blue-700' : ($isComplete ? 'font-medium text-slate-700' : 'text-slate-400') }}">
                                        {{ $stepLabel }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Expected delivery time</p>
                        <p class="mt-2 text-xl font-bold tracking-tight text-slate-900">
                            {{ $order->expected_delivery_days }}
                            <span class="text-sm font-medium text-slate-500">
                                {{ $order->expected_delivery_days === 1 ? 'day' : 'days' }}
                            </span>
                        </p>
                        <p class="mt-1 text-xs text-slate-500">Estimated time to delivery</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Delivery date</p>
                        <p class="mt-2 text-lg font-bold tracking-tight text-slate-900">
                            {{ $order->expected_delivery_date?->format('d M Y') ?? 'To be confirmed' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">Estimated arrival date</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Organization --}}
        <section class="oy-card overflow-hidden">
            <div class="oy-card-header border-b border-slate-100 bg-slate-50/70 px-5 py-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V5l7-3 7 3v16M9 9h1m-1 4h1m4-4h1m-1 4h1M9 21v-4h6v4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="oy-card-title">Organization</h2>
                        <p class="oy-card-description mt-1">Order account details</p>
                    </div>
                </div>
            </div>

            <div class="oy-card-body p-5">
                <p class="break-words text-base font-semibold leading-6 text-slate-900">
                    {{ $order->organization->name }}
                </p>
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Organization code</p>
                    <p class="oy-code mt-2 inline-flex max-w-full break-all rounded-lg bg-slate-50 px-3 py-2">
                        {{ $order->organization->organization_code }}
                    </p>
                </div>
            </div>
        </section>

        {{-- Primary contact --}}
        <section class="oy-card overflow-hidden lg:col-span-1">
            <div class="oy-card-header border-b border-slate-100 bg-slate-50/70 px-5 py-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="12" cy="8" r="4"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 21a8 8 0 0 1 16 0"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="oy-card-title">Primary Contact</h2>
                        <p class="oy-card-description mt-1">Contact for this order</p>
                    </div>
                </div>
            </div>

            <div class="oy-card-body p-5">
                <p class="text-base font-semibold text-slate-900">
                    {{ $recipient->contact->first_name }}
                    {{ $recipient->contact->last_name }}
                </p>

                @if($recipient->contact->position)
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $recipient->contact->position }}
                    </p>
                @endif

                <div class="mt-4 border-t border-slate-100 pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Email address</p>
                    <p class="mt-2 break-all text-sm font-medium text-slate-700">
                        {{ $recipient->email }}
                    </p>
                </div>
            </div>
        </section>

    </div>

    {{-- Order items --}}
    <section class="oy-card overflow-hidden">
        <div class="oy-card-header border-b border-slate-100 bg-slate-50/70 px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 7.8 7.5 4.3 7.5-4.3M12 12v8.5M8 5.3l8 4.5"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="oy-card-title">Order items</h2>
                        <p class="oy-card-description mt-1">A breakdown of the items recorded against this order.</p>
                    </div>
                </div>

                <span class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600">
                    {{ $order->items->count() }} {{ $order->items->count() === 1 ? 'item' : 'items' }}
                </span>
            </div>
        </div>

        <div class="oy-card-body p-0">
            @if($order->items->isEmpty())
                <div class="px-5 py-12 text-center sm:px-6">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/>
                        </svg>
                    </div>
                    <p class="mt-3 font-medium text-slate-800">No order items available</p>
                    <p class="mt-1 text-sm text-slate-500">There are currently no items recorded for this order.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="oy-table w-full min-w-[34rem]">
                        <thead>
                            <tr class="bg-white">
                                <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 sm:px-6">Item details</th>
                                <th class="whitespace-nowrap px-5 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Quantity</th>
                                <th class="whitespace-nowrap px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 sm:px-6">Unit</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($order->items as $item)
                                <tr class="transition-colors hover:bg-slate-50/80">
                                    <td class="px-5 py-4 sm:px-6">
                                        <p class="font-semibold text-slate-900">
                                            {{ $item->item_name }}
                                        </p>

                                        @if($item->description)
                                            <p class="mt-1 max-w-xl text-sm leading-5 text-slate-500">
                                                {{ $item->description }}
                                            </p>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-right font-semibold tabular-nums text-slate-800">
                                        {{ number_format((float) $item->quantity, 2) }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600 sm:px-6">
                                        {{ $item->unit }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    <div class="flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50/70 px-4 py-4 text-sm leading-6 text-blue-950">
        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <circle cx="12" cy="12" r="9"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11v5m0-8h.01"/>
        </svg>
        <p>
            <span class="font-semibold">Need assistance?</span>
            Contact your Oneyard Outfitters representative for questions about your order or delivery expectations.
        </p>
    </div>

</div>
@endsection
