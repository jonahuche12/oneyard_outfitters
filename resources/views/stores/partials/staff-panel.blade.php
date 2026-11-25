
@php
    $reopen = old('_staff_modal');
    $reopenAssignment = $history->firstWhere('id', (int) old('_staff_assignment'));
    $coordinator = $store->coordinatorAssignment;
@endphp

{{-- Validation errors --}}
@if ($errors->any() && ! $reopen)
    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
        <div class="flex items-start gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-700">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.86 2.5 17.5A2 2 0 0 0 4.23 20h15.54a2 2 0 0 0 1.73-2.5L13.7 3.86a2 2 0 0 0-3.4 0Z"/>
                </svg>
            </div>

            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-rose-900">Please correct the following</h3>
                <div class="mt-2 space-y-1 text-sm text-rose-800">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Current staff --}}
<div class="grid gap-5 lg:grid-cols-2">

    {{-- Coordinator --}}
    <section class="overflow-hidden rounded-2xl border border-blue-200 bg-white shadow-sm">
        <div class="border-b border-blue-100 bg-gradient-to-r from-blue-50 via-white to-indigo-50 px-4 py-4 sm:px-5">
            <div class="flex items-start justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700 ring-1 ring-blue-200">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m14-13.5a3.5 3.5 0 1 1 0 7m4.5 6.5v-2a4 4 0 0 0-3-3.87M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-blue-950 sm:text-base">Store coordinator</h2>
                        <p class="mt-0.5 text-xs text-blue-700">Primary store responsibility</p>
                    </div>
                </div>

                @if ($coordinator)
                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Assigned
                    </span>
                @else
                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        Unassigned
                    </span>
                @endif
            </div>
        </div>

        <div class="p-4 sm:p-5">
            @if ($coordinator)
                <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m14-13.5a3.5 3.5 0 1 1 0 7m4.5 6.5v-2a4 4 0 0 0-3-3.87M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1">
                            <h3 class="break-words text-base font-bold text-slate-900">
                                {{ $coordinator->user->name }}
                            </h3>

                            <span class="mt-1 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">
                                Store coordinator
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-2 border-t border-blue-100 pt-3 text-sm text-slate-600">
                        <svg class="h-4 w-4 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="12" cy="12" r="9"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/>
                        </svg>
                        <span>Assigned since</span>
                        <span class="font-semibold text-slate-800">{{ $coordinator->starts_at->format('d M Y') }}</span>
                    </div>
                </div>

                @if ($canManageStaff)
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-300"
                            data-store-modal-open="move"
                            data-title="Move or change role"
                            data-action="{{ route('stores.staff.move', [$store, $coordinator]) }}"
                            data-assignment="{{ $coordinator->id }}"
                            data-person="{{ $coordinator->user->name }}"
                            data-store="{{ $store->id }}"
                            data-role="coordinator"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10m0 0-3-3m3 3-3 3M17 17H7m0 0 3 3m-3-3 3-3"/>
                            </svg>
                            Move / change role
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-300"
                            data-store-modal-open="end"
                            data-title="End assignment"
                            data-action="{{ route('stores.staff.end', [$store, $coordinator]) }}"
                            data-assignment="{{ $coordinator->id }}"
                            data-person="{{ $coordinator->user->name }}"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6h12M9 6l3-3M9 6l3 3M15 18H3m12 0-3-3m3 3-3 3"/>
                            </svg>
                            End assignment
                        </button>
                    </div>
                @endif
            @else
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.86 2.5 17.5A2 2 0 0 0 4.23 20h15.54a2 2 0 0 0 1.73-2.5L13.7 3.86a2 2 0 0 0-3.4 0Z"/>
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-amber-900">No coordinator assigned</h3>
                            <p class="mt-1 text-sm leading-5 text-amber-800">
                                A coordinator is needed to receive consignments and manage store operations.
                            </p>
                        </div>
                    </div>

                    @if ($canAssign)
                        <button
                            type="button"
                            class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 sm:w-auto"
                            data-store-modal-open="assign"
                            data-role="coordinator"
                            data-title="Assign coordinator"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/>
                            </svg>
                            Assign coordinator
                        </button>
                    @endif
                </div>
            @endif

            @if ($coordinator && $canAssign)
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <button
                        type="button"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300"
                        data-store-modal-open="assign"
                        data-role="coordinator"
                        data-title="Replace coordinator"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m14-13.5a3.5 3.5 0 1 1 0 7m4.5 6.5v-2a4 4 0 0 0-3-3.87M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                        </svg>
                        Replace coordinator
                    </button>
                </div>
            @endif
        </div>
    </section>

    {{-- Assistants --}}
    <section class="overflow-hidden rounded-2xl border border-violet-200 bg-white shadow-sm">
        <div class="border-b border-violet-100 bg-gradient-to-r from-violet-50 via-white to-fuchsia-50 px-4 py-4 sm:px-5">
            <div class="flex items-start justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700 ring-1 ring-violet-200">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m14-13.5a3.5 3.5 0 1 1 0 7m4.5 6.5v-2a4 4 0 0 0-3-3.87M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-violet-950 sm:text-base">Assistants</h2>
                        <p class="mt-0.5 text-xs text-violet-700">Supporting store operations</p>
                    </div>
                </div>

                <span class="inline-flex shrink-0 items-center rounded-full border border-violet-200 bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700">
                    {{ $store->assistantAssignments->count() }} {{ Str::plural('assistant', $store->assistantAssignments->count()) }}
                </span>
            </div>
        </div>

        <div class="p-4 sm:p-5">
            @if ($store->assistantAssignments->isNotEmpty())
                <div class="space-y-3">
                    @foreach ($store->assistantAssignments as $assistant)
                        <article class="rounded-xl border border-violet-100 bg-violet-50/50 p-4 transition hover:border-violet-200 hover:bg-violet-50">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-600 text-white shadow-sm">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m14-13.5a3.5 3.5 0 1 1 0 7m4.5 6.5v-2a4 4 0 0 0-3-3.87M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                                    </svg>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h3 class="break-words text-sm font-semibold text-slate-900">
                                        {{ $assistant->user->name }}
                                    </h3>

                                    <span class="mt-1 inline-flex items-center rounded-full bg-violet-100 px-2 py-0.5 text-xs font-semibold text-violet-700">
                                        Store assistant
                                    </span>

                                    <div class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                                        <svg class="h-3.5 w-3.5 text-violet-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <circle cx="12" cy="12" r="9"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/>
                                        </svg>
                                        Assigned since {{ $assistant->starts_at->format('d M Y') }}
                                    </div>
                                </div>
                            </div>

                            @if ($canManageStaff)
                                <div class="mt-3 flex flex-wrap gap-2 border-t border-violet-100 pt-3">
                                    @if ($canAssign)
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                            data-store-modal-open="move"
                                            data-title="Make coordinator"
                                            data-action="{{ route('stores.staff.move', [$store, $assistant]) }}"
                                            data-assignment="{{ $assistant->id }}"
                                            data-person="{{ $assistant->user->name }}"
                                            data-store="{{ $store->id }}"
                                            data-role="coordinator"
                                        >
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m12 3 2.8 5.7L21 9.6l-4.5 4.4 1.1 6.3-5.6-3-5.6 3 1.1-6.3L3 9.6l6.2-.9L12 3Z"/>
                                            </svg>
                                            Make coordinator
                                        </button>
                                    @endif

                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                                        data-store-modal-open="move"
                                        data-title="Move or change role"
                                        data-action="{{ route('stores.staff.move', [$store, $assistant]) }}"
                                        data-assignment="{{ $assistant->id }}"
                                        data-person="{{ $assistant->user->name }}"
                                        data-store="{{ $store->id }}"
                                        data-role="assistant"
                                    >
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10m0 0-3-3m3 3-3 3M17 17H7m0 0 3 3m-3-3 3-3"/>
                                        </svg>
                                        Move / change role
                                    </button>

                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"
                                        data-store-modal-open="end"
                                        data-title="End assignment"
                                        data-action="{{ route('stores.staff.end', [$store, $assistant]) }}"
                                        data-assignment="{{ $assistant->id }}"
                                        data-person="{{ $assistant->user->name }}"
                                    >
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6h12M9 6l3-3M9 6l3 3M15 18H3m12 0-3-3m3 3-3 3"/>
                                        </svg>
                                        End assignment
                                    </button>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl border border-dashed border-violet-200 bg-violet-50/50 px-4 py-7 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-violet-100 text-violet-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m14-13.5a3.5 3.5 0 1 1 0 7m4.5 6.5v-2a4 4 0 0 0-3-3.87M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                        </svg>
                    </div>

                    <h3 class="mt-3 text-sm font-semibold text-violet-900">No assistants assigned</h3>
                    <p class="mt-1 text-sm text-violet-700">Assistants support daily store activities.</p>
                </div>
            @endif

            @if ($canAssign)
                <button
                    type="button"
                    class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-semibold text-violet-700 transition hover:bg-violet-100 focus:outline-none focus:ring-2 focus:ring-violet-300"
                    data-store-modal-open="assign"
                    data-role="assistant"
                    data-title="Add assistant"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/>
                    </svg>
                    Add assistant
                </button>
            @endif
        </div>
    </section>
