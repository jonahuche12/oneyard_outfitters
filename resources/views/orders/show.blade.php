@extends('layouts.app')

@section('content')
<div class="oy-page">

    <div class="oy-page-header">
        <div class="oy-page-header-content">

            <div class="oy-page-title-row">
                <h1 class="oy-page-title">{{ $order->order_number }}</h1>

                @if($order->status === 'approved')
                    <span class="oy-badge oy-badge-success">Approved</span>
                @elseif($order->status === 'in_production')
                    <span class="oy-badge oy-badge-neutral">In Production</span>
                @elseif($order->status === \App\Models\Order::STATUS_CORRECTION_REQUIRED)
                    <span class="oy-badge oy-badge-danger">Correction Required</span>
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

            <p class="oy-page-description">
                {{ $order->organization->name }}
                · {{ $order->order_date->format('d M Y') }}
            </p>
        </div>

        <div class="oy-page-actions flex flex-wrap gap-2">
            @can('update', $order)
                <a href="{{ route('orders.edit', $order) }}" class="oy-btn oy-btn-primary">
                    Edit Delivery Estimate
                </a>
            @endcan

            <a href="{{ route('orders.index') }}" class="oy-btn oy-btn-secondary">
                All Orders
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Production coordination --}}
    <div class="oy-card oy-section">
        <div class="oy-card-header">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="oy-card-title">Order Coordinator</h2>

                    <p class="oy-card-description">
                        Assign or reassign the staff member responsible for coordinating this Order.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @can('createForOrder', [App\Models\Procurement::class, $order])
                        <a
                            href="{{ route('orders.procurements.create', $order) }}"
                            class="oy-btn oy-btn-primary shrink-0"
                        >
                            Create Procurement
                        </a>
                    @endcan

                    @can('assign', $order)
                        @if($coordinatorCandidates->isNotEmpty())
                            <button
                                type="button"
                                id="openCoordinatorModal"
                                class="oy-btn oy-btn-primary shrink-0"
                            >
                                {{ $order->currentAssignment
                                    ? 'Reassign Coordinator'
                                    : 'Assign Coordinator' }}
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
        </div>

            <div class="oy-card-body">
                @if($order->currentAssignment)
                    <div class="rounded-lg border border-amber-300 bg-amber-50 p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="oy-meta">Current Coordinator</span>

                            <span class="oy-badge oy-badge-success">
                                Current Coordinator
                            </span>
                        </div>

                        <div class="mt-2 font-medium text-slate-900">
                            {{ $order->currentAssignment->user->name }}
                        </div>

                        <div class="mt-1 text-sm text-slate-600">
                            {{ $order->currentAssignment->user->email }}
                        </div>

                        <div class="mt-2 text-xs text-slate-500">
                            Assigned
                            {{ $order->currentAssignment->assigned_at->format('d M Y, h:i A') }}
                        </div>
                    </div>
                @elseif($coordinatorCandidates->isEmpty())
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        No active staff member with production coordination permission is currently available.
                    </div>
                @else
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        No coordinator has been assigned to this Order yet.
                        Use <strong class="text-slate-900">Assign Coordinator</strong> to select one.
                    </div>
                @endif
            </div>
        </div>

        @if($coordinatorCandidates->isNotEmpty())
            @php
                $currentCoordinatorId = $order->currentAssignment?->user_id;

                $orderedCoordinatorCandidates = $coordinatorCandidates->sortByDesc(
                    fn ($candidate) => $currentCoordinatorId === $candidate->id ? 1 : 0
                );
            @endphp

            <div
                id="coordinatorModal"
                class="fixed inset-0 z-50 hidden overflow-y-auto"
                aria-labelledby="coordinatorModalTitle"
                aria-modal="true"
                role="dialog"
            >
                <div
                    id="coordinatorModalBackdrop"
                    class="fixed inset-0 bg-slate-900/60"
                ></div>

                <div class="relative flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-2xl rounded-xl bg-white shadow-xl">

                        {{-- Modal Header --}}
                        <div class="flex items-start justify-between border-b border-slate-200 p-6">
                            <div>
                                <h2
                                    id="coordinatorModalTitle"
                                    class="text-lg font-semibold text-slate-900"
                                >
                                    {{ $order->currentAssignment
                                        ? 'Reassign Order Coordinator'
                                        : 'Assign Order Coordinator' }}
                                </h2>

                                <p class="mt-1 text-sm text-slate-600">
                                    Select the active staff member who will coordinate this Order.
                                </p>
                            </div>

                            <button
                                type="button"
                                id="closeCoordinatorModal"
                                class="text-2xl leading-none text-slate-400 hover:text-slate-700"
                                aria-label="Close"
                            >
                                &times;
                            </button>
                        </div>

                        {{-- Current Coordinator Summary --}}
                        @if($order->currentAssignment)
                            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                                <div class="oy-meta">
                                    Current Order Coordinator
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-slate-900">
                                        {{ $order->currentAssignment->user->name }}
                                    </span>

                                    <span class="oy-badge oy-badge-success">
                                        Current Coordinator
                                    </span>
                                </div>

                                <div class="mt-1 text-sm text-slate-600">
                                    {{ $order->currentAssignment->user->email }}
                                </div>
                            </div>
                        @endif

                        {{-- Coordinator Selection --}}
                        <form
                            method="POST"
                            action="{{ route('orders.assign-coordinator', $order) }}"
                            class="oy-form"
                        >
                            @csrf

                            <div class="max-h-[60vh] space-y-3 overflow-y-auto p-6">
                                @foreach($orderedCoordinatorCandidates as $candidate)
                                    @php
                                        $isCurrentCoordinator =
                                            $currentCoordinatorId === $candidate->id;
                                    @endphp

                                    <label
                                        class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition
                                            {{ $isCurrentCoordinator
                                                ? 'border-amber-400 bg-amber-50 ring-1 ring-amber-300'
                                                : 'border-slate-200 hover:bg-slate-50' }}"
                                    >
                                        <input
                                            type="radio"
                                            name="user_id"
                                            value="{{ $candidate->id }}"
                                            class="mt-1"
                                            required
                                            @checked(
                                                old(
                                                    'user_id',
                                                    $currentCoordinatorId
                                                ) == $candidate->id
                                            )
                                        >

                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-medium text-slate-900">
                                                    {{ $candidate->name }}
                                                </span>

                                                @if($isCurrentCoordinator)
                                                    <span class="oy-badge oy-badge-success">
                                                        Current Coordinator
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="mt-1 text-sm text-slate-600">
                                                {{ $candidate->email }}
                                            </div>

                                            @if($isCurrentCoordinator)
                                                <div class="mt-2 text-xs font-medium text-amber-800">
                                                    Currently responsible for coordinating this Order.
                                                </div>
                                            @endif

                                            <div class="mt-2 text-xs text-slate-500">
                                                {{ $candidate->active_orders_count }}
                                                {{ $candidate->active_orders_count === 1 ? 'active order' : 'active orders' }}
                                            </div>
                                        </div>
                                    </label>
                                @endforeach

                                @error('user_id')
                                    <div class="oy-error mt-3">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Modal Footer --}}
                            <div class="flex justify-end gap-3 border-t border-slate-200 p-6">
                                <button
                                    type="button"
                                    id="cancelCoordinatorModal"
                                    class="oy-btn oy-btn-secondary"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    class="oy-btn oy-btn-primary"
                                >
                                    {{ $order->currentAssignment
                                        ? 'Reassign Coordinator'
                                        : 'Assign Coordinator' }}
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        @endif

