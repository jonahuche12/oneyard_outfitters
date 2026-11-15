@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('stores.show', $store) }}" class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900">
                ← Back to {{ $store->name }}
            </a>

            <h1 class="mt-4 text-2xl font-semibold tracking-tight text-slate-900">Edit Store</h1>
        </div>

        <form
            method="POST"
            action="{{ route('stores.update', $store) }}"
            class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            @csrf
            @method('PUT')

            @include('stores.partials.form', ['store' => $store])

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('stores.show', $store) }}" class="oy-btn oy-btn-secondary">Cancel</a>
                <button type="submit" class="oy-btn oy-btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
@endsection
