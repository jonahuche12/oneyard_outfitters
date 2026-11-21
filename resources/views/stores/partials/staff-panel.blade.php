@php
    $reopen = old('_staff_modal');
    $reopenAssignment = $history->firstWhere('id', (int) old('_staff_assignment'));
    $coordinator = $store->coordinatorAssignment;
@endphp

@if ($errors->any() && ! $reopen)
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Coordinator --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <h2 class="text-base font-semibold text-slate-900">Store coordinator</h2>

                @if ($canAssign)
                    <button
                        type="button"
                        class="oy-btn oy-btn-secondary"
                        data-store-modal-open="assign"
                        data-role="coordinator"
                        data-title="{{ $coordinator ? 'Replace coordinator' : 'Assign coordinator' }}"
                    >
                        {{ $coordinator ? 'Replace coordinator' : 'Assign coordinator' }}
                    </button>
                @endif
            </div>

            @if ($coordinator)
                <div class="mt-4">
                    <div class="font-medium text-slate-900">{{ $coordinator->user->name }}</div>
                    <div class="mt-1 text-xs text-slate-500">
                        Since {{ $coordinator->starts_at->format('d M Y') }}
                    </div>

                    @if ($canManageStaff)
                        <div class="mt-3 flex flex-wrap gap-3 text-sm font-medium">
                            <button
                                type="button"
                                class="text-slate-600 hover:text-slate-900"
                                data-store-modal-open="move"
                                data-title="Move or change role"
                                data-action="{{ route('stores.staff.move', [$store, $coordinator]) }}"
                                data-assignment="{{ $coordinator->id }}"
                                data-person="{{ $coordinator->user->name }}"
                                data-store="{{ $store->id }}"
                                data-role="coordinator"
                            >Move</button>

                            <button
                                type="button"
                                class="text-red-600 hover:text-red-800"
                                data-store-modal-open="end"
                                data-title="End assignment"
                                data-action="{{ route('stores.staff.end', [$store, $coordinator]) }}"
                                data-assignment="{{ $coordinator->id }}"
                                data-person="{{ $coordinator->user->name }}"
                            >End assignment</button>
                        </div>
                    @endif
                </div>
            @else
                <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    No coordinator assigned. A coordinator is needed to receive consignments.
                </p>
            @endif
        </div>

        {{-- Assistants --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <h2 class="text-base font-semibold text-slate-900">Assistants</h2>

                @if ($canAssign)
                    <button
                        type="button"
                        class="oy-btn oy-btn-secondary"
                        data-store-modal-open="assign"
                        data-role="assistant"
                        data-title="Add assistant"
                    >Add assistant</button>
                @endif
            </div>

            @forelse ($store->assistantAssignments as $assistant)
                <div class="mt-4">
                    <div class="font-medium text-slate-900">{{ $assistant->user->name }}</div>
                    <div class="mt-1 text-xs text-slate-500">
                        Since {{ $assistant->starts_at->format('d M Y') }}
                    </div>

                    @if ($canManageStaff)
                        <div class="mt-3 flex flex-wrap gap-3 text-sm font-medium">
                            @if ($canAssign)
                                <button
                                    type="button"
                                    class="text-slate-600 hover:text-slate-900"
                                    data-store-modal-open="move"
                                    data-title="Make coordinator"
                                    data-action="{{ route('stores.staff.move', [$store, $assistant]) }}"
                                    data-assignment="{{ $assistant->id }}"
                                    data-person="{{ $assistant->user->name }}"
                                    data-store="{{ $store->id }}"
                                    data-role="coordinator"
                                >Make coordinator</button>
                            @endif

                            <button
                                type="button"
                                class="text-slate-600 hover:text-slate-900"
                                data-store-modal-open="move"
                                data-title="Move or change role"
                                data-action="{{ route('stores.staff.move', [$store, $assistant]) }}"
                                data-assignment="{{ $assistant->id }}"
                                data-person="{{ $assistant->user->name }}"
                                data-store="{{ $store->id }}"
                                data-role="assistant"
                            >Move</button>

                            <button
                                type="button"
                                class="text-red-600 hover:text-red-800"
                                data-store-modal-open="end"
                                data-title="End assignment"
                                data-action="{{ route('stores.staff.end', [$store, $assistant]) }}"
                                data-assignment="{{ $assistant->id }}"
                                data-person="{{ $assistant->user->name }}"
                            >End assignment</button>
                        </div>
                    @endif
                </div>
            @empty
                <p class="mt-4 text-sm text-slate-600">No assistants assigned.</p>
            @endforelse
        </div>
    </div>

    @can('manageStaff', $store)
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-base font-semibold text-slate-900">Assignment history</h2>
            </div>

            @if ($history->isEmpty())
                <p class="px-6 py-8 text-sm text-slate-600">No staff have been assigned to this store yet.</p>
            @else
                {{-- Mobile assignment cards --}}
                <div class="space-y-3 bg-slate-50/50 p-3 md:hidden">
                    @foreach ($history as $assignment)
                        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="min-w-0 break-words text-sm font-semibold text-slate-900">
                                    {{ $assignment->user->name }}
                                </h3>

                                <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    {{ ucfirst($assignment->role) }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-xs text-slate-500">From</p>
                                    <p class="mt-1 font-medium text-slate-800">
                                        {{ $assignment->starts_at->format('d M Y') }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-slate-500">Until</p>
                                    <p class="mt-1 font-medium text-slate-800">
                                        {{ $assignment->ends_at ? $assignment->ends_at->format('d M Y') : 'Current' }}
                                    </p>
                                </div>

                                <div class="col-span-2 border-t border-slate-100 pt-3">
                                    <p class="text-xs text-slate-500">Reason ended</p>
                                    <p class="mt-1 break-words text-sm text-slate-700">
                                        {{ $assignment->ended_reason ?? '—' }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- Tablet and desktop assignment table --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th scope="col" class="px-4 py-3">Person</th>
                                <th scope="col" class="px-4 py-3">Role</th>
                                <th scope="col" class="px-4 py-3">From</th>
                                <th scope="col" class="px-4 py-3">Until</th>
                                <th scope="col" class="px-4 py-3">Reason ended</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @foreach ($history as $assignment)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $assignment->user->name }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ ucfirst($assignment->role) }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $assignment->starts_at->format('d M Y') }}</td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $assignment->ends_at ? $assignment->ends_at->format('d M Y') : 'Current' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $assignment->ended_reason ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endcan

    @if ($canManageStaff)
        @include('stores.partials.staff-modals')
    @endif