@can('manageProductionPlan', $order)
    <div class="oy-card oy-section">
        <div class="oy-card-header">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="oy-card-title">Production Plan</h2>

                    <p class="oy-card-description">
                        Select the production activities required to complete this Order.
                        Quality Control and Delivery are handled as separate downstream stages.
                    </p>
                </div>

                @if(!$order->productionPlan && $productionActivities->isNotEmpty())
                    <button
                        type="button"
                        id="openProductionPlanModal"
                        class="oy-btn oy-btn-primary shrink-0"
                    >
                        Create Production Plan
                    </button>
                @endif
            </div>
        </div>

        <div class="oy-card-body">
            @if($order->productionPlan)
                @php
                    $planActivities = $order->productionPlan->activities;
                    $completedActivities = $planActivities
                        ->where('status', \App\Models\ProductionPlanActivity::STATUS_COMPLETED)
                        ->count();
                    $startedActivities = $planActivities
                        ->whereIn('status', [
                            \App\Models\ProductionPlanActivity::STATUS_STARTED,
                            \App\Models\ProductionPlanActivity::STATUS_COMPLETED,
                        ])
                        ->count();
                    $activityCount = $planActivities->count();
                    $progressPercent = $activityCount > 0
                        ? round(($startedActivities / $activityCount) * 100)
                        : 0;
                    $allProductionActivitiesCompleted = $activityCount > 0
                        && $completedActivities === $activityCount;
                @endphp

                <div>
                    <button
                        type="button"
                        id="toggleProductionPlan"
                        class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-4 text-left transition hover:border-slate-300 hover:bg-slate-100"
                        aria-expanded="false"
                        aria-controls="productionPlanActivities"
                    >
                        <span class="min-w-0">
                            <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="font-semibold text-slate-900">
                                    Production progress
                                </span>

                                <span class="text-sm font-medium text-slate-500">
                                    <span id="productionStartedCount">{{ $startedActivities }}</span>
                                    /
                                    <span id="productionTotalCount">{{ $activityCount }}</span>
                                    started
                                </span>
                            </span>

                            <span class="mt-1 block text-sm text-slate-500">
                                <span id="productionCompletedCount">{{ $completedActivities }}</span>
                                completed
                            </span>
                        </span>

                        <svg
                            id="productionPlanChevron"
                            class="h-5 w-5 shrink-0 text-slate-500 transition-transform duration-200"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                        <div class="mb-2 flex items-center justify-between text-xs">
                            <span class="font-medium uppercase tracking-wide text-slate-500">
                                Production started
                            </span>

                            <span
                                id="productionPlanProgressPercent"
                                class="font-semibold text-slate-700"
                            >
                                {{ $progressPercent }}%
                            </span>
                        </div>

                        <div
                            class="h-2 overflow-hidden rounded-full bg-slate-100"
                            role="progressbar"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-valuenow="{{ $progressPercent }}"
                        >
                            <div
                                id="productionPlanProgressBar"
                                class="h-full rounded-full bg-slate-800 transition-all duration-500"
                                style="width: {{ $progressPercent }}%"
                            ></div>
                        </div>
                    </div>

                    <div
                        id="productionPlanActivities"
                        class="mt-4 hidden space-y-3"
                    >
                        @foreach($planActivities as $planActivity)
                            @php
                                $isCompleted = $planActivity->status
                                    === \App\Models\ProductionPlanActivity::STATUS_COMPLETED;

                                $isStarted = $planActivity->status
                                    === \App\Models\ProductionPlanActivity::STATUS_STARTED;
                            @endphp

                            <div
                                class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-slate-300"
                                data-production-activity="{{ $planActivity->id }}"
                                data-status="{{ $planActivity->status }}"
                                data-completion-url="{{ route('orders.production-plan.activities.complete', [$order, $planActivity]) }}"
                                data-unmark-url="{{ route('orders.production-plan.activities.unmark', [$order, $planActivity]) }}"
                                data-activity-name="{{ $planActivity->activity->name }}"
                            >
                                <div class="flex items-start gap-4">
                                    <div
                                        class="production-activity-number flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold
                                            {{ $isCompleted
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : ($isStarted
                                                    ? 'bg-amber-100 text-amber-700'
                                                    : 'bg-slate-100 text-slate-600') }}"
                                    >
                                        {{ $loop->iteration }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-semibold text-slate-900">
                                                {{ $planActivity->activity->name }}
                                            </span>

                                            @if($planActivity->activity->is_required)
                                                <span class="oy-badge oy-badge-success">
                                                    Required
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mt-2">
                                            <span
                                                class="production-activity-status inline-flex rounded-full px-2.5 py-1 text-xs font-medium
                                                    {{ $isCompleted
                                                        ? 'bg-emerald-50 text-emerald-700'
                                                        : ($isStarted
                                                            ? 'bg-amber-50 text-amber-700'
                                                            : 'bg-slate-100 text-slate-600') }}"
                                            >
                                                {{ $isCompleted
                                                    ? 'Completed'
                                                    : ($isStarted ? 'In progress' : 'Pending') }}
                                            </span>
                                        </div>

                                        @if($planActivity->completed_at)
                                            <div
                                                class="production-activity-completed-at mt-2 text-xs text-slate-500"
                                            >
                                                Completed
                                                {{ $planActivity->completed_at->format('d M Y, h:i A') }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="production-activity-action shrink-0 pt-1">
                                        @if($isCompleted)
                                            @can('unmarkActivity', [$order, $planActivity])
                                                <button
                                                    type="button"
                                                    class="production-activity-unmark-toggle flex items-center gap-2"
                                                    data-unmark-url="{{ route('orders.production-plan.activities.unmark', [$order, $planActivity]) }}"
                                                    data-activity-name="{{ $planActivity->activity->name }}"
                                                    aria-label="Unmark {{ $planActivity->activity->name }}"
                                                >
                                                    <span class="text-sm font-medium text-emerald-700">
                                                        Completed
                                                    </span>

                                                    <span
                                                        class="relative inline-flex h-6 w-11 items-center rounded-full bg-emerald-600 transition"
                                                        aria-label="Activity completed"
                                                    >
                                                        <span class="inline-block h-4 w-4 translate-x-6 rounded-full bg-white shadow transition"></span>
                                                    </span>
                                                </button>
                                            @else
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-medium text-emerald-700">
                                                        Completed
                                                    </span>

                                                    <span
                                                        class="relative inline-flex h-6 w-11 items-center rounded-full bg-emerald-600"
                                                        aria-label="Activity completed"
                                                    >
                                                        <span class="inline-block h-4 w-4 translate-x-6 rounded-full bg-white shadow"></span>
                                                    </span>
                                                </div>
                                            @endcan
                                        @elseif($isStarted)
                                            <button
                                                type="button"
                                                class="production-activity-completion-toggle flex items-center gap-2"
                                                data-completion-url="{{ route('orders.production-plan.activities.complete', [$order, $planActivity]) }}"
                                                data-activity-name="{{ $planActivity->activity->name }}"
                                                aria-label="Mark {{ $planActivity->activity->name }} as completed"
                                            >
                                                <span class="text-sm font-medium text-slate-600">
                                                    Mark done
                                                </span>

                                                <span class="relative inline-flex h-6 w-11 items-center rounded-full bg-slate-300">
                                                    <span class="inline-block h-4 w-4 translate-x-1 rounded-full bg-white shadow"></span>
                                                </span>
                                            </button>
                                        @else
                                            <form
                                                method="POST"
                                                action="{{ route('orders.production-plan.activities.start', [$order, $planActivity]) }}"
                                                class="production-activity-start-form"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="oy-btn oy-btn-secondary"
                                                >
                                                    Start Activity
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($allProductionActivitiesCompleted)
                        <div
                            id="productionCoordinatorHandoff"
                            class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-5"
                        >
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-semibold text-emerald-900">
                                            Production activities completed
                                        </h3>

                                        <span class="oy-badge oy-badge-success">
                                            Ready for Coordinator Check
                                        </span>
                                    </div>

                                    <p class="mt-2 text-sm leading-6 text-emerald-800">
                                        All selected production activities have been completed.
                                        The Order Coordinator must check and confirm production
                                        before this Order can enter Quality Control.
                                    </p>
                                </div>

                                @can('confirmProductionComplete', $order)
                                    <button
                                        type="button"
                                        id="openProductionCoordinatorHandoff"
                                        class="oy-btn oy-btn-primary shrink-0"
                                    >
                                        Check &amp; Send to Quality Control
                                    </button>
                                @else
                                    <div class="shrink-0 rounded-lg border border-emerald-200 bg-white px-4 py-3 text-sm text-emerald-800">
                                        Waiting for the assigned Order Coordinator to confirm completion.
                                    </div>
                                @endcan
                            </div>
                        </div>
                    @endif
                </div>
            @elseif($productionActivities->isEmpty())
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    No active production activities are currently available.
                </div>
            @else
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    No production plan has been created for this Order yet.
                    Use <strong class="text-slate-900">Create Production Plan</strong>
                    to select the activities required for this Order.
                </div>
            @endif
        </div>
    </div>

    @if($order->productionPlan)
        @can('confirmProductionComplete', $order)
            <div
                id="productionCoordinatorHandoffModal"
                class="fixed inset-0 z-50 hidden overflow-y-auto"
                aria-labelledby="productionCoordinatorHandoffModalTitle"
                aria-modal="true"
                role="dialog"
            >
                <div
                    id="productionCoordinatorHandoffModalBackdrop"
                    class="fixed inset-0 bg-slate-900/60"
                ></div>

                <div class="relative flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">
                        <div class="flex items-start justify-between border-b border-slate-200 p-6">
                            <div>
                                <h2
                                    id="productionCoordinatorHandoffModalTitle"
                                    class="text-lg font-semibold text-slate-900"
                                >
                                    Confirm Production Complete
                                </h2>

                                <p class="mt-1 text-sm text-slate-600">
                                    Check the completed Production Plan before sending this Order to Quality Control.
                                </p>
                            </div>

                            <button
                                type="button"
                                id="closeProductionCoordinatorHandoffModal"
                                class="text-2xl leading-none text-slate-400 hover:text-slate-700"
                                aria-label="Close"
                            >
                                &times;
                            </button>
                        </div>

                        <form
                            id="productionCoordinatorHandoffForm"
                            method="POST"
                            action="{{ route('orders.production-plan.confirm-complete', $order) }}"
                            class="oy-form"
                        >
                            @csrf

                            <div class="space-y-5 p-6">
                                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                                    <div class="text-xs font-medium uppercase tracking-wide text-emerald-700">
                                        Coordinator Check
                                    </div>

                                    <div class="mt-1 font-medium text-emerald-900">
                                        {{ $completedActivities }} of {{ $activityCount }} production activities completed
                                    </div>

                                    <p class="mt-2 text-sm leading-6 text-emerald-800">
                                        Confirming this action will move the Order to
                                        <strong>Ready for Quality Control</strong>.
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="productionCoordinatorHandoffNotes"
                                        class="block text-sm font-medium text-slate-700"
                                    >
                                        Coordinator Note
                                    </label>

                                    <textarea
                                        id="productionCoordinatorHandoffNotes"
                                        name="notes"
                                        rows="4"
                                        maxlength="5000"
                                        class="mt-2 block w-full rounded-lg border border-slate-300 p-3 text-sm text-slate-700"
                                        placeholder="Optional: record any final production check note."
                                    ></textarea>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 border-t border-slate-200 p-6">
                                <button
                                    type="button"
                                    id="cancelProductionCoordinatorHandoffModal"
                                    class="oy-btn oy-btn-secondary"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    id="confirmProductionCoordinatorHandoff"
                                    class="oy-btn oy-btn-primary"
                                >
                                    Confirm &amp; Send to Quality Control
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan

        <div
            id="productionUnmarkModal"
            class="fixed inset-0 z-50 hidden overflow-y-auto"
            aria-labelledby="productionUnmarkModalTitle"
            aria-modal="true"
            role="dialog"
        >
            <div
                id="productionUnmarkModalBackdrop"
                class="fixed inset-0 bg-slate-900/60"
            ></div>

            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">
                    <div class="flex items-start justify-between border-b border-slate-200 p-6">
                        <div>
                            <h2
                                id="productionUnmarkModalTitle"
                                class="text-lg font-semibold text-slate-900"
                            >
                                Unmark Activity
                            </h2>

                            <p class="mt-1 text-sm text-slate-600">
                                Return this completed activity to in progress.
                            </p>
                        </div>

                        <button
                            type="button"
                            id="closeProductionUnmarkModal"
                            class="text-2xl leading-none text-slate-400 hover:text-slate-700"
                            aria-label="Close"
                        >
                            &times;
                        </button>
                    </div>

                    <form
                        id="productionUnmarkForm"
                        method="POST"
                        action=""
                        class="oy-form"
                    >
                        @csrf

                        <div class="space-y-5 p-6">
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                <div class="text-xs font-medium uppercase tracking-wide text-amber-700">
                                    Activity
                                </div>

                                <div
                                    id="productionUnmarkActivityName"
                                    class="mt-1 font-medium text-slate-900"
                                ></div>

                                <p class="mt-2 text-sm text-amber-800">
                                    This will return the activity to <strong>In progress</strong>.
                                    Existing completion evidence will be preserved.
                                </p>
                            </div>

                            <div>
                                <label
                                    for="productionUnmarkNotes"
                                    class="block text-sm font-medium text-slate-700"
                                >
                                    Reason / Note
                                </label>

                                <textarea
                                    id="productionUnmarkNotes"
                                    name="notes"
                                    rows="4"
                                    maxlength="5000"
                                    class="mt-2 block w-full rounded-lg border border-slate-300 p-3 text-sm text-slate-700"
                                    placeholder="Optional: explain why this activity is being returned to in progress."
                                ></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-slate-200 p-6">
                            <button
                                type="button"
                                id="cancelProductionUnmarkModal"
                                class="oy-btn oy-btn-secondary"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                id="confirmProductionUnmark"
                                class="oy-btn oy-btn-primary"
                            >
                                Confirm Unmark
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div
            id="productionCompletionModal"
            class="fixed inset-0 z-50 hidden overflow-y-auto"
            aria-labelledby="productionCompletionModalTitle"
            aria-modal="true"
            role="dialog"
        >
            <div
                id="productionCompletionModalBackdrop"
                class="fixed inset-0 bg-slate-900/60"
            ></div>

            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">
                    <div class="flex items-start justify-between border-b border-slate-200 p-6">
                        <div>
                            <h2
                                id="productionCompletionModalTitle"
                                class="text-lg font-semibold text-slate-900"
                            >
                                Complete Activity
                            </h2>

                            <p class="mt-1 text-sm text-slate-600">
                                Confirm that this activity has been completed and provide evidence.
                            </p>
                        </div>

                        <button
                            type="button"
                            id="closeProductionCompletionModal"
                            class="text-2xl leading-none text-slate-400 hover:text-slate-700"
                            aria-label="Close"
                        >
                            &times;
                        </button>
                    </div>

                    <form
                        id="productionCompletionForm"
                        method="POST"
                        action=""
                        enctype="multipart/form-data"
                        class="oy-form"
                    >
                        @csrf

                        <div class="space-y-5 p-6">
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                <div class="text-xs font-medium uppercase tracking-wide text-amber-700">
                                    Activity
                                </div>

                                <div
                                    id="productionCompletionActivityName"
                                    class="mt-1 font-medium text-slate-900"
                                ></div>

                                <p class="mt-2 text-sm text-amber-800">
                                    Has this activity been completed?
                                </p>
                            </div>

                            <div>
                                <label
                                    for="productionCompletionEvidence"
                                    class="block text-sm font-medium text-slate-700"
                                >
                                    Evidence
                                </label>

                                <input
                                    type="file"
                                    id="productionCompletionEvidence"
                                    name="evidence"
                                    required
                                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                                    class="mt-2 block w-full rounded-lg border border-slate-300 bg-white p-2 text-sm text-slate-700"
                                >

                                <p class="mt-1 text-xs text-slate-500">
                                    JPG, JPEG, PNG, WEBP or PDF. Maximum 10 MB.
                                </p>

                                @error('evidence')
                                    <div class="oy-error mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="productionCompletionNotes"
                                    class="block text-sm font-medium text-slate-700"
                                >
                                    Note
                                </label>

                                <textarea
                                    id="productionCompletionNotes"
                                    name="notes"
                                    rows="4"
                                    maxlength="5000"
                                    class="mt-2 block w-full rounded-lg border border-slate-300 p-3 text-sm text-slate-700"
                                    placeholder="Add a note about the completed activity (optional)."
                                >{{ old('notes') }}</textarea>

                                @error('notes')
                                    <div class="oy-error mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-slate-200 p-6">
                            <button
                                type="button"
                                id="cancelProductionCompletionModal"
                                class="oy-btn oy-btn-secondary"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="oy-btn oy-btn-primary"
                            >
                                Confirm Completion
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if(!$order->productionPlan && $productionActivities->isNotEmpty())
        <div
            id="productionPlanModal"
            class="fixed inset-0 z-50 hidden overflow-y-auto"
            aria-labelledby="productionPlanModalTitle"
            aria-modal="true"
            role="dialog"
        >
            <div
                id="productionPlanModalBackdrop"
                class="fixed inset-0 bg-slate-900/60"
            ></div>

            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl rounded-xl bg-white shadow-xl">

                    {{-- Modal Header --}}
                    <div class="flex items-start justify-between border-b border-slate-200 p-6">
                        <div>
                            <h2
                                id="productionPlanModalTitle"
                                class="text-lg font-semibold text-slate-900"
                            >
                                Create Production Plan
                            </h2>

                            <p class="mt-1 text-sm text-slate-600">
                                Select every activity required to complete this Order.
                            </p>
                        </div>

                        <button
                            type="button"
                            id="closeProductionPlanModal"
                            class="text-2xl leading-none text-slate-400 hover:text-slate-700"
                            aria-label="Close"
                        >
                            &times;
                        </button>
                    </div>

                    {{-- Activity Selection --}}
                    <form
                        method="POST"
                        action="{{ route('orders.production-plan.store', $order) }}"
                        class="oy-form"
                    >
                        @csrf

                        <div class="max-h-[60vh] space-y-3 overflow-y-auto p-6">
                            @php
                                $oldActivityIds = old('activity_ids', []);
                                $oldActivityIds = is_array($oldActivityIds)
                                    ? array_map('intval', $oldActivityIds)
                                    : [];
                            @endphp

                            @foreach($productionActivities as $activity)
                                @php
                                    $isRequired = $activity->is_required;
                                    $isSelected = $isRequired
                                        || in_array($activity->id, $oldActivityIds, true);
                                @endphp

                                <label
                                    class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition
                                        {{ $isRequired
                                            ? 'border-amber-400 bg-amber-50 ring-1 ring-amber-300'
                                            : 'border-slate-200 hover:bg-slate-50' }}"
                                >
                                    <input
                                        type="checkbox"
                                        name="activity_ids[]"
                                        value="{{ $activity->id }}"
                                        class="mt-1"
                                        @checked($isSelected)
                                        @disabled($isRequired)
                                    >

                                    @if($isRequired)
                                        <input
                                            type="hidden"
                                            name="activity_ids[]"
                                            value="{{ $activity->id }}"
                                        >
                                    @endif

                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="font-medium text-slate-900">
                                                {{ $activity->name }}
                                            </span>

                                            @if($isRequired)
                                                <span class="oy-badge oy-badge-success">
                                                    Required
                                                </span>
                                            @endif
                                        </span>

                                        @if($activity->description)
                                            <span class="mt-1 block text-sm text-slate-600">
                                                {{ $activity->description }}
                                            </span>
                                        @endif

                                        @if($isRequired)
                                            <span class="mt-2 block text-xs font-medium text-amber-800">
                                                This activity is mandatory for every Order.
                                            </span>
                                        @else
                                            <span class="mt-2 block text-xs text-slate-500">
                                                Select this activity if it applies to this Order.
                                            </span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach

                            @error('activity_ids')
                                <div class="oy-error mt-3">
                                    {{ $message }}
                                </div>
                            @enderror

                            @error('activity_ids.*')
                                <div class="oy-error mt-3">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Modal Footer --}}
                        <div class="flex justify-end gap-3 border-t border-slate-200 p-6">
                            <button
                                type="button"
                                id="cancelProductionPlanModal"
                                class="oy-btn oy-btn-secondary"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="oy-btn oy-btn-primary"
                            >
                                Create Production Plan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endcan


