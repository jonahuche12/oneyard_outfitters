@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                @can('viewAny', App\Models\Store::class)
                    <a href="{{ route('stores.index') }}" class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900">
                        ← Back to Stores
                    </a>
                @endcan

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $store->name }}</h1>

                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $store->isWarehouse() ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-700' }}">
                        {{ $store->isWarehouse() ? 'General warehouse' : 'Store' }}
                    </span>

                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $store->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                        {{ $store->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <p class="mt-1 text-sm text-slate-500">Code {{ $store->code }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @can('update', $store)
                    <a href="{{ route('stores.edit', $store) }}" class="oy-btn oy-btn-secondary">Edit Store</a>
                @endcan

                @can('toggleActive', $store)
                    @if (! $store->is_active)
                        <form method="POST" action="{{ route('stores.activate', $store) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="oy-btn oy-btn-primary">Activate</button>
                        </form>
                    @elseif (! $store->isWarehouse())
                        <form method="POST" action="{{ route('stores.deactivate', $store) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="oy-btn oy-btn-secondary">Deactivate</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>

        {{-- Feedback --}}
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('is_active'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first('is_active') }}
            </div>
        @endif

        {{-- Details --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Details</h2>

            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-slate-500">Phone</dt>
                    <dd class="mt-1 text-slate-900">{{ $store->phone ?? '—' }}</dd>
                </div>

                <div>
                    <dt class="text-slate-500">Address</dt>
                    <dd class="mt-1 text-slate-900">{{ $store->address ?? '—' }}</dd>
                </div>

                @can('update', $store)
                    <div>
                        <dt class="text-slate-500">Created</dt>
                        <dd class="mt-1 text-slate-900">
                            {{ $store->created_at->format('d M Y') }}@if ($store->creator) by {{ $store->creator->name }}@endif
                        </dd>
                    </div>
                @endcan
            </dl>
        </div>

        {{-- Staff --}}
        @include('stores.partials.staff-panel')
    </div>
@endsection
