@extends('layouts.app')

@section('content')
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                        Staff
                    </h1>

                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                        {{ $staff->total() }}
                    </span>
                </div>

                <p class="mt-1 text-sm text-slate-600">
                    Manage internal Oneyard staff accounts, roles and access.
                </p>
            </div>

            @can('create', App\Models\User::class)
                <a
                    href="{{ route('staff.create') }}"
                    class="oy-btn oy-btn-primary"
                >
                    + Add Staff
                </a>
            @endcan
        </div>


        {{-- Feedback --}}
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('staff'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first('staff') }}
            </div>
        @endif


        {{-- Search --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('staff.index') }}">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="min-w-0 flex-1">
                        <label for="staff-search" class="sr-only">
                            Search staff
                        </label>

                        <input
                            id="staff-search"
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search by name or email..."
                            autocomplete="off"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >
                    </div>

                    <button
                        type="submit"
                        class="oy-btn oy-btn-primary"
                    >
                        Search
                    </button>

                    @if ($search !== '')
                        <a
                            href="{{ route('staff.index') }}"
                            class="oy-btn oy-btn-secondary"
                        >
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>


        {{-- Staff Table --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-slate-900">
                    Staff Accounts
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    View and manage staff access from the actions on each account.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                            >
                                Staff
                            </th>

                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                            >
                                Roles
                            </th>

                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                            >
                                Status
                            </th>

                            <th
                                scope="col"
                                class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200">

                        @forelse ($staff as $member)
                            <tr class="transition hover:bg-slate-50">

                                {{-- Staff --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-900 text-sm font-semibold text-white"
                                        >
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </div>

                                        <div class="min-w-0">
                                            <div class="truncate font-medium text-slate-900">
                                                {{ $member->name }}
                                            </div>

                                            <div class="truncate text-sm text-slate-500">
                                                {{ $member->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>


                                {{-- Roles --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse ($member->roles as $role)
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                                {{ $role->name }}
                                            </span>
                                        @empty
                                            <span class="text-sm text-slate-500">
                                                No roles
                                            </span>
                                        @endforelse
                                    </div>
                                </td>


                                {{-- Status --}}
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if ($member->is_active)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                            Inactive
                                        </span>
                                    @endif
                                </td>


                                {{-- Actions --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap items-center justify-end gap-2">

                                        @can('view', $member)
                                            <a
                                                href="{{ route('staff.show', $member) }}"
                                                class="oy-btn oy-btn-secondary oy-btn-sm"
                                            >
                                                View
                                            </a>
                                        @endcan

                                        @can('update', $member)
                                            <a
                                                href="{{ route('staff.edit', $member) }}"
                                                class="oy-btn oy-btn-secondary oy-btn-sm"
                                            >
                                                Edit
                                            </a>
                                        @endcan

                                        @can('activate', $member)
                                            @if (! $member->is_active)
                                                <form
                                                    method="POST"
                                                    action="{{ route('staff.activate', $member) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-medium text-white transition hover:bg-emerald-800"
                                                    >
                                                        Activate
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan

                                        @can('deactivate', $member)
                                            @if ($member->is_active && ! $member->is(auth()->user()))
                                                <form
                                                    method="POST"
                                                    action="{{ route('staff.deactivate', $member) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg border border-red-300 bg-white px-3 py-2 text-xs font-medium text-red-700 transition hover:bg-red-50"
                                                    >
                                                        Deactivate
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan

                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="4"
                                    class="px-6 py-16 text-center"
                                >
                                    <div class="text-sm font-medium text-slate-900">
                                        No staff accounts found.
                                    </div>

                                    @if ($search !== '')
                                        <p class="mt-1 text-sm text-slate-500">
                                            Try another search or clear the current filter.
                                        </p>

                                        <a
                                            href="{{ route('staff.index') }}"
                                            class="mt-4 inline-flex text-sm font-medium text-slate-900 hover:underline"
                                        >
                                            Clear search
                                        </a>
                                    @else
                                        <p class="mt-1 text-sm text-slate-500">
                                            Create the first staff account to begin managing your team.
                                        </p>

                                        @can('create', App\Models\User::class)
                                            <a
                                                href="{{ route('staff.create') }}"
                                                class="mt-4 inline-flex text-sm font-medium text-slate-900 hover:underline"
                                            >
                                                Add Staff
                                            </a>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>


            {{-- Pagination --}}
            @if ($staff->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $staff->links() }}
                </div>
            @endif

        </div>

    </div>
@endsection
