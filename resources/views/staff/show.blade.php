@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <a
                    href="{{ route('staff.index') }}"
                    class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900"
                >
                    ← Back to Staff
                </a>

                <div class="mt-4 flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-slate-900 text-lg font-semibold text-white">
                        {{ strtoupper(substr($staff->name, 0, 1)) }}
                    </div>

                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                            {{ $staff->name }}
                        </h1>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ $staff->email }}
                        </p>
                    </div>
                </div>
            </div>

            @can('update', $staff)
                <a
                    href="{{ route('staff.edit', $staff) }}"
                    class="oy-btn oy-btn-secondary"
                >
                    Edit Staff
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


        {{-- Account Information --}}
        <div class="grid gap-6 lg:grid-cols-2">

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                    Account Information
                </h2>

                <dl class="mt-5 divide-y divide-slate-100">

                    <div class="py-4 first:pt-0">
                        <dt class="text-xs text-slate-500">
                            Full name
                        </dt>

                        <dd class="mt-1 text-sm font-medium text-slate-900">
                            {{ $staff->name }}
                        </dd>
                    </div>

                    <div class="py-4">
                        <dt class="text-xs text-slate-500">
                            Email address
                        </dt>

                        <dd class="mt-1 break-all text-sm font-medium text-slate-900">
                            {{ $staff->email }}
                        </dd>
                    </div>

                    <div class="py-4 last:pb-0">
                        <dt class="text-xs text-slate-500">
                            Account status
                        </dt>

                        <dd class="mt-2">
                            @if ($staff->is_active)
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
                        </dd>
                    </div>

                </dl>
            </section>


            {{-- Roles --}}
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                            Assigned Roles
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Permissions are inherited from assigned roles.
                        </p>
                    </div>

                    @can('assignRoles', $staff)
                        <a
                            href="{{ route('staff.edit', $staff) }}"
                            class="text-xs font-medium text-slate-700 hover:text-slate-900 hover:underline"
                        >
                            Manage
                        </a>
                    @endcan
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    @forelse ($staff->roles as $role)
                        <span class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700">
                            {{ $role->name }}
                        </span>
                    @empty
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm font-medium text-amber-900">
                                No roles assigned.
                            </p>

                            @can('assignRoles', $staff)
                                <p class="mt-1 text-xs text-amber-800">
                                    Edit this account to assign an operational role.
                                </p>
                            @endcan
                        </div>
                    @endforelse
                </div>
            </section>

        </div>


        {{-- Account Actions --}}
        @if (
            auth()->user()->can('activate', $staff)
            || auth()->user()->can('deactivate', $staff)
        )
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">
                    Account Actions
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Control whether this staff account can access the system.
                </p>

                <div class="mt-5 flex flex-wrap gap-3">

                    @can('activate', $staff)
                        @if (! $staff->is_active)
                            <form
                                method="POST"
                                action="{{ route('staff.activate', $staff) }}"
                            >
                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                                >
                                    Activate Account
                                </button>
                            </form>
                        @endif
                    @endcan


                    @can('deactivate', $staff)
                        @if ($staff->is_active)
                            @if ($staff->is(auth()->user()))
                                <p class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-600">
                                    You cannot deactivate your own account.
                                </p>
                            @else
                                <form
                                    method="POST"
                                    action="{{ route('staff.deactivate', $staff) }}"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2"
                                    >
                                        Deactivate Account
                                    </button>
                                </form>
                            @endif
                        @endif
                    @endcan

                </div>
            </section>
        @endif

    </div>
@endsection