@can('returnToProductionForCorrection', $order)
    <div class="oy-card oy-section">
        <div class="oy-card-body">
            <div class="rounded-xl border border-red-200 bg-red-50 p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-red-900">
                                Quality Control Correction Required
                            </h2>

                            <span class="oy-badge oy-badge-danger">
                                QC Failed
                            </span>
                        </div>

                        <p class="mt-2 text-sm leading-6 text-red-800">
                            Quality Control has identified corrections that must be completed
                            before this Order can be inspected again. Return the Order to
                            Production to reopen its production activities for correction.
                        </p>
                    </div>

                    <button
                        type="button"
                        id="openQualityControlCorrectionModal"
                        class="oy-btn oy-btn-primary shrink-0"
                    >
                        Return to Production
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div
        id="qualityControlCorrectionModal"
        class="fixed inset-0 z-50 hidden overflow-y-auto"
        aria-labelledby="qualityControlCorrectionModalTitle"
        aria-modal="true"
        role="dialog"
    >
        <div
            id="qualityControlCorrectionModalBackdrop"
            class="fixed inset-0 bg-slate-900/60"
        ></div>

        <div class="relative flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">
                <div class="flex items-start justify-between border-b border-slate-200 p-6">
                    <div>
                        <h2
                            id="qualityControlCorrectionModalTitle"
                            class="text-lg font-semibold text-slate-900"
                        >
                            Return to Production for Correction
                        </h2>

                        <p class="mt-1 text-sm text-slate-600">
                            Confirm that this Order should be reopened for Production correction.
                        </p>
                    </div>

                    <button
                        type="button"
                        id="closeQualityControlCorrectionModal"
                        class="text-2xl leading-none text-slate-400 hover:text-slate-700"
                        aria-label="Close"
                    >
                        &times;
                    </button>
                </div>

                <form
                    id="qualityControlCorrectionForm"
                    method="POST"
                    action="{{ route('orders.production.correction', $order) }}"
                    class="oy-form"
                >
                    @csrf

                    <div class="space-y-5 p-6">
                        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                            <div class="text-xs font-medium uppercase tracking-wide text-red-700">
                                Coordinator Confirmation
                            </div>

                            <p class="mt-2 text-sm leading-6 text-red-800">
                                The existing Production activities will be reopened so the
                                required corrections can be completed. The failed Quality
                                Control inspection will remain in the Order's inspection history.
                            </p>
                        </div>

                        <div>
                            <label
                                for="qualityControlCorrectionNotes"
                                class="block text-sm font-medium text-slate-700"
                            >
                                Coordinator Note
                            </label>

                            <textarea
                                id="qualityControlCorrectionNotes"
                                name="notes"
                                rows="4"
                                maxlength="5000"
                                class="mt-2 block w-full rounded-lg border border-slate-300 p-3 text-sm text-slate-700"
                                placeholder="Add a brief note about the correction work required or the action being taken."
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-200 p-6">
                        <button
                            type="button"
                            id="cancelQualityControlCorrectionModal"
                            class="oy-btn oy-btn-secondary"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            id="confirmQualityControlCorrection"
                            class="oy-btn oy-btn-primary"
                        >
                            Confirm &amp; Return to Production
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan

