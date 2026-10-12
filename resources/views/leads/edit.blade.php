@extends('layouts.app')

@section('title', 'Edit Lead')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-6">
        <a href="{{ route('leads.show', $lead) }}" class="text-sm text-gray-600 hover:text-gray-900">
            ← Back to Lead
        </a>
        <h1 class="mt-3 text-2xl font-semibold text-gray-900">Edit Lead</h1>
    </div>

    <form method="POST" action="{{ route('leads.update', $lead) }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        @include('leads._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('leads.show', $lead) }}"
               class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancel
            </a>

            <button type="submit"
                    class="inline-flex min-h-11 items-center justify-center rounded-lg border border-gray-900 bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 hover:border-gray-800 hover:bg-gray-800 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 active:bg-black">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
