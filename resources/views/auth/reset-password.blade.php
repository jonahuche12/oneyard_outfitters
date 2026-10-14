@extends('layouts.app')

@section('title', 'Reset Password')

@section('content')
    <div class="mx-auto w-full max-w-md space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                Reset password
            </h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Choose a new password for your Oneyard staff account.
            </p>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4">
                <p class="text-sm text-red-700">{{ $errors->first() }}</p>
            </div>
        @endif

        @if (session('status'))
            <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-sm text-emerald-700">{{ session('status') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}"
              class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf

            <input type="hidden" name="token"
                   value="{{ request()->route('token') ?? request()->token }}">

            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-slate-700">
                    Email address
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', request()->email) }}"
                    autocomplete="email"
                    required
                    autofocus
                    class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                >
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-slate-700">
                    New password
                </label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    required
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
                    autocomplete="new-password"
                    required
                    class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                >
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                Reset password
            </button>
        </form>

        <p class="text-center text-sm text-slate-500">
            <a href="{{ route('login') }}" class="font-medium text-slate-900 hover:underline">
                Return to sign in
            </a>
        </p>
    </div>
@endsection
