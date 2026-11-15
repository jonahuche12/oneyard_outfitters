@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('stores.index') }}" class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900">
                ← Back to Stores
            </a>

            <h1 class="mt-4 text-2xl font-semibold tracking-tight text-slate-900">Add Store</h1>
            <p class="mt-1 text-sm text-slate-600">Create a store. Staff are assigned once it exists.</p>
        </div>

        <form
            method="POST"
            action="{{ route('stores.store') }}"
            class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            @csrf

            @include('stores.partials.form', ['warehouseExists' => $warehouseExists])

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('stores.index') }}" class="oy-btn oy-btn-secondary">Cancel</a>
                <button type="submit" class="oy-btn oy-btn-primary">Create Store</button>
            </div>
        </form>
    </div>
@endsection