@can('resubmitCorrectionToQualityControl', $order)
    <div class="oy-card oy-section">
        <div class="oy-card-body">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-amber-900">
                                Quality Control Correction
                            </h2>

                            <span class="oy-badge oy-badge-warning">
                                Correction Completed
                            </span>
                        </div>

                        <p class="mt-2 text-sm leading-6 text-amber-800">
                            Quality Control previously failed this Order and returned it
                            to Production for correction. Confirm that all required
                            corrections have been completed before sending it back
                            for a new Quality Control inspection.
                        </p>
                    </div>

                    <button
                        type="button"
                        id="openQualityControlResubmissionModal"
                        class="oy-btn oy-btn-primary shrink-0"
                    >
                        Resubmit for Quality Control
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div
        id="qualityControlResubmissionModal"
        class="fixed inset-0 z-50 hidden overflow-y-auto"
        aria-labelledby="qualityControlResubmissionModalTitle"
        aria-modal="true"
        role="dialog"
    >
        <div
            id="qualityControlResubmissionModalBackdrop"
            class="fixed inset-0 bg-slate-900/60"
        ></div>

        <div class="relative flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">
                <div class="flex items-start justify-between border-b border-slate-200 p-6">
                    <div>
                        <h2
                            id="qualityControlResubmissionModalTitle"
                            class="text-lg font-semibold text-slate-900"
                        >
                            Resubmit for Quality Control
                        </h2>

                        <p class="mt-1 text-sm text-slate-600">
                            Confirm that the Quality Control corrections have been completed.
                        </p>
                    </div>

                    <button
                        type="button"
                        id="closeQualityControlResubmissionModal"
                        class="text-2xl leading-none text-slate-400 hover:text-slate-700"
                        aria-label="Close"
                    >
                        &times;
                    </button>
                </div>

                <form
                    id="qualityControlResubmissionForm"
                    method="POST"
                    action="{{ route('orders.production.correction.resubmit-quality-control', $order) }}"
                    class="oy-form"
                >
                    @csrf

                    <div class="space-y-5 p-6">
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <div class="text-xs font-medium uppercase tracking-wide text-amber-700">
                                Coordinator Confirmation
                            </div>

                            <p class="mt-2 text-sm leading-6 text-amber-800">
                                By continuing, you confirm that the required Production
                                corrections have been completed and the Order is ready
                                for a new Quality Control inspection.
                            </p>
                        </div>

                        <label class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                name="correction_confirmed"
                                value="1"
                                required
                                class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                            >

                            <span class="text-sm leading-6 text-slate-700">
                                I confirm that all required corrections have been completed
                                and checked.
                            </span>
                        </label>

                        <div>
                            <label
                                for="qualityControlResubmissionNotes"
                                class="block text-sm font-medium text-slate-700"
                            >
                                Coordinator Note
                            </label>

                            <textarea
                                id="qualityControlResubmissionNotes"
                                name="notes"
                                rows="4"
                                maxlength="5000"
                                required
                                class="mt-2 block w-full rounded-lg border border-slate-300 p-3 text-sm text-slate-700"
                                placeholder="Briefly describe the corrections completed before resubmission."
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-200 p-6">
                        <button
                            type="button"
                            id="cancelQualityControlResubmissionModal"
                            class="oy-btn oy-btn-secondary"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            id="confirmQualityControlResubmission"
                            class="oy-btn oy-btn-primary"
                        >
                            Confirm &amp; Resubmit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan


