@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
    <div class="mx-auto w-full max-w-md space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                Forgot password?
            </h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Enter your staff email address. If it matches an eligible account,
                we’ll send instructions to reset your password.
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

        <form method="POST" action="{{ route('password.email') }}"
              class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf

            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-slate-700">
                    Email address
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    required
                    autofocus
                    class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    placeholder="staff@example.com"
                >
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                Send reset instructions
            </button>
        </form>

        <p class="text-center text-sm text-slate-500">
            Remember your password?
            <a href="{{ route('login') }}" class="font-medium text-slate-900 hover:underline">
                Sign in
            </a>
        </p>
    </div>
@endsection
