@extends('layouts.app')

@section('content')
    @php
        $action = route('contacts.update', $contact);
        $cancelUrl = route('contacts.index');
        $submitLabel = 'Save Changes';
        $organizationLocked = false;
    @endphp

    <div class="mx-auto max-w-5xl space-y-6">
        <div>
            <a href="{{ route('contacts.index') }}"
               class="text-sm font-medium text-slate-600 hover:text-slate-900">
                ← Back to Contacts
            </a>

            <h1 class="mt-3 text-2xl font-semibold text-slate-900">Edit Contact</h1>
            <p class="mt-1 text-sm text-slate-600">
                Update the contact information and organization relationship.
            </p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-medium">Please correct the following:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('contacts.partials.form')
    </div>
@endsection