@if($order->status === \App\Models\Order::STATUS_READY)
    <div class="oy-card oy-section">
        <div class="oy-card-body">
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-blue-900">
                                Ready for Delivery
                            </h2>

                            <span class="oy-badge oy-badge-success">
                                Quality Control Passed
                            </span>
                        </div>

                        <p class="mt-2 text-sm leading-6 text-blue-800">
                            Quality Control has passed this Order. The Order can now
                            be moved into the Delivery workflow.
                        </p>
                    </div>

                    @can('proceedToDelivery', $order)
                        <form
                            method="POST"
                            action="{{ route('orders.delivery.store', $order) }}"
                            class="shrink-0"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="oy-btn oy-btn-primary"
                            >
                                Proceed to Delivery
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endif

@if($order->delivery)
    <div class="oy-card oy-section">
        <div class="oy-card-body">

            @if($order->delivery->status === \App\Models\Delivery::STATUS_CONFIRMED)

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold text-emerald-900">
                                    Order Delivered
                                </h2>

                                <span class="oy-badge oy-badge-success">
                                    Balance Collected
                                </span>

                                <span class="oy-badge oy-badge-success">
                                    Delivery Confirmed
                                </span>
                            </div>

                            <p class="mt-2 text-sm leading-6 text-emerald-800">
                                The outstanding balance has been collected and
                                the Order has been delivered successfully.
                            </p>
                        </div>

                        <a
                            href="{{ route('deliveries.show', $order->delivery) }}"
                            class="oy-btn oy-btn-secondary shrink-0"
                        >
                            View Delivery
                        </a>
                    </div>
                </div>

            @elseif($order->delivery->activated_at)

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold text-amber-900">
                                    Delivery Activated
                                </h2>

                                @php
                                    $deliveryPaymentArrangement =
                                        $order->delivery->payment_arrangement;

                                    $deliveryTotalPaid = (float) $order->quotation->payments
                                        ->where('status', 'completed')
                                        ->sum(fn ($payment) => (float) $payment->amount);

                                    $deliveryBalanceDue = max(
                                        0,
                                        round(
                                            (float) $order->total - $deliveryTotalPaid,
                                            2
                                        )
                                    );
                                @endphp

                                @if(
                                    $deliveryPaymentArrangement ===
                                    \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAY_NOW
                                    && $deliveryBalanceDue <= 0.01
                                )
                                    <span class="oy-badge oy-badge-success">
                                        Payment Complete · Awaiting Delivery
                                    </span>
                                @elseif(
                                    $deliveryPaymentArrangement ===
                                    \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAY_ON_DELIVERY
                                )
                                    <span class="oy-badge oy-badge-warning">
                                        Awaiting Payment & Delivery
                                    </span>
                                @else
                                    <span class="oy-badge oy-badge-warning">
                                        Balance Due & Delivery Pending
                                    </span>
                                @endif
                            </div>

                            <p class="mt-2 text-sm leading-6 text-amber-800">
                                The customer has activated the Delivery.

                                @if(
                                    $deliveryPaymentArrangement ===
                                    \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAY_NOW
                                    && $deliveryBalanceDue <= 0.01
                                )
                                    Payment has been completed. Staff must complete
                                    the physical delivery.
                                @else
                                    Any outstanding balance will be settled when
                                    Delivery is completed.
                                @endif
                            </p>
                        </div>

                        @can('confirm', $order->delivery)
                            <button
                                type="button"
                                class="oy-btn oy-btn-primary shrink-0"
                                data-open-complete-delivery
                            >
                                Complete Delivery
                            </button>
                        @else
                            <a
                                href="{{ route('deliveries.show', $order->delivery) }}"
                                class="oy-btn oy-btn-secondary shrink-0"
                            >
                                View Delivery
                            </a>
                        @endcan
                    </div>
                </div>

                @can('confirm', $order->delivery)
                    <div
                        id="complete-delivery-modal"
                        class="fixed inset-0 z-50 hidden"
                        aria-labelledby="complete-delivery-modal-title"
                        aria-modal="true"
                        role="dialog"
                    >
                        <div
                            class="absolute inset-0 bg-slate-950/60"
                            data-close-complete-delivery
                        ></div>

                        <div class="relative flex min-h-full items-center justify-center p-4">
                            <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">

                                <div class="border-b border-slate-200 px-6 py-5">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h2
                                                id="complete-delivery-modal-title"
                                                class="text-lg font-semibold text-slate-900"
                                            >
                                                Confirm Delivery Completion
                                            </h2>

                                            <p class="mt-1 text-sm leading-6 text-slate-600">
                                                Confirm that the outstanding balance has been received
                                                and the Order has been physically delivered.
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            class="text-2xl leading-none text-slate-400 hover:text-slate-700"
                                            data-close-complete-delivery
                                            aria-label="Close"
                                        >
                                            &times;
                                        </button>
                                    </div>
                                </div>

                                <form
                                    method="POST"
                                    action="{{ route('deliveries.confirm', $order->delivery) }}"
                                >
                                    @csrf

                                    <div class="space-y-5 px-6 py-6">

                                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                                            <input
                                                type="checkbox"
                                                name="confirmation"
                                                value="1"
                                                required
                                                class="mt-1 rounded border-slate-300"
                                            >

                                            <span class="text-sm leading-6 text-amber-900">
                                                I confirm that the outstanding balance has been
                                                received and the Order has been delivered to
                                                the customer.
                                            </span>
                                        </label>

                                        <div>
                                            <label
                                                for="order-delivery-completion-notes"
                                                class="block text-sm font-medium text-slate-700"
                                            >
                                                Completion Notes
                                            </label>

                                            <textarea
                                                id="order-delivery-completion-notes"
                                                name="notes"
                                                rows="4"
                                                maxlength="5000"
                                                class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                                placeholder="Record any relevant payment or delivery details..."
                                            >{{ old('notes') }}</textarea>
                                        </div>
                                    </div>

                                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 px-6 py-5 sm:flex-row sm:justify-end">
                                        <button
                                            type="button"
                                            class="oy-btn oy-btn-secondary"
                                            data-close-complete-delivery
                                        >
                                            Cancel
                                        </button>

                                        <button
                                            type="submit"
                                            class="oy-btn oy-btn-primary"
                                        >
                                            Confirm & Close Order
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const modal = document.getElementById('complete-delivery-modal');
                            const openButton = document.querySelector(
                                '[data-open-complete-delivery]'
                            );
                            const closeButtons = document.querySelectorAll(
                                '[data-close-complete-delivery]'
                            );

                            if (!modal || !openButton) {
                                return;
                            }

                            const openModal = function () {
                                modal.classList.remove('hidden');
                                document.body.classList.add('overflow-hidden');
                            };

                            const closeModal = function () {
                                modal.classList.add('hidden');
                                document.body.classList.remove('overflow-hidden');
                            };

                            openButton.addEventListener('click', openModal);

                            closeButtons.forEach(function (button) {
                                button.addEventListener('click', closeModal);
                            });

                            document.addEventListener('keydown', function (event) {
                                if (
                                    event.key === 'Escape' &&
                                    !modal.classList.contains('hidden')
                                ) {
                                    closeModal();
                                }
                            });
                        });
                    </script>
                @endcan

            @else

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="font-semibold text-slate-900">
                                Delivery Pending Customer Activation
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                This Order has entered the Delivery workflow
                                and is awaiting customer activation.
                            </p>
                        </div>

                        <a
                            href="{{ route('deliveries.show', $order->delivery) }}"
                            class="oy-btn oy-btn-secondary shrink-0"
                        >
                            View Delivery
                        </a>
                    </div>
                </div>

            @endif

        </div>
    </div>
