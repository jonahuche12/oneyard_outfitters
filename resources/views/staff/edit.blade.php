@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">

        {{-- Header --}}
        <div>
            <a
                href="{{ route('staff.show', $staff) }}"
                class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900"
            >
                ← Back to {{ $staff->name }}
            </a>

            <div class="mt-4">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                    Edit Staff
                </h1>

                <p class="mt-1 text-sm text-slate-600">
                    Update account information, security credentials and authorized roles.
                </p>
            </div>
        </div>


        {{-- Form --}}
        <form
            method="POST"
            action="{{ route('staff.update', $staff) }}"
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            @csrf
            @method('PUT')


            {{-- Personal Information --}}
            <section class="p-6 sm:p-8">
                <div class="mb-6">
                    <h2 class="text-base font-semibold text-slate-900">
                        Personal Information
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Update the staff member's basic account information.
                    </p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">

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
                            value="{{ old('name', $staff->name) }}"
                            autocomplete="name"
                            required
                            autofocus
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >

                        @error('name')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>


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
                            value="{{ old('email', $staff->email) }}"
                            autocomplete="email"
                            required
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >

                        @error('email')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                </div>
            </section>


            {{-- Security --}}
            <section class="border-t border-slate-200 p-6 sm:p-8">
                <div class="mb-6">
                    <h2 class="text-base font-semibold text-slate-900">
                        Account Security
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Change the password only when necessary. Leave both fields blank to keep the current password.
                    </p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">

                    <div>
                        <label
                            for="password"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            New password
                        </label>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >

                        @error('password')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>


                    <div>
                        <label
                            for="password_confirmation"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Confirm new password
                        </label>

                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >
                    </div>

                </div>
            </section>


            {{-- Roles --}}
            @can('assignRoles', $staff)
                <section class="border-t border-slate-200 p-6 sm:p-8">
                    <div class="mb-6">
                        <h2 class="text-base font-semibold text-slate-900">
                            Access & Roles
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Select the operational roles assigned to this staff account.
                            Permissions are inherited from these roles.
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
                                old('roles', $staff->roles->pluck('id')->all())
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
            @endcan


            {{-- Actions --}}
            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 p-6 sm:flex-row sm:items-center sm:justify-between sm:px-8">

                <a
                    href="{{ route('staff.show', $staff) }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                >
                    Save Changes
                </button>

            </div>

        </form>
    </div>
@endsection
