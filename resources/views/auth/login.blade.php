<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Sign In — Oneyard Outfitters</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-950">

<div class="flex min-h-screen">

    <div class="hidden flex-1 items-center justify-center bg-slate-900 p-12 lg:flex">
        <div class="max-w-lg">
            <div class="mb-8">
                <div class="text-3xl font-bold tracking-tight text-white">
                    Oneyard
                </div>

                <div class="mt-1 text-sm font-medium uppercase tracking-[0.25em] text-slate-400">
                    Outfitters
                </div>
            </div>

            <h1 class="text-4xl font-semibold leading-tight text-white">
                Institutional supply,
                <span class="text-slate-400">properly organized.</span>
            </h1>

            <p class="mt-6 text-base leading-7 text-slate-400">
                Manage organizations, requirements, quotations, orders,
                production, payments and delivery from one operational system.
            </p>
        </div>
    </div>

    <div class="flex w-full items-center justify-center bg-white px-6 py-12 lg:w-[480px]">

        <div class="w-full max-w-sm">

            <div class="mb-10 lg:hidden">
                <div class="text-2xl font-bold text-slate-900">
                    Oneyard
                </div>

                <div class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500">
                    Outfitters
                </div>
            </div>

            <div class="mb-8">
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Sign in
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Access your Oneyard staff account.
                </p>
            </div>

            @if($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
                    <p class="text-sm text-red-700">
                        {{ $errors->first() }}
                    </p>
                </div>
            @endif

            @if(session('status'))
                <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-sm text-emerald-700">
                        {{ session('status') }}
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label
                        for="email"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
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
                        class="block w-full rounded-lg border border-slate-300 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label
                            for="password"
                            class="block text-sm font-medium text-slate-700"
                        >
                            Password
                        </label>

                        @if(Route::has('password.request'))
                            <a
                                href="{{ route('password.request') }}"
                                class="text-xs font-medium text-slate-600 hover:text-slate-900"
                            >
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="block w-full rounded-lg border border-slate-300 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>

                <label class="flex items-center gap-3">
                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                        class="h-4 w-4 rounded border-slate-300"
                    >

                    <span class="text-sm text-slate-600">
                        Remember me
                    </span>
                </label>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2"
                >
                    Sign in
                </button>
            </form>

            <p class="mt-8 text-center text-xs leading-5 text-slate-400">
                Staff accounts are created and managed by authorized
                Oneyard administrators.
            </p>

        </div>

    </div>

</div>

</body>
</html>