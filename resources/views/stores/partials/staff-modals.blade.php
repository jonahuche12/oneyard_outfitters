@if ($canAssign)
    {{-- Assign staff --}}
    <div
        id="store-assign-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="store-assign-title"
        data-current-coordinator="{{ $coordinator?->user->name }}"
        @if ($reopen === 'assign') data-reopen="1" @endif
    >
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h2 id="store-assign-title" class="text-lg font-semibold text-slate-900" data-store-modal-title>Assign staff</h2>

            <form method="POST" action="{{ route('stores.staff.assign', $store) }}" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="_staff_modal" value="assign">

                @if ($reopen === 'assign' && $errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                @if ($assignableUsers->isEmpty())
                    <p class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                        No active staff are free to assign. Everyone active already belongs to a store.
                    </p>
                @endif

                <div>
                    <label for="store-assign-user" class="block text-sm font-medium text-slate-700">Person</label>
                    <select
                        id="store-assign-user"
                        name="user_id"
                        required
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                    >
                        <option value="">Choose a person</option>
                        @foreach ($assignableUsers as $candidate)
                            <option value="{{ $candidate->id }}" @selected((string) old('user_id') === (string) $candidate->id)>
                                {{ $candidate->name }} ({{ $candidate->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="store-assign-role" class="block text-sm font-medium text-slate-700">Role</label>
                    <select
                        id="store-assign-role"
                        name="role"
                        required
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                    >
                        <option value="assistant" @selected(old('role', 'assistant') === 'assistant')>Assistant</option>
                        <option value="coordinator" @selected(old('role') === 'coordinator')>Coordinator</option>
                    </select>
                </div>

                <p id="store-assign-replace-hint" class="hidden rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    This ends {{ $coordinator?->user->name }}'s assignment as coordinator.
                    They will have no store until they are assigned again.
                </p>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" class="oy-btn oy-btn-secondary" data-store-modal-close>Cancel</button>
                    <button type="submit" class="oy-btn oy-btn-primary" @disabled($assignableUsers->isEmpty())>Assign</button>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- Move or change role --}}
<div
    id="store-move-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="store-move-title"
    @if ($reopen === 'move') data-reopen="1" @endif
>
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <h2 id="store-move-title" class="text-lg font-semibold text-slate-900" data-store-modal-title>Move or change role</h2>
        <p class="mt-1 text-sm text-slate-600">
            For <span class="font-medium text-slate-900" data-store-person>{{ $reopen === 'move' ? $reopenAssignment?->user->name : '' }}</span>.
            Choose the same store and a different role to change their role.
        </p>

        <form
            method="POST"
            action="{{ $reopen === 'move' && $reopenAssignment ? route('stores.staff.move', [$store, $reopenAssignment]) : '' }}"
            class="mt-4 space-y-4"
        >
            @csrf
            <input type="hidden" name="_staff_modal" value="move">
            <input type="hidden" name="_staff_assignment" value="{{ $reopen === 'move' ? old('_staff_assignment') : '' }}">

            @if ($reopen === 'move' && $errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div>
                <label for="store-move-store" class="block text-sm font-medium text-slate-700">Store</label>
                <select
                    id="store-move-store"
                    name="to_store_id"
                    required
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                >
                    <option value="">Choose a store</option>
                    @foreach ($destinationStores as $destination)
                        <option value="{{ $destination->id }}" @selected((string) old('to_store_id') === (string) $destination->id)>
                            {{ $destination->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="store-move-role" class="block text-sm font-medium text-slate-700">Role</label>
                <select
                    id="store-move-role"
                    name="role"
                    required
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                >
                    <option value="assistant" @selected(old('role', 'assistant') === 'assistant')>Assistant</option>
                    <option value="coordinator" @selected(old('role') === 'coordinator')>Coordinator</option>
                </select>
            </div>

            <p id="store-move-coordinator-hint" class="hidden rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                Making someone coordinator ends the current coordinator's assignment at that store.
            </p>

            <div class="flex items-center justify-end gap-3">
                <button type="button" class="oy-btn oy-btn-secondary" data-store-modal-close>Cancel</button>
                <button type="submit" class="oy-btn oy-btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- End assignment --}}
<div
    id="store-end-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="store-end-title"
    @if ($reopen === 'end') data-reopen="1" @endif
>
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <h2 id="store-end-title" class="text-lg font-semibold text-slate-900" data-store-modal-title>End assignment</h2>
        <p class="mt-1 text-sm text-slate-600">
            This removes <span class="font-medium text-slate-900" data-store-person>{{ $reopen === 'end' ? $reopenAssignment?->user->name : '' }}</span>
            from the store. The history is kept.
        </p>

        <form
            method="POST"
            action="{{ $reopen === 'end' && $reopenAssignment ? route('stores.staff.end', [$store, $reopenAssignment]) : '' }}"
            class="mt-4 space-y-4"
        >
            @csrf
            <input type="hidden" name="_staff_modal" value="end">
            <input type="hidden" name="_staff_assignment" value="{{ $reopen === 'end' ? old('_staff_assignment') : '' }}">

            @if ($reopen === 'end' && $errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div>
                <label for="store-end-reason" class="block text-sm font-medium text-slate-700">Reason</label>
                <textarea
                    id="store-end-reason"
                    name="reason"
                    rows="3"
                    maxlength="500"
                    required
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                >{{ old('reason') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">At least 10 characters.</p>
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="button" class="oy-btn oy-btn-secondary" data-store-modal-close>Cancel</button>
                <button type="submit" class="oy-btn oy-btn-primary" data-store-end-submit disabled>End assignment</button>
            </div>
        </form>
    </div>
</div>
