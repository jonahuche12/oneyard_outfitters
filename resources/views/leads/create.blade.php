@extends('layouts.app')

@section('title', 'Create Lead')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-6">
        <a href="{{ route('leads.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
            ← Back to Leads
        </a>
        <h1 class="mt-3 text-2xl font-semibold text-gray-900">Create Lead</h1>
    </div>

    <form method="POST" action="{{ route('leads.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf

        @include('leads._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('leads.index') }}"
               class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancel
            </a>

            <button type="submit"
                    class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-800">
                Create Lead
            </button>
        </div>
    </form>
</div>
@endsection
