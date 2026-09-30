<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'Quotation' }} — Oneyard Outfitters</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <div>
                <div class="text-lg font-bold text-slate-900">
                    Oneyard Outfitters
                </div>

                <div class="text-xs text-slate-500">
                    Institutional Supply & Uniform Management
                </div>
            </div>
        </div>
    </header>

    <main class="px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mx-auto mb-6 max-w-5xl rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mx-auto mb-6 max-w-5xl rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-6 text-center text-xs text-slate-500 sm:px-6 lg:px-8">
            Oneyard Outfitters
        </div>
    </footer>
</body>
</html>