@endif

@if($order->status === \App\Models\Order::STATUS_READY_FOR_QUALITY_CONTROL)
    <div class="oy-card oy-section">
        <div class="oy-card-body">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-emerald-900">
                                Ready for Quality Control
                            </h2>

                            <span class="oy-badge oy-badge-success">
                                Production Complete
                            </span>
                        </div>

                        <p class="mt-2 text-sm leading-6 text-emerald-800">
                            The Order Coordinator has confirmed that all production activities
                            are complete. The Order is now awaiting Quality Control.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@can('approve', $order)
        <div class="oy-card oy-section">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Approve & Notify Organization</h2>
                <p class="oy-card-description">
                    Select the organization contacts who should receive the approved-order notification and tracking link.
                </p>
            </div>

            <div class="oy-card-body">
                @php
                    $organizationContacts = $order->organization
                        ->contacts()
                        ->where('is_active', true)
                        ->orderByDesc('is_primary')
                        ->orderBy('first_name')
                        ->orderBy('last_name')
                        ->get();
                @endphp

                @if($organizationContacts->isEmpty())
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        This organization has no active contacts available for notification.
                        Add an active contact before approving this order.
                    </div>
                @else
                    <form method="POST" action="{{ route('orders.approve', $order) }}">
                        @csrf

                        <div class="space-y-3">
                            @foreach($organizationContacts as $contact)
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                                    <input
                                        type="checkbox"
                                        name="contact_ids[]"
                                        value="{{ $contact->id }}"
                                        class="mt-1"
                                        @checked(in_array($contact->id, old('contact_ids', [])))
                                    >

                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="font-medium text-slate-900">
                                                {{ $contact->first_name }}
                                                {{ $contact->middle_name }}
                                                {{ $contact->last_name }}
                                            </span>

                                            @if($contact->is_primary)
                                                <span class="oy-badge oy-badge-success">
                                                    Primary Contact
                                                </span>
                                            @endif
                                        </span>

                                        <span class="mt-1 block text-sm text-slate-600">
                                            {{ $contact->email }}
                                        </span>

                                        @if($contact->position)
                                            <span class="mt-1 block text-xs text-slate-500">
                                                {{ $contact->position }}
                                            </span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @error('contact_ids')
                            <div class="oy-error mt-3">{{ $message }}</div>
                        @enderror

                        @error('contact_ids.*')
                            <div class="oy-error mt-3">{{ $message }}</div>
                        @enderror

                        <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                            <strong class="text-slate-900">Super Admin:</strong>
                            Active Super Admin users will automatically receive an internal approval notification.
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="oy-btn oy-btn-primary">
                                Approve Order & Notify Selected Contacts
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endcan

    @can('resendApproval', $order)
        <div class="oy-card oy-section">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Resend Approval Email</h2>
                <p class="oy-card-description">
                    Resend the approved-order notification and existing tracking link to selected organization contacts.
                </p>
            </div>

            <div class="oy-card-body">
                @php
                    $resendContacts = $order->organization
                        ->contacts()
                        ->where('is_active', true)
                        ->orderByDesc('is_primary')
                        ->orderBy('first_name')
                        ->orderBy('last_name')
                        ->get();
                @endphp

                @if($resendContacts->isEmpty())
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        This organization has no active contacts available for notification.
                    </div>
                @else
                    <form method="POST" action="{{ route('orders.resend-approval', $order) }}">
                        @csrf

                        <div class="space-y-3">
                            @foreach($resendContacts as $contact)
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                                    <input
                                        type="checkbox"
                                        name="contact_ids[]"
                                        value="{{ $contact->id }}"
                                        class="mt-1"
                                        @checked(in_array($contact->id, old('contact_ids', [])))
                                    >

                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="font-medium text-slate-900">
                                                {{ $contact->first_name }}
                                                {{ $contact->middle_name }}
                                                {{ $contact->last_name }}
                                            </span>

                                            @if($contact->is_primary)
                                                <span class="oy-badge oy-badge-success">
                                                    Primary Contact
                                                </span>
                                            @endif
                                        </span>

                                        <span class="mt-1 block text-sm text-slate-600">
                                            {{ $contact->email }}
                                        </span>

                                        @if($contact->position)
                                            <span class="mt-1 block text-xs text-slate-500">
                                                {{ $contact->position }}
                                            </span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @error('contact_ids')
                            <div class="oy-error mt-3">{{ $message }}</div>
                        @enderror

                        @error('contact_ids.*')
                            <div class="oy-error mt-3">{{ $message }}</div>
                        @enderror

                        <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                            The existing secure tracking link will be reused for contacts who have already been notified.
                            No new tracking link will be generated.
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="oy-btn oy-btn-primary">
                                Resend Approval Email
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endcan

    <div class="grid gap-6 lg:grid-cols-3">

        <div class="oy-card lg:col-span-2">

            <div class="oy-card-header">
                <h2 class="oy-card-title">Order Details</h2>
                <p class="oy-card-description">
                    The fulfillment record created from the accepted quotation.
                </p>
            </div>

            <div class="oy-card-body">
                <dl class="grid gap-5 sm:grid-cols-2">

                    <div>
                        <dt class="oy-meta">Organization</dt>
                        <dd class="mt-1 font-medium text-slate-900">
                            {{ $order->organization->name }}
                        </dd>
                        <dd class="oy-code mt-1">
                            {{ $order->organization->organization_code }}
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Contact</dt>
                        <dd class="mt-1 text-sm text-slate-700">
                            @if($order->contact)
                                {{ $order->contact->first_name }}
                                {{ $order->contact->last_name }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Quotation</dt>
                        <dd class="mt-1 text-sm text-slate-700">
                            {{ $order->quotation->quotation_number }}
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Order Date</dt>
                        <dd class="mt-1 text-sm text-slate-700">
                            {{ $order->order_date->format('d M Y') }}
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Expected Delivery</dt>
                        <dd class="mt-1">
                            @if($order->expected_delivery_days < 5)
                                <span class="oy-badge oy-badge-warning">
                                    {{ $order->expected_delivery_days }} days to delivery
                                </span>
                            @else
                                <span class="oy-badge oy-badge-neutral">
                                    {{ $order->expected_delivery_days }} days to delivery
                                </span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Expected Delivery Date</dt>
                        <dd class="mt-1 text-sm text-slate-700">
                            {{ $order->expected_delivery_date?->format('d M Y') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="oy-meta">Status</dt>
                        <dd class="mt-1 text-sm text-slate-700">
                            {{ ucwords(str_replace('_', ' ', $order->status)) }}
                        </dd>
                    </div>

                </dl>
            </div>

        </div>

        <div class="oy-card">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Commercial Summary</h2>
            </div>

            <div class="oy-card-body">
                <dl class="space-y-3 text-sm">

                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Subtotal</dt>
                        <dd class="font-medium text-slate-900">
                            ₦{{ number_format((float) $order->subtotal, 2) }}
                        </dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Discount</dt>
                        <dd class="font-medium text-slate-900">
                            ₦{{ number_format((float) $order->discount, 2) }}
                        </dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Additional Charges</dt>
                        <dd class="font-medium text-slate-900">
                            ₦{{ number_format((float) $order->additional_charges, 2) }}
                        </dd>
                    </div>

                    <div class="border-t border-slate-200 pt-3">
                        <div class="flex justify-between gap-4">
                            <dt class="font-semibold text-slate-900">Total</dt>
                            <dd class="text-lg font-bold text-slate-900">
                                ₦{{ number_format((float) $order->total, 2) }}
                            </dd>
                        </div>
                    </div>

                </dl>
            </div>
        </div>

    </div>

    <div class="oy-card oy-section">

        <div class="oy-card-header">
            <h2 class="oy-card-title">Order Items</h2>
            <p class="oy-card-description">
                These are the item snapshots transferred from the quotation when the order was created.
            </p>
        </div>

        <div class="oy-card-body">

            @if($order->items->isEmpty())

                <div class="oy-empty-state">
                    <div class="oy-empty-state-title">No order items</div>
                    <div class="oy-empty-state-description">
                        This order contains no item records.
                    </div>
                </div>

            @else

                <div class="overflow-x-auto">
                    <table class="oy-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Unit Price</th>
                                <th>Line Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="font-medium text-slate-900">
                                            {{ $item->item_name }}
                                        </div>

                                        @if($item->description)
                                            <div class="mt-1 max-w-xl whitespace-pre-line text-xs text-slate-500">
                                                {{ $item->description }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>{{ number_format((float) $item->quantity, 2) }}</td>
                                    <td>{{ $item->unit }}</td>
                                    <td>₦{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="font-medium text-slate-900">
                                        ₦{{ number_format((float) $item->line_total, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @endif

        </div>

    </div>

    <div class="grid gap-6 lg:grid-cols-2">

        <div class="oy-card oy-section">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Payment</h2>
                <p class="oy-card-description">
                    Verified payment records associated with the quotation.
                </p>
            </div>

            <div class="oy-card-body">

                @php
                    $totalPaid = (float) $order->quotation->payments
                        ->where('status', 'completed')
                        ->sum(fn ($payment) => (float) $payment->amount);

                    $balanceDue = max(
                        0,
                        (float) $order->total - $totalPaid
                    );

                    $isFullyPaid = $balanceDue <= 0.01;

                    $paymentArrangement =
                        $order->delivery?->payment_arrangement;

                    $paymentArrangementLabel = match ($paymentArrangement) {
                        \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAY_NOW =>
                            'Pay Now',

                        \App\Models\Delivery::PAYMENT_ARRANGEMENT_PAY_ON_DELIVERY =>
                            'Pay on Delivery',

                        default =>
                            'Not Selected',
                    };
                @endphp

                <div class="mb-5 rounded-lg border border-slate-200 p-4">
                    <div class="flex justify-between gap-4">
                        <span class="text-sm text-slate-500">
                            Payment Status
                        </span>

                        <span class="oy-badge {{ $isFullyPaid ? 'oy-badge-success' : 'oy-badge-warning' }}">
                            {{ $isFullyPaid ? 'Paid in Full' : 'Partially Paid' }}
                        </span>
                    </div>

                    <div class="mt-3 flex justify-between gap-4">
                        <span class="text-sm text-slate-500">
                            Payment Arrangement
                        </span>

                        <span class="font-medium text-slate-900">
                            {{ $paymentArrangementLabel }}
                        </span>
                    </div>

                    <div class="mt-3 flex justify-between gap-4">
                        <span class="text-sm text-slate-500">
                            Order Total
                        </span>

                        <span class="font-medium text-slate-900">
                            ₦{{ number_format((float) $order->total, 2) }}
                        </span>
                    </div>

                    <div class="mt-2 flex justify-between gap-4">
                        <span class="text-sm text-slate-500">
                            Amount Paid
                        </span>

                        <span class="font-medium text-slate-900">
                            ₦{{ number_format($totalPaid, 2) }}
                        </span>
                    </div>

                    <div class="mt-2 flex justify-between gap-4 border-t border-slate-200 pt-3">
                        <span class="font-semibold text-slate-900">
                            Balance Due
                        </span>

                        <span class="font-bold text-slate-900">
                            ₦{{ number_format($balanceDue, 2) }}
                        </span>
                    </div>
                </div>

                @forelse($order->quotation->payments as $payment)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="flex justify-between gap-4">
                            <span class="text-sm text-slate-500">
                                Reference
                            </span>

                            <span class="text-sm font-medium text-slate-900">
                                {{ $payment->reference }}
                            </span>
                        </div>

                        <div class="mt-2 flex justify-between gap-4">
                            <span class="text-sm text-slate-500">
                                Amount
                            </span>

                            <span class="font-medium text-slate-900">
                                ₦{{ number_format((float) $payment->amount, 2) }}
                            </span>
                        </div>

                        <div class="mt-2 flex justify-between gap-4">
                            <span class="text-sm text-slate-500">
                                Recorded
                            </span>

                            <span class="text-sm text-slate-700">
                                {{ $payment->paid_at?->format('d M Y H:i') ?? '—' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-slate-500">
                        No payment record is attached to this quotation.
                    </div>
                @endforelse

            </div>
        </div>

        <div class="oy-card oy-section">
            <div class="oy-card-header">
                <h2 class="oy-card-title">Terms & Notes</h2>
            </div>

            <div class="oy-card-body">

                @if($order->terms)
                    <div>
                        <div class="oy-meta">Terms</div>
                        <div class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                            {{ $order->terms }}
                        </div>
                    </div>
                @endif

                @if($order->notes)
                    <div class="@if($order->terms) mt-6 border-t border-slate-200 pt-6 @endif">
                        <div class="oy-meta">Internal Notes</div>
                        <div class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                            {{ $order->notes }}
                        </div>
                    </div>
                @endif

                @if(!$order->terms && !$order->notes)
                    <div class="text-sm text-slate-500">
                        No terms or internal notes recorded.
                    </div>
                @endif

            </div>
        </div>

    </div>

</div>


@endsection
