@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">

        {{-- Page Header --}}
        <div>
            <a
                href="{{ route('staff.index') }}"
                class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900"
            >
                ← Back to Staff
            </a>

            <div class="mt-4">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                    Create Staff Account
                </h1>

                <p class="mt-1 max-w-2xl text-sm text-slate-600">
                    Create an internal Oneyard user account and configure the access
                    required for their work.
                </p>
            </div>
        </div>


        {{-- Form --}}
        <form
            method="POST"
            action="{{ route('staff.store') }}"
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            @csrf


            {{-- Personal Information --}}
            <section class="p-6 sm:p-8">

                <div class="mb-6">
                    <h2 class="text-base font-semibold text-slate-900">
                        Personal Information
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Basic information used to identify the staff member.
                    </p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">

                    {{-- Full Name --}}
                    <div>
                        <label
                            for="name"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Full name
                        </label>

                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            autocomplete="name"
                            required
                            autofocus
                            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                            placeholder="Enter staff full name"
                        >

                        @error('name')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>


                    {{-- Email --}}
                    <div>
                        <label
                            for="email"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Email address
                        </label>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                            placeholder="staff@oneyard.com"
                        >

                        @error('email')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                </div>

            </section>


            {{-- Account Security --}}
            <section class="border-t border-slate-200 p-6 sm:p-8">

                <div class="mb-6">
                    <h2 class="text-base font-semibold text-slate-900">
                        Account Security
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Set the initial password for this staff account.
                    </p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">

                    {{-- Password --}}
                    <div>
                        <label
                            for="password"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Password
                        </label>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                            placeholder="Enter a secure password"
                        >

                        @error('password')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>


                    {{-- Confirm Password --}}
                    <div>
                        <label
                            for="password_confirmation"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Confirm password
                        </label>

                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                            placeholder="Confirm the password"
                        >

                        @error('password_confirmation')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                </div>

                <div class="mt-4 rounded-lg bg-slate-50 p-4">
                    <p class="text-xs leading-5 text-slate-600">
                        Use a strong password that is difficult to guess.
                        The staff member will use this account to sign in to Oneyard.
                    </p>
                </div>

            </section>


            {{-- Access & Roles --}}
            @if (auth()->user()->hasPermission('users.assign-roles'))

                <section class="border-t border-slate-200 p-6 sm:p-8">

                    <div class="mb-6">
                        <h2 class="text-base font-semibold text-slate-900">
                            Access & Roles
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Select one or more operational roles. Permissions are inherited
                            from the selected roles.
                        </p>
                    </div>

                    @if ($roles->isEmpty())

                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm font-medium text-amber-900">
                                No roles are currently available.
                            </p>

                            <p class="mt-1 text-sm text-amber-800">
                                Create or seed staff roles before assigning access.
                            </p>
                        </div>

                    @else

                        @php
                            $selectedRoles = array_map(
                                'intval',
                                old('roles', [])
                            );
                        @endphp

                        <div class="grid gap-3 sm:grid-cols-2">

                            @foreach ($roles as $role)

                                <label
                                    for="role-{{ $role->id }}"
                                    class="group flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-slate-300 hover:bg-slate-50"
                                >

                                    <input
                                        id="role-{{ $role->id }}"
                                        type="checkbox"
                                        name="roles[]"
                                        value="{{ $role->id }}"
                                        @checked(in_array($role->id, $selectedRoles, true))
                                        class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                    >

                                    <span class="min-w-0">

                                        <span class="block text-sm font-semibold text-slate-900">
                                            {{ $role->name }}
                                        </span>

                                        @if ($role->description)
                                            <span class="mt-1 block text-xs leading-5 text-slate-500">
                                                {{ $role->description }}
                                            </span>
                                        @endif

                                    </span>

                                </label>

                            @endforeach

                        </div>

                        @error('roles')
                            <p class="mt-3 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                        @error('roles.*')
                            <p class="mt-3 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    @endif

                </section>

            @else

                {{-- Role Permission Notice --}}
                <section class="border-t border-slate-200 p-6 sm:p-8">

                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">

                        <div class="flex items-start gap-3">

                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-600">
                                i
                            </div>

                            <div>
                                <p class="text-sm font-medium text-slate-900">
                                    Role assignment is not available for your account.
                                </p>

                                <p class="mt-1 text-sm leading-6 text-slate-600">
                                    The staff account can still be created, but an authorized
                                    administrator will need to assign its operational role later.
                                </p>
                            </div>

                        </div>

                    </div>

                </section>

            @endif


            {{-- Form Actions --}}
            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 p-6 sm:flex-row sm:items-center sm:justify-between sm:px-8">

                <a
                    href="{{ route('staff.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                >
                    Create Staff Account
                </button>

            </div>

        </form>

    </div>
@endsection