</div>

{{-- Assignment history --}}
@can('manageStaff', $store)
    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-blue-50 px-4 py-4 sm:px-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-slate-900 sm:text-base">Assignment history</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Previous and current staff assignments</p>
                    </div>
                </div>

                <span class="inline-flex shrink-0 items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-600">
                    {{ $history->count() }} records
                </span>
            </div>
        </div>

        @if ($history->isEmpty())
            <div class="px-5 py-10 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m14-13.5a3.5 3.5 0 1 1 0 7m4.5 6.5v-2a4 4 0 0 0-3-3.87M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                    </svg>
                </div>

                <h3 class="mt-3 text-sm font-semibold text-slate-800">No assignment history</h3>
                <p class="mt-1 text-sm text-slate-500">Staff assignments will appear here when people are assigned to this store.</p>
            </div>
        @else
            {{-- Mobile assignment history --}}
            <div class="space-y-3 bg-slate-50/70 p-3 md:hidden">
                @foreach ($history as $assignment)
                    <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-start gap-3 p-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg
                                {{ $assignment->role === 'coordinator' ? 'bg-blue-100 text-blue-700' : 'bg-violet-100 text-violet-700' }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m14-13.5a3.5 3.5 0 1 1 0 7m4.5 6.5v-2a4 4 0 0 0-3-3.87M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                                </svg>
                            </div>

                            <div class="min-w-0 flex-1">
                                <h3 class="break-words text-sm font-semibold text-slate-900">
                                    {{ $assignment->user->name }}
                                </h3>

                                <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                    {{ $assignment->role === 'coordinator' ? 'bg-blue-100 text-blue-800' : 'bg-violet-100 text-violet-800' }}">
                                    {{ ucfirst($assignment->role) }}
                                </span>

                                <div class="mt-2">
                                    @if ($assignment->ends_at)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            Past assignment
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Current assignment
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 border-t border-slate-100 bg-slate-50/70 p-4">
                            <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-3">
                                <div class="flex items-center gap-1.5 text-xs font-medium text-emerald-700">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/>
                                    </svg>
                                    Start date
                                </div>
                                <p class="mt-1.5 text-sm font-semibold text-emerald-900">
                                    {{ $assignment->starts_at->format('d M Y') }}
                                </p>
                            </div>

                            <div class="rounded-lg border {{ $assignment->ends_at ? 'border-slate-200 bg-slate-100' : 'border-blue-100 bg-blue-50' }} p-3">
                                <div class="flex items-center gap-1.5 text-xs font-medium {{ $assignment->ends_at ? 'text-slate-600' : 'text-blue-700' }}">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                                    </svg>
                                    End date
                                </div>
                                <p class="mt-1.5 text-sm font-semibold {{ $assignment->ends_at ? 'text-slate-800' : 'text-blue-900' }}">
                                    {{ $assignment->ends_at ? $assignment->ends_at->format('d M Y') : 'Current' }}
                                </p>
                            </div>

                            <div class="col-span-2 rounded-lg border border-amber-100 bg-amber-50 p-3">
                                <div class="flex items-center gap-1.5 text-xs font-medium text-amber-700">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.86 2.5 17.5A2 2 0 0 0 4.23 20h15.54a2 2 0 0 0 1.73-2.5L13.7 3.86a2 2 0 0 0-3.4 0Z"/>
                                    </svg>
                                    Reason ended
                                </div>
                                <p class="mt-1.5 break-words text-sm text-amber-900">
                                    {{ $assignment->ended_reason ?? '—' }}
                                </p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Desktop assignment history --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3.5">Staff member</th>
                            <th scope="col" class="px-4 py-3.5">Role</th>
                            <th scope="col" class="px-4 py-3.5">Start date</th>
                            <th scope="col" class="px-4 py-3.5">End date</th>
                            <th scope="col" class="px-4 py-3.5">Status</th>
                            <th scope="col" class="px-4 py-3.5">Reason ended</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach ($history as $assignment)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg
                                            {{ $assignment->role === 'coordinator' ? 'bg-blue-100 text-blue-700' : 'bg-violet-100 text-violet-700' }}">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m14-13.5a3.5 3.5 0 1 1 0 7m4.5 6.5v-2a4 4 0 0 0-3-3.87M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                                            </svg>
                                        </div>
                                        <span class="font-semibold text-slate-900">{{ $assignment->user->name }}</span>
                                    </div>
                                </td>

                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                        {{ $assignment->role === 'coordinator' ? 'bg-blue-100 text-blue-800' : 'bg-violet-100 text-violet-800' }}">
                                        {{ ucfirst($assignment->role) }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-4 py-3.5 text-slate-700">
                                    {{ $assignment->starts_at->format('d M Y') }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3.5 text-slate-700">
                                    {{ $assignment->ends_at ? $assignment->ends_at->format('d M Y') : '—' }}
                                </td>

                                <td class="px-4 py-3.5">
                                    @if ($assignment->ends_at)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            Past
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Current
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3.5 text-slate-600">
                                    {{ $assignment->ended_reason ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endcan

{{-- Staff assignment modals --}}
@if ($canManageStaff)
    @include('stores.partials.staff-modals')
@endif
