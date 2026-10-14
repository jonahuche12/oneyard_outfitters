@extends('layouts.app')

@section('title', 'Set Up Staff Account')

@section('content')
    <div class="mx-auto w-full max-w-md space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                Set up your staff account
            </h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Create a password for
                <span class="font-medium text-slate-900">{{ $email }}</span>.
                This one-use link expires after 24 hours.
            </p>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-medium text-red-800">
                    Please correct the following:
                </p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ route('staff.onboarding.store', ['token' => $token]) }}"
              class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf

            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-slate-700">
                    New password
                </label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    autofocus
                    class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                >
                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation"
                       class="mb-2 block text-sm font-medium text-slate-700">
                    Confirm new password
                </label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                >
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                Set password
            </button>
        </form>

        <p class="text-xs leading-5 text-slate-500">
            Your account becomes active as soon as you successfully set your password.
        </p>
    </div>
@endsection
