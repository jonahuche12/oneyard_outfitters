@extends('layouts.app')

@section('content')
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                        Organizations
                    </h1>

                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                        {{ $organizations->total() }}
                    </span>
                </div>

                <p class="mt-1 text-sm text-slate-600">
                    Manage schools, businesses and other organizations served by Oneyard Outfitters.
                </p>
            </div>

            @can('create', App\Models\Organization::class)
                <a
                    href="{{ route('organizations.create') }}"
                    class="oy-btn oy-btn-primary"
                >
                    + Add Organization
                </a>
            @endcan
        </div>


        {{-- Feedback --}}
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif


        {{-- Search --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('organizations.index') }}">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="min-w-0 flex-1">
                        <label for="organization-search" class="sr-only">
                            Search organizations
                        </label>

                        <input
                            id="organization-search"
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search by code, name, type, city or state..."
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
                            href="{{ route('organizations.index') }}"
                            class="oy-btn oy-btn-secondary"
                        >
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>


        {{-- Organization Table --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-slate-900">
                    Organization Records
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    View and manage organization information from the actions on each record.
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
                                Organization
                            </th>

                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                            >
                                Type
                            </th>

                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                            >
                                Location
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

                        @forelse ($organizations as $organization)
                            <tr class="transition hover:bg-slate-50">

                                {{-- Organization --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-sm font-semibold text-white"
                                        >
                                            {{ strtoupper(substr($organization->name, 0, 1)) }}
                                        </div>

                                        <div class="min-w-0">
                                            <div class="truncate font-medium text-slate-900">
                                                {{ $organization->name }}
                                            </div>

                                            <div class="truncate text-sm font-mono text-slate-500">
                                                {{ $organization->organization_code }}
                                            </div>
                                        </div>
                                    </div>
                                </td>


                                {{-- Type --}}
                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium capitalize text-slate-700">
                                        {{ str_replace('_', ' ', $organization->type) }}
                                    </span>
                                </td>


                                {{-- Location --}}
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-700">
                                        {{ $organization->city ?: '—' }}
                                    </div>

                                    @if ($organization->state)
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $organization->state }}
                                        </div>
                                    @endif
                                </td>


                                {{-- Status --}}
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if ($organization->is_active)
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

                                        @can('view', $organization)
                                            <a
                                                href="{{ route('organizations.show', $organization) }}"
                                                class="oy-btn oy-btn-secondary oy-btn-sm"
                                            >
                                                View
                                            </a>
                                        @endcan

                                        @can('update', $organization)
                                            <a
                                                href="{{ route('organizations.edit', $organization) }}"
                                                class="oy-btn oy-btn-secondary oy-btn-sm"
                                            >
                                                Edit
                                            </a>
                                        @endcan

                                        @can('activate', $organization)
                                            @if (! $organization->is_active)
                                                <form
                                                    method="POST"
                                                    action="{{ route('organizations.activate', $organization) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <button
                                                        type="submit"
                                                        class="oy-btn oy-btn-success oy-btn-sm"
                                                    >
                                                        <svg
                                                            class="h-3.5 w-3.5"
                                                            viewBox="0 0 20 20"
                                                            fill="currentColor"
                                                            aria-hidden="true"
                                                        >
                                                            <path
                                                                fill-rule="evenodd"
                                                                d="M16.704 4.153a.75.75 0 01.143 1.052l-7.5 9.5a.75.75 0 01-1.127.075l-4-4a.75.75 0 011.06-1.06l3.403 3.402 6.97-8.828a.75.75 0 011.051-.141z"
                                                                clip-rule="evenodd"
                                                            />
                                                        </svg>

                                                        Activate
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan

                                        @can('deactivate', $organization)
                                            @if ($organization->is_active)
                                                <form
                                                    method="POST"
                                                    action="{{ route('organizations.deactivate', $organization) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <button
                                                        type="submit"
                                                        class="oy-btn oy-btn-danger oy-btn-sm"
                                                    >
                                                        <svg
                                                            class="h-3.5 w-3.5"
                                                            viewBox="0 0 20 20"
                                                            fill="currentColor"
                                                            aria-hidden="true"
                                                        >
                                                            <path
                                                                fill-rule="evenodd"
                                                                d="M4.25 9.25a.75.75 0 01.75-.75h10a.75.75 0 010 1.5H5a.75.75 0 01-.75-.75z"
                                                                clip-rule="evenodd"
                                                            />
                                                        </svg>

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
                                <td colspan="5" class="px-6 py-16 text-center">

                                    <div class="text-sm font-medium text-slate-900">
                                        No organizations found.
                                    </div>

                                    @if ($search !== '')
                                        <p class="mt-1 text-sm text-slate-500">
                                            Try another search or clear the current filter.
                                        </p>

                                        <a
                                            href="{{ route('organizations.index') }}"
                                            class="mt-4 inline-flex text-sm font-medium text-slate-900 hover:underline"
                                        >
                                            Clear search
                                        </a>
                                    @else
                                        <p class="mt-1 text-sm text-slate-500">
                                            Register the first organization to begin managing your institutional relationships.
                                        </p>

                                        @can('create', App\Models\Organization::class)
                                            <a
                                                href="{{ route('organizations.create') }}"
                                                class="mt-4 inline-flex text-sm font-medium text-slate-900 hover:underline"
                                            >
                                                Add Organization
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
            @if ($organizations->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $organizations->links() }}
                </div>
            @endif

        </div>

    </div>
@endsection
